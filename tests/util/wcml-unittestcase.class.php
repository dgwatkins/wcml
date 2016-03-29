<?php

class WCML_UnitTestCase extends WPML_UnitTestCase {

	/**
	 * @uses \WPML_UnitTestCase::setUp to setup a WPML installation and remove query filters added by WP core unit tests
	 *                                 that interfere with WPML's unit tests
	 */
	function setUp() {
		parent::setUp();
		require_once WC_PATH . '/woocommerce.php';

		$wc_unit = new WC_Unit_Test_Case();
		$wc_unit->setUp();
	}

	function get_wcml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
	}

}
