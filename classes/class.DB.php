<?php

	/**
	 * Created by IntelliJ IDEA.
	 * User: Michael Risher
	 * Date: 5/27/2017
	 * Time: 16:01
	 */
	class DB {
		public function __construct(){}

		/**
		 * @deprecated
		 * @return mysqli
		 */
		public function createConnection(){
			$connection = new mysqli( DB_IP, DB_USER, DB_PASS, DB_DB );

			if($connection->connect_errno > 0){
				if( MODE == 'live' ){
					die('Unable to connect to database [' . $connection->connect_error . ']');
				} else{
					die( 'An error occurred. Please contact the administrator' );
				}
			}
			return $connection;
		}
	}