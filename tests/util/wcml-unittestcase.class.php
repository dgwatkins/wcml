<?php

class WCML_UnitTestCase extends WPML_UnitTestCase {

	public $sitepress;
	public $woocommerce_wpml;

	/**
	 * @uses \WPML_UnitTestCase::setUp to setup a WPML installation and remove query filters added by WP core unit tests
	 *                                 that interfere with WPML's unit tests
	 */
	function setUp() {
		parent::setUp();

		global $woocommerce_wpml, $sitepress;

		$this->sitepress 		=& $sitepress;
		$this->woocommerce_wpml	=& $woocommerce_wpml;
		$this->wcml_helper = new WCML_Helper();

		require_once WC_PATH . '/woocommerce.php';

		$wc_unit = new WC_Unit_Test_Case();
		$wc_unit->setUp();


	}

	protected function make_current_user_wcml_admin( ) {
		global $current_user;

		if ( ! isset( $current_user ) || (bool) get_current_user_id() === false ) {
			$user_factory = new WP_UnitTest_Factory_For_User();
			$current_user = $user_factory->create_and_get();
		}

		$current_user->add_cap( 'manage_options' );
		$current_user->add_cap( 'activate_plugins' );
		$current_user->add_cap( 'wpml_operate_woocommerce_multilingual' );
		$current_user->get_role_caps();
		$current_user->update_user_level_from_caps();

		return $current_user->ID;
	}

}
