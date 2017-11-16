<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Michael Risher
	 * Date: 11/7/2017
	 * Time: 13:38
	 */
class pages extends Main{
	private $moduleName = 'pages';

	/**
	 * get a simple listing of classes only id and title are returned
	 * only allowed if the user is admin
	 * @return array
	 */
	public function listing( $page = 1){
		$this->loadModules( 'roles discipline' );
		$fullRoles = $this->roles->getAllForUser( Core::getSessionId() );
		$userDisciplines = $this->discipline->getIdsForUser( Core::getSessionId() );
		$limit = 25;
		$page--;//to make good looking page numbers for users
		$offset = $page * $limit;
		$search = '';
		if( isset( $_POST ) || isset( $_GET ) ){
			$_POST = Core::sanitize( $_POST );
			if( isset( $_POST['search'] ) ){
				$search = $_POST['search'];
			}
			if( isset( $_GET['q'] ) ){
				$search = Core::sanitize( $_GET['q'] );
			}
		}

		if( isset( $_POST['all'] ) ){
			$query = "SELECT * FROM pages ORDER BY name";
		}
		else if( empty( $search ) ){
			$query = "SELECT * FROM pages ORDER BY name LIMIT $offset,$limit";//remove limit for a time LIMIT $page,50
		} else {
			$query = "SELECT * FROM pages WHERE name LIKE '%$search%'ORDER BY name LIMIT $offset,$limit";
		}

		if ( !$result = $this->db->query( $query ) ) {
			echo( 'There was an error running the query [' . $this->db->error . ']' );
			return null;
		}

		$return = array();
		while ( $row = $result->fetch_assoc() ) {
			if ( $this->roles->haveAccess( 'dataManage', Core::getSessionId(), $row['discipline'], $fullRoles, $userDisciplines ) ) {
				$canEdit = true;
				$canDelete = true;
				$a = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'edit' => $canEdit,
					'delete' => $canDelete
				);
				array_push( $return, $a );
			}
		}

		//get count of data
		if( empty( $search ) ) {
			$query = "SELECT COUNT( id ) AS items FROM pages";
		} else {
			$query = "SELECT COUNT( id ) AS items FROM pages WHERE name LIKE '%$search%'";
		}
		$result->close();
		if( !$result = $this->db->query( $query ) ) {
			echo( 'There was an error running the query [' . $this->db->error . ']' );
			return null;
		}

		if( $result->num_rows == 1 ){
			$row = $result->fetch_assoc();
			$count = $row['items'];
		}
		$result->close();
		$return = array(
			'listing' => $return,
			'count' => intval( $count ),
			'limit' => $limit,
			'currentPage' => (int)++$page );
		if ( IS_AJAX ) {
			echo Core::ajaxResponse( $return );
		}
		return $return;
	}

	/**
	 * get the data from the database for a class
	 * only allowed if the user is admin
	 * echos json if ajaxed
	 * returns an array if forced to return
	 * @param string|int $id id of the page or the path of the page
	 * @param bool|false $forceReturn force a return if the get is not called through ajax
	 * @return array|void array if forceReturn is true echos echos json otherwise
	 */
	public function get( $id, $forceReturn = false ){
		if( gettype( $id ) == 'string' ){
			$query = "SELECT * FROM pages WHERE path=?";
			$state = $this->db->prepare( $query );
			$state->bind_param( 's', $id );
		} else{
			$query = "SELECT * FROM pages WHERE id=?";
			$state = $this->db->prepare( $query );
			$state->bind_param( 'i', $id );
		}

		if( !$state->execute() ){
			error_log( 'pages.php get: ' . $state->error );
			if( !$forceReturn )
				echo Core::ajaxResponse( array( 'error' => "An error occurred please try again" ), false );
			return null;
		} else {
			$result = $state->get_result();
			if( $result->num_rows > 0 ){
				$row = $result->fetch_assoc();
				return $row;
			} else {
				if( !$forceReturn )
					echo Core::ajaxResponse( array( 'error' => "Page not found" ), false );
				return null;
			}
		}
	}

	/**
	 * edit an item from the database returns html
	 * @param int $id id for the item
	 */
	public function edit( $id = -1 ){

	}

	/**
	 * save an item from the database
	 * @param int $id id for the item
	 */
	public function save( $id ){

	}

	/**
	 * delete an item from the database
	 * @param int $id id for the item
	 */
	public function delete( $id ){

	}

	/**
	 * displays the page with the path provided. checks the files first then checks the database.
	 * if no page was found it errors to a 404
	 * @param $path
	 */
	public function display( $path ){
//		Core::debug( $path );
		if( is_array( $path ) ){
			$data['params'] = $path;
			$path = $path[0];
		} else{
			$data['params'] = $path;
		}

		//if the page exists load it with the params if there was any
		if ( file_exists( CORE_PATH . 'pages/' . $path .'.php' ) ) {
			Core::queueStyle( 'assets/css/reset.css' );
			Core::queueStyle( 'assets/css/ui.css' );
			include( CORE_PATH . 'pages/' . $path . '.php' );
		} else {
			//check if page exists in the database
			$page = $this->get( $path, true );
			$data['page'] = $page;
			if( $page != null ){
				Core::queueStyle( 'assets/css/reset.css' );
				//if there is a user included style use that one instead of the default
				if( $page['stylesheet'] != null ){
					$stylesheet = CORE_PATH . 'pages/userPages/' . $page['stylesheet'];
					if( file_exists( $stylesheet ) ){
						Core::queueStyle( $stylesheet );
					}
				} else {
					Core::queueStyle( 'assets/css/ui.css' );
				}

				//include header file if there was one
				if( $page['headerTemplate'] != null ){
					$headerTemp = CORE_PATH . 'pages/userPages/' . $page['headerTemplate'];
					if( file_exists( $headerTemp ) ){
						include( $headerTemp );
					}
				}
				//include body template if there is one
				if( $page['bodyTemplate'] != null ){
					$bodyTemp = CORE_PATH . 'pages/userPages/' . $page['bodyTemplate'];
					if( file_exists( $bodyTemp ) ){
						include( $bodyTemp );
					}
				}
			} else {
				//the page doesn't exist so 404 the user
				Core::errorPage( 404 );
			}
		}
	}
}