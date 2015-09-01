<?php

class WCML_UnitTestCase extends WPML_UnitTestCase {

	/**
	 * @uses \WPML_UnitTestCase::setUp to setup a WPML installation and remove query filters added by WP core unit tests
	 *                                 that interfere with WPML's unit tests
	 */
	function setUp() {
		parent::setUp();
		require_once WC_PATH . '/woocommerce.php';
	}
}
