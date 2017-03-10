<?php

abstract class WPML_Templates_Factory {

	private $custom_filters;
	private $custom_functions;

	/* @var WPML_WP_API $wp_api */
	private $wp_api;

	/**
	 * WPML_Templates_Factory constructor.
	 *
	 * @param array $custom_functions
	 * @param array $custom_filters
	 * @param WPML_WP_API $wp_api
	 */
	public function __construct( array $custom_functions = array(), array $custom_filters = array(), $wp_api = null ) {
		$this->init_template_base_dir();
		$this->custom_functions = $custom_functions;
		$this->custom_filters   = $custom_filters;

		if ( $wp_api ) {
			$this->wp_api = $wp_api;
		}
	}

	abstract protected function init_template_base_dir();
}
