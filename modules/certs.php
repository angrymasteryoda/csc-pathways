<?php

	/**
	 * Created by IntelliJ IDEA.
	 * User: Michael Risher
	 * Date: 6/6/2017
	 * Time: 12:22
	 */
	class certs extends Main {
		/**
		 * get a simple-ish listing of the certificate
		 * @param string $order any custom sort you want to run with the query
		 * @return array
		 */
		public function listing( $order = 'id' ) {
			$query = "SELECT * FROM certificateList ORDER BY $order";//remove limit for a time LIMIT $page,50

			if ( !$result = $this->db->query( $query ) ) {
				echo( 'There was an error running the query [' . $this->db->error . ']' );
			}

			$return = array();
			while ( $row = $result->fetch_assoc() ) {
				$a = array(
					'id' => $row['id'],
					'code' => $row['code'],
					'description' => $row['description'],
					'category' => $row['category'],
					'hasAs' => $row['hasAs'],
					'hasCe' => $row['hasCe'],
					'units' => $row['units'],
					'sort' => $row['sort'],
				);
				array_push( $return, $a );
			}
			if ( IS_AJAX ) {
				echo Core::ajaxResponse( $return );
			}
			return $return;
		}

		/**
		 * edit cert action from the admin page
		 * only allowed if the user is admin
		 * @param $id
		 */
		public function edit( $id ) {
			$this->loadModule( 'users' );
			if ( $this->users->isLoggedIn() ) {
				Core::queueStyle( 'assets/css/reset.css' );
				Core::queueStyle( 'assets/css/ui.css' );
				//put the data onscreen
				$data = $this->get( $id );
				$data['categories'] = $this->listCategories();

				include( CORE_PATH . 'pages/certEdit.php' );

			}
		}


		/**
		 * get a specific cert from an id
		 * echos JSON to the user
		 * returns an array if forceReturn is true
		 * @param $id int id which is not the three digit code
		 * @param bool|false $forceReturn forces a array return
		 * @return array|null
		 */
		public function get( $id, $forceReturn = false ) {
			$this->loadModule( 'users' );
			$query = <<<EOD
SELECT
certificateList.id,
certificateList.code,
certificateList.hasAs,
certificateList.hasCe,
certificateList.units,
certificateList.category,
certificateList.description AS title,
certificateList.sort,
certificateData.description,
certificateData.elo,
certificateData.schedule
FROM
certificateList
INNER JOIN enumCategories ON certificateList.category = enumCategories.id
INNER JOIN certificateData ON certificateList.id = certificateData.cert
WHERE certificateList.id = ${id}
EOD;
			if ( !$result = $this->db->query( $query ) ) {
				echo Core::ajaxResponse( array( 'error' => "An error occurred please try again" ), false );
				return null;
			}
			$row = $result->fetch_assoc();
			$return = $row;

			if ( IS_AJAX && !$forceReturn ) {
				echo Core::ajaxResponse( $return );
			} else {
				return $return;
			}
		}

		/**
		 * get a listing of all the categories
		 * only allowed if the user is admin
		 * @return array
		 */
		public function listCategories(){
			$this->loadModule( 'users' );
			if ( $this->users->isLoggedIn() ) {
				$query = "SELECT * FROM enumCategories";//remove limit for a time LIMIT $page,50

				if ( !$result = $this->db->query( $query ) ) {
					echo( 'There was an error running the query [' . $this->db->error . ']' );
				}

				$return = array();
				while ( $row = $result->fetch_assoc() ) {
					$a = array(
						'id' => $row['id'],
						'category' => $row['category'],
					);
					array_push( $return, $a );
				}
				if ( IS_AJAX ) {
					echo Core::ajaxResponse( $return );
				}
				return $return;
			}
		}

		/**
		 * save from the admin page
		 * only allowed if the user is admin
		 * @param $id
		 */
		public function save( $id ) {
			$this->loadModule( 'users' );
			$obj = array();
			if( $this->users->isLoggedIn() ){
				$this->loadModule( 'audit' );
				$id = core::sanitize( $id );
				$_POST['title'] = core::sanitize( $_POST['title'] ); //certlist description
				$_POST['code'] = core::sanitize( $_POST['code'] );
				$_POST['units'] = core::sanitize( $_POST['units'] );
				$_POST['category'] = core::sanitize( $_POST['category'] );
				$_POST['description'] = core::sanitize( $_POST['description'], true );
				$_POST['elo'] = core::sanitize( $_POST['elo'], true );
				$_POST['schedule'] = core::sanitize( $_POST['schedule'], true );
				$_POST['sort'] = core::sanitize( $_POST['sort'] );
				$hasCe = isset( $_POST['hasCe'] ) ? 1 : 0; //js returns something like hasCe=on if its on else its not set
				$hasAs = isset( $_POST['hasAs'] ) ? 1 : 0; //js returns something like hasCe=on if its on else its not set


				//separate the two tables data
				$query = <<<EOD
INSERT INTO certificateList (id, code, hasAs, hasCe, category, units, description, sort)
VALUES (?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE
code = VALUES( code ),
hasAs = VALUES( hasAs ),
hasCe = VALUES( hasCe ),
category = VALUES( category ),
units = VALUES( units ),
description = VALUES( description ),
sort = VALUES( sort )
EOD;
				$queryData = <<<EOD
INSERT INTO certificateData (cert, description, elo, schedule )
VALUES (?,?,?,?)
ON DUPLICATE KEY UPDATE
description = VALUES( description ),
elo = VALUES( elo ),
schedule = VALUES( schedule )
EOD;
				//cert list update statement
				$statement = $this->db->prepare($query);
				$statement->bind_param( 'isiiiisi', $id, $_POST['code'], $hasAs, $hasCe, $_POST['category'], $_POST['units'], $_POST['title'], $_POST['sort'] );
				//cert data update statement
				$statementData = $this->db->prepare($queryData);
				$statementData->bind_param( 'isss', $id, $_POST['description'], $_POST['elo'], $_POST['schedule'] );
				if( $statement->execute() && $statementData->execute() ){
					$obj['msg'] = "Saved successfully.";
					$this->audit->newEvent( "Updated cert: " . $_POST['title'] );
					echo Core::ajaxResponse( $obj );
				} else{
					$obj['error'] = $statement->error;
					echo Core::ajaxResponse( $obj, false );
				}
			} else{
				$obj['error'] = 'Session expired.<br>Please log in again';
				echo Core::ajaxResponse( $obj, false );
			}
		}

		/**
		 * create from the admin page
		 * only allowed if the user is admin
		 */
		public function create(){
			Core::queueStyle( 'assets/css/reset.css' );
			Core::queueStyle( 'assets/css/ui.css' );
			//get the next id
			$query = "SHOW TABLE STATUS LIKE 'certificateList'";
			if( !$result = $this->db->query( $query ) ) {
				die('There was an error running the query [' . $this->db->error . ']');
			}
			$row = null;
			if( $result->num_rows ) {
				$row = $result->fetch_assoc();
				$data = array(
					'id' => $row['Auto_increment'],
					'title' => '',
					'code' => '',
					'hasAs' => 0,
					'hasCe' => 0,
					'category' => 0,
					'description' => '',
					'elo' => '',
					'schedule' => '',
					'sort' => 0,
					'categories' => $this->listCategories()
				);
			}
			$result->close();
			include( CORE_PATH . 'pages/certEdit.php' );
		}

		/**
		 * delete a cert from the admin page
		 * only allowed if the user is admin
		 * @param $id
		 */
		public function delete( $id ){
			$this->loadModule( 'users' );
			$this->loadModule( 'audit' );
			$obj = array();
			$_POST = Core::sanitize( $_POST, true );
			if( $this->users->isLoggedIn() ) {
				$query = "SELECT description FROM certificateList WHERE id = '${_POST['id']}'";
				$event = '';
				if ( !$result = $this->db->query( $query ) ) {
					$event = $_POST['id'];
				}
				$row = $result->fetch_assoc();
				$event = $row['description'];

				$statement = $this->db->prepare("DELETE FROM certificateList WHERE id=?");
				$statement->bind_param( "s", $_POST['id']);
				$statementData = $this->db->prepare("DELETE FROM certificateData WHERE cert=?");
				$statementData->bind_param( "s", $_POST['id']);
				if( $statement->execute() && $statementData->execute() ){
					$obj['msg'] = "Deleted successfully.";
					$this->audit->newEvent( "Deleted certificate: " . $event );
					echo Core::ajaxResponse( $obj );
				} else{
					$obj['error'] = $statement->error;
					echo Core::ajaxResponse( $obj, false );
				}
				$result->close();
				$statement->close();
				$statementData->close();
			} else{
				$obj['error'] = "Session expired.<br>Please log in again";
				echo Core::ajaxResponse( $obj, false );
			}
		}

		/**
		 * parse the class link from the html editor into a link for user
		 * @param $string
		 * @return mixed
		 */
		public function replaceClassLink( $string ){
			return preg_replace( '/\[class\s*id=[\'|\"](.+?)[\'|\"]\s*text=[\'|\"](.+?)[\'|\"]\s*\/\]/',
				"<a class='fakeLink' data-to='class' data-code='$1'>$2</a>",
				$string
				);//regex to match the link string
		}
	}
