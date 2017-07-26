<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Michael Risher
	 * Date: 5/31/2017
	 * Time: 11:05
	 */
	class users extends Main{

		public function listing( $order = 'username' ){
			if( $this->isAdmin() ){
				$query = "SELECT * FROM users ORDER BY " . $order;//remove limit for a time LIMIT $page,50

				if ( !$result = $this->db->query( $query ) ) {
					echo( 'There was an error running the query [' . $this->db->error . ']' );
				}

				$return = array();
				while ( $row = $result->fetch_assoc() ) {
					$a = array(
						'id' => $row['id'],
						'username' => $row['username']
					);
					array_push( $return, $a );
				}
				if( IS_AJAX ){ echo Core::ajaxResponse( $return ); }
				return $return;
			}
		}

		/**
		 * get the data from the database for a class
		 * only allowed if the user is admin
		 * echos json if ajaxed
		 * returns an array if forced to return
		 * @param $id string id of the class
		 * @param bool|false $forceReturn force a return if the get is not called through ajax
		 * @return array|void array if forceReturn is true echos echos json otherwise
		 */
		public function get( $id, $forceReturn = false ){
			$this->loadModule( 'users' );
			if( $this->users->isAdmin() ) {
				$query = "SELECT * FROM users WHERE id = '$id'";

				if ( !$result = $this->db->query( $query ) ) {
					echo Core::ajaxResponse( array( 'error' => "An error occurred please try again" ), false );
					return;
				}
				$row = $result->fetch_assoc();
				$return = array(
					'id' => $row['id'],
					'username' => $row['username'],
					'isAdmin' => $row['isAdmin'],
					'active' => $row['active']
				);

				if ( IS_AJAX  && !$forceReturn) {
					echo Core::ajaxResponse( $return );
				} else {
					return $return;
				}
			} else {
				echo Core::ajaxResponse( array( 'error' => 'Session expired.<br>Please log in again'), false );
			}
		}

		private function find( $username ){
			$query = "SELECT * FROM users WHERE username = '$username'";

			if ( !$result = $this->db->query( $query ) ) {
				return false;
			}
			$row = $result->fetch_assoc();
			$return = $row;

			$result->close();
			return $return;
		}

		/**
		 * save a class from the admin page
		 * only allowed if the user is admin
		 */
		public function save(){
			$this->loadModule( 'audit' );
			$obj = array();
			$_POST = Core::sanitize( $_POST, true );
			if( isset( $_POST['create'] ) && $_POST['create'] == 'create' ){
				$this->create( $_POST );
			} else{
				if( $this->isAdmin() ) {
					$statement = $this->db->prepare("UPDATE users SET username=?, isAdmin=?, active=? WHERE id=?");
					$statement->bind_param( "siii", $_POST['username'], $_POST['isAdmin'], $_POST['active'], $_POST['id']);
					if( $statement->execute() ){
						$obj['msg'] = "Saved successfully.";
						$this->audit->newEvent( "Updated user: " . $_POST['username'] );
						echo Core::ajaxResponse( $obj );
					} else{
						$obj['error'] = $statement->error;
						echo Core::ajaxResponse( $obj, false );
					}
				} else{
					$obj['error'] = "Session expired.<br>Please log in again";
					echo Core::ajaxResponse( $obj, false );
				}
			}
		}

		/**
		 * get the next unused id in the table
		 */
		private function create( $data ){
			if( $this->isAdmin() ) {
				$this->loadModule( 'audit' );
				$query = "SHOW TABLE STATUS LIKE 'users'";
				$id = -1;
				if ( !$result = $this->db->query( $query ) ) {
					die( 'There was an error running the query [' . $this->db->error . ']' );
				}
				$row = null;
				if ( $result->num_rows ) {
					$row = $result->fetch_assoc();
					$id = $row['Auto_increment'];
					$statement = $this->db->prepare( "INSERT INTO users( username, password, isAdmin, active ) VALUES( ?,' ',?,? )" );
					$statement->bind_param( 'sii', $_POST['username'], $_POST['isAdmin'], $_POST['active'] );
					if( $statement->execute() ){
						$token = $this->createResetPassword( $id, true, false );
						$obj['msg'] = "Created User successfully<br>";
						$obj['msg'] .= "Give the user this link so they can set their password<br>";
						$obj['msg'] .= '<a href="' . CORE_URL . 'users/resetPassword&token=' . $token . '">';
						$obj['msg'] .= CORE_URL . 'users/resetPassword&token=' . $token . "</a>";
						$obj['id'] = $id;
						echo Core::ajaxResponse( $obj, true );
						$this->audit->newEvent( 'Created user: ' . $_POST['username']);
					} else{
						$obj['error'] = $statement->error;
						echo Core::ajaxResponse( $obj, false );
					}
				}
				$result->close();
			} else{
				$obj['error'] = "Session expired.<br>Please log in again";
				echo Core::ajaxResponse( $obj, false );
			}
		}

		/**
		 * delete a class from the admin page
		 * only allowed if the user is admin
		 */
		public function delete(){
			$this->loadModule( 'audit' );
			$obj = array();
			$_POST = Core::sanitize( $_POST, true );
			if( $this->isAdmin() ) {
				$query = "SELECT username FROM users WHERE id = '${_POST['id']}'";
				$event = '';
				if ( !$result = $this->db->query( $query ) ) {
					$event = $_POST['id'];
				}
				$row = $result->fetch_assoc();
				$event = $row['username'];

				$statement = $this->db->prepare("DELETE FROM users WHERE id=?");
				$statement->bind_param( "s", $_POST['id']);
				if( $statement->execute() ){
					$obj['msg'] = "Deleted successfully.";
					$this->audit->newEvent( "Deleted user: " . $event );
					echo Core::ajaxResponse( $obj );
				} else{
					$obj['error'] = $statement->error;
					echo Core::ajaxResponse( $obj, false );
				}
			} else{
				$obj['error'] = "Session expired.<br>Please log in again";
				echo Core::ajaxResponse( $obj, false );
			}
		}

		/**
		 * create a reset password token
		 * @param $id
		 * @param $forceReturn
		 * @return string
		 */
		public function createResetPassword( $id, $forceReturn = false, $noLog = false ){
			$this->loadModule( 'audit' );
			if( $this->isAdmin() ) {
				$hasToken = false;
				$id = Core::sanitize( $id );
				//check if token already exists
				$query = "SELECT * FROM tokens WHERE forUser = " . (int)$id . " AND used = 0";
				if( $result = $this->db->query($query) ){
					$row = null;
					//if there was a user matching
					if( $result->num_rows == 1 ){
						$row = $result->fetch_assoc();
						$token = $row['token'];
						$hasToken = true;
					}
				}

				if( !$hasToken ) {
					//create the token since none exist
					$token = Core::userFriendlyId( 15 );
					$statement = $this->db->prepare( "INSERT INTO tokens(token, forUser, byUser) VALUES (?,?,?)" );
					$statement->bind_param( "sii", $token, $id, $_SESSION['session']['id'] );
					if ( $statement->execute() ) {
						$obj['msg'] = "Give the user this link so they can set their password<br>";
						$obj['msg'] .= '<a href="' . CORE_URL . 'users/resetPassword&token=' . $token . '">';
						$obj['msg'] .= CORE_URL . 'users/resetPassword&token=' . $token . "</a>";
						if( !$noLog ) $this->audit->newEvent( "Reset password for user: " . $_POST['username'] );
						if( !$forceReturn ){
							echo Core::ajaxResponse( $obj );
						} else {
							return $token;
						}
					} else {
						$obj['error'] = $statement->error;
						if( !$forceReturn ){
							echo Core::ajaxResponse( $obj, false );
						} else {
							return $token;
						}
					}
				} else {
					$obj['msg'] = "User already has an existing unused token<br>Give the user this link so they can set their password<br>";
					$obj['msg'] .= '<a href="' . CORE_URL . 'users/resetPassword&token=' . $token . '">';
					$obj['msg'] .= CORE_URL . 'users/resetPassword&token=' . $token . "</a>";
					if( !$forceReturn ){
						echo Core::ajaxResponse( $obj );
					} else {
						return $token;
					}
				}
			}
		}

		/**
		 * displays the reset password
		 */
		public function resetPassword(){
			Core::queueStyle( 'assets/css/reset.css' );
			Core::queueStyle( 'assets/css/ui.css' );
			//put the data onscreen
			include( CORE_PATH . 'pages/resetPassword.php' );
		}

		/**
		 * verify that the user and the token match up
		 */
		public function verifyToken(){
			$_POST = Core::sanitize( $_POST );
			$query = "SELECT * FROM tokens WHERE token = '" . $_POST['token'] . "'";

			$obj = array();
			if( !$result = $this->db->query($query) ){
				$obj['error'] = "An error occurred please try again";
				echo Core::ajaxResponse( $obj, false );
				return;
			}

			$row = null;
			//if there was a user matching
			if( $result->num_rows == 1 ){
				$row = $result->fetch_assoc();
				$user = $this->find( $_POST['user'] );
				if( $row['forUser'] == $user['id'] ){
					if( $row['used'] == 0 ){
						$obj['msg'] = 'Enter a new password';
						echo Core::ajaxResponse( $obj );
					} else {
						$obj['error'] = 'Token has already been used';
						echo Core::ajaxResponse( $obj, false );
					}
				} else {
					$obj['error'] = "Token doesn't match for the username";
					echo Core::ajaxResponse( $obj, false );
				}
				$result->close();
			} else {
				$obj['error'] = "Invalid token";
				echo Core::ajaxResponse( $obj, false );
			}
		}

		/**
		 *
		 */
		public function setPassword(){
			$_POST = Core::sanitize($_POST);
			$user = $this->find( $_POST['user'] );
			$obj = array();
			if( $user ){
				$statement = $this->db->prepare( "UPDATE users SET password=? WHERE id = ?" );
				$pass = $this->hashPassword( $_POST['password'], $_POST['user'] );
				$uid = (int)$user['id'];
				$statement->bind_param( "si", $pass, $uid );
				if ( $statement->execute() ) {
					$obj['msg'] = "Password changed successfully";
					$obj['redirect'] = 'login';
					echo Core::ajaxResponse( $obj );
					if ( isset( $_POST['token'] ) ) {
						$statement = $this->db->prepare( "UPDATE tokens SET used=1 WHERE token = ?" );
						$statement->bind_param( "s", $_POST['token'] );
						$statement->execute();
						$statement->close();
					}
				} else {
					$obj['error'] = "An error occurred please try again";
					echo Core::ajaxResponse( $obj );
				}
			} else{
				$obj['error'] = 'Username does not exist';
				echo Core::ajaxResponse( $obj, false );
			}
		}

		/**
		 * has the password and the username check the database and login
		 */
		public function login(){
			//load translations
			$lang = new Lang( Lang::getCode() );
			//clean the post of any injects
			$_POST = Core::sanitize( $_POST );
			//return array
			$obj = array();

			//check we can even attempt
			$query = "SELECT * FROM loginBan WHERE ip = '" . Core::getIp() ."'";
			if( $result = $this->db->query($query) ){
				if( $result->num_rows > 0 ){
					$row = $result->fetch_assoc();
					if( $row['expires'] > time() ){
						//can't run yet still blocked
						$diff = ( $row['expires'] - time() ) / 60 ;
						$obj['error'] = "You have put in your password incorrectly to many times<br>You can try again in " . ceil( $diff ) . " minutes.";
						echo Core::ajaxResponse( $obj, false );
						return;
					}
				}
			}

			//hash the password and username to salt the password
			$passwordHash = $this->hashPassword( $_POST['password'], $_POST['user'] );
			//create the query to check if the user exists and to verify hashs
			$query = "SELECT * FROM users WHERE username = '${_POST['user']}' AND password = '$passwordHash'";


			//query the database
			if( !$result = $this->db->query($query) ){
//				die('There was an error running the query [' . $this->db->error . ']');
				//if there was an error report it to the user
				$obj['error'] = $lang->o( 'ajaxErrorOccurred' ); //"An error occurred please try again";
				echo Core::ajaxResponse( $obj, false );
				return;
			}
			$row = null;
			//if there was a user matching
			if( $result->num_rows == 1 ){
				$row = $result->fetch_assoc();
				$obj['user'] = $row['username'];
				//if the user is set to active meaning they are allowed to access report that error to them
				if( $row['active'] == 0 ){
					$obj['error'] = $lang->o( 'ajaxLoginDisabled' ); //"Account has been deactivated.";
					echo Core::ajaxResponse( $obj, false );
				} else {
					//if user is active then log in
					$obj['msg'] = $lang->o( 'ajaxLogin' ); //'Successfully logged in.';
					//set redirect url
					$obj['redirect'] = 'admin';
					//update date and ip
					$query = "UPDATE users SET lastIP = '${_SERVER['REMOTE_ADDR']}', latestDate = CURRENT_TIMESTAMP WHERE id = ${row['id']}";
					if( $this->db->query( $query ) === true ) {
						//todo log this error
					}
					//echo the reponse to user
					echo Core::ajaxResponse( $obj );
					//update session to reflect login status
					$_SESSION['session'] = array();
					$_SESSION['session']['username'] = $row['username'];
					$_SESSION['session']['id'] = $row['id'];
					$_SESSION['session']['expires'] = time() + ( 60 * 10 ); //session set for 10 minutes
					$_SESSION['session']['started'] = time();
					$this->deleteRecord( 'loginBan', 'ip = "' .Core::getIp() . '"' );
					unset( $_SESSION['loginAttempts'] );
					//load audit module and report the login
					$this->loadModule( 'audit' );
					$this->audit->newEvent( "Logged into administration" );
				}
			} else{
				//you entered a wrong password
				if( isset( $_SESSION['loginAttempts'] ) ){
					$_SESSION['loginAttempts']++;
				} else{
					$_SESSION['loginAttempts'] = 0;
				}
				$this->upsertRecord( 'loginBan', 'ip = "' . Core::getIp() . '"', array(
					'attempt' => $_SESSION['loginAttempts'],
					'expires' => max( time(), time() + ( 120 * $_SESSION['loginAttempts'] ) ), //2 minutes times attempts
					'ip' => Core::getIp(),
					'created' => time()
				) );
				$obj['error'] = $lang->o( 'ajaxLoginIncorrect' ); //"Your username or email is incorrect";
				echo Core::ajaxResponse( $obj, false );
			}
			$result->close();
		}

		/**
		 * logout the user
		 */
		public function logout(){
			if ( isset( $_SESSION['session'] ) ) {
				unset( $_SESSION['session'] );
			}
		}

		/**
		 * check if the session expired if so log them out if not expired update the expire time
		 */
		public function checkExpiredSession( $refresh = true ){
			if ( isset( $_SESSION['session'] ) ) {
				//if the expire time is less than the current time log the user out
				if( $_SESSION['session']['expires'] <= time() ){
					$this->logout();
				} else {
					//if not expired then refresh the expire time to current time
					if( $refresh ) {
						$_SESSION['session']['expires'] = time() + ( 60 * 10 ); //session set for 10 minutes
					}
				}

			}
		}

		/**
		 * check if they have an active session with the server
		 * @return bool true if has ative session false otherwise
		 */
		public function isLoggedIn(){
			if( isset( $_SESSION['session'] ) && isset( $_SESSION['session']['username'] ) ) {
				if ( $_SESSION['session']['expires'] > time() ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * check if user is a global admin used to create users and manage them
		 */
		public function isAdmin(){
			if( $this->isLoggedIn() ){
				$query = "SELECT id, isAdmin FROM users WHERE username = '" . $_SESSION['session']['username'] . "'";
				if( !$result = $this->db->query($query) ){
					return false;
				}
				$row = null;
				//if there was a user matching
				if( $result->num_rows == 1 ) {
					$row = $result->fetch_assoc();
					if( $row['isAdmin'] == 1 ){
						return true;
					}
				}
				$result->close();
			}

			return false;
		}

		private function hashPassword( $inPass, $inUser ){
			$password[0] = hash( 'sha512', substr( $inPass, 0, 64 ) );
			$password[1] = hash( 'sha512', substr( $inPass, 64 ) );
			$passwordHash = hash( 'sha512', $password[0] . $password[1] . $inUser );
			return $passwordHash;
		}
	}