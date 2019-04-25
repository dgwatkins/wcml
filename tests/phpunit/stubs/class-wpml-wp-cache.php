<?php

class WPML_WP_Cache {

	public function __construct( $group = '' ) {}

	public function get( $key, &$found ) {}

	public function set( $key, $value, $expire = 0 ) {}

	/**
	 * Get specific number for group.
	 * Which later can be incremented to flush cache for group.
	 *
	 * @return int
	 */
	public function get_current_key( $key ) {}

	/**
	 * Increment the number stored with group name as key.
	 */
	public function flush_group_cache() {}
}
