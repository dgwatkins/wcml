<?php

/**
 * Class WCML_Admin_Menus
 */
class Test_WCML_Admin_Menus extends WCML_UnitTestCase {

	function setUp() {

		parent::setUp();

	}

	public function test_check_user_admin_access(){

		$current_user_id = get_current_user_id();

		$user_factory = new WP_UnitTest_Factory_For_User();
		$user = $user_factory->create_and_get();

		$prevent_access = WCML_Admin_Menus::check_user_admin_access( true );
		$this->assertTrue( $prevent_access );

		$tm = new TranslationManagement();

		$language_pairs = array( 'en' => array( 'es' => 1 ) );
		$tm->add_translator( $user->ID, $language_pairs );
		wp_set_current_user( $user->ID );

		$prevent_access = WCML_Admin_Menus::check_user_admin_access( true );
		$this->assertFalse( $prevent_access );

		//switch user back
		wp_set_current_user( $current_user_id );

	}

}
