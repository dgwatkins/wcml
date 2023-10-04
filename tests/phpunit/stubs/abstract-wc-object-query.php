<?php
abstract class WC_Object_Query {

	protected $query_vars = array();

	public function __construct( $args = array() ) {}

	public function get_query_vars() {}

	public function get( $query_var, $default = '' ) {}

	public function set( $query_var, $value ) {}

	protected function get_default_query_vars() {}

}
