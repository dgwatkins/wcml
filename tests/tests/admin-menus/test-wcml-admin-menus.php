<?php

/**
 * Class WCML_Admin_Menus
 */
class Test_WCML_Admin_Menus extends WCML_UnitTestCase {

	function setUp() {

		parent::setUp();

	}

	public function test_check_user_admin_access(){
		global $wpdb;

		$current_user_id = get_current_user_id();

		$user_factory = new WP_UnitTest_Factory_For_User();
		$user = $user_factory->create_and_get();

		$prevent_access = WCML_Admin_Menus::check_user_admin_access( true );
		$this->assertTrue( $prevent_access );

		$user->add_cap( WPML_Translator_Role::CAPABILITY );
		$language_pair_records = new WPML_Language_Pair_Records( $wpdb, new WPML_Language_Records( $wpdb ) );
		$language_pair_records->store( $user->ID, array( 'en' => array( 'es' ) ) );
		wp_set_current_user( $user->ID );

		$prevent_access = WCML_Admin_Menus::check_user_admin_access( true );
		$this->assertFalse( $prevent_access );

		$user_factory = new WP_UnitTest_Factory_For_User();
		$user = $user_factory->create_and_get();

		$this->make_current_user_wcml_manager();
		$prevent_access = WCML_Admin_Menus::check_user_admin_access( true );
		$this->assertFalse( $prevent_access );
		wp_delete_user( $user->ID );

		//switch user back
		wp_set_current_user( $current_user_id );

	}

}
