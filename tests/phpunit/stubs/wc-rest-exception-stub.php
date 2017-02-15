<?php
	class WC_REST_Exception extends Exception {
		public function __construct( $error_code, $error_message, $http_status_code ) {
			$this->error_code = $error_code;

			parent::__construct( $error_message, $http_status_code );
		}

}