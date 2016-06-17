<?php

/**
 * Class Test_WCML_Screen_Options
 */
class Test_WCML_Screen_Options extends WCML_UnitTestCase {

	/**
	 * Test \WCML_Screen_Options::init()
	 * @test
	 */
	public function check_setup_hooks() {
		$subject = $this->get_test_subject( $this->get_sitepress_mock() );
		$subject->init();

		$this->assertEquals( 10, has_filter( 'default_hidden_columns', array( $subject, 'filter_screen_options' ) ) );
		$this->assertEquals( 10, has_action( 'admin_init', array( $subject, 'save_translation_controls' ) ) );
		$this->assertEquals( 10, has_action( 'admin_notices', array( $subject, 'product_page_admin_notices' ) ) );
	}

	/**
	 * Test \WCML_Screen_Options::filter_screen_options()
	 * @test
	 */
	public function filter_screen_options() {
		$subject = $this->get_test_subject( $this->get_sitepress_mock() );
		$hidden = array();
		$screen = new stdClass();
		$screen->id = 'edit-product';

		$this->assertTrue( in_array( 'icl_translations', $subject->filter_screen_options( $hidden, $screen ) ) );

		$screen->id = 'edit-cpt';
		$this->assertFalse( in_array( 'icl_translations', $subject->filter_screen_options( $hidden, $screen ) ) );
	}

	/**
	 * Test \WCML_Screen_Options::save_translation_controls()
	 * @test
	 */
	public function save_translation_controls() {
		$user_id = $this->get_current_user_id();
		$_GET['translation_controls'] = 1;
		$_GET['nonce'] = wp_create_nonce( 'enable_translation_controls' );

		$subject = $this->get_test_subject( $this->get_test_sitepress_mock() );
		$subject->save_translation_controls();
		$hidden_columns = get_user_meta( $user_id, 'manageedit-productcolumnshidden', true );
		$this->assertEquals( 0, count( $hidden_columns ) );
		delete_user_meta( $user_id, 'manageedit-productcolumnshidden' );

		$_GET['translation_controls'] = 0;
		$subject = $this->get_test_subject( $this->get_test_sitepress_mock() );
		$subject->save_translation_controls();
		$hidden_columns = get_user_meta( $user_id, 'manageedit-productcolumnshidden', true );
		$this->assertTrue( in_array( 'icl_translations', $hidden_columns ) );
		delete_user_meta( $user_id, 'manageedit-productcolumnshidden' );
	}

	/**
	 * Test \WCML_Screen_Options::dismiss_notice_permanently()
	 * @test
	 */
	public function saving_dismiss_notice() {
		$user_id = $this->get_current_user_id();
		$subject = $this->get_test_subject( $this->get_sitepress_mock() );
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
		$subject->dismiss_notice_permanently();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );


		$_POST['nonce'] = wp_create_nonce( 'products-screen-option-action' );
		$subject->dismiss_notice_permanently();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );

		$_POST['dismiss_notice'] = 'enabled';
		$subject->dismiss_notice_permanently();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 1, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );

		$_POST['dismiss_notice'] = 'disabled';
		$subject->dismiss_notice_permanently();
		$this->assertEquals( 1, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 1, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );
		delete_user_meta( $user_id, 'screen-option-disabled-notice-dismissed' );
		delete_user_meta( $user_id, 'screen-option-enabled-notice-dismissed' );
	}

	/**
	 * Test \WCML_Screen_Options::dismiss_notice_on_screen_option_change()
	 * @test
	 */
	public function check_dismiss_notice_on_screen_option_change() {
		$user_id = $this->get_current_user_id();
		$subject = $this->get_test_subject( $this->get_sitepress_mock() );
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$subject->dismiss_notice_on_screen_option_change();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );


		$_REQUEST['screenoptionnonce'] = wp_create_nonce( 'screen-options-nonce' );
		$subject->dismiss_notice_on_screen_option_change();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );

		$_POST['page'] = 'edit-product';
		$subject->dismiss_notice_on_screen_option_change();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );

		$_POST['hidden'] = 'icl-translations';
		$subject->dismiss_notice_on_screen_option_change();
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 0, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );

		$_POST['hidden'] = '';
		$subject->dismiss_notice_on_screen_option_change();
		$this->assertEquals( 1, (int) get_user_meta( $user_id, 'screen-option-disabled-notice-dismissed', true ) );
		$this->assertEquals( 1, (int) get_user_meta( $user_id, 'screen-option-enabled-notice-dismissed', true ) );

		delete_user_meta( $user_id, 'screen-option-disabled-notice-dismissed' );
		delete_user_meta( $user_id, 'screen-option-enabled-notice-dismissed' );
	}

	/**
	 * Test \WCML_Screen_Options::has_products()
	 *
	 * @test
	 */
	public function check_has_products() {
		$subject = $this->get_test_subject( $this->get_sitepress_mock() );
		$this->assertFalse( $subject->has_products() );
		wpml_test_insert_post( 'en', 'product', null );
		$this->assertTrue( $subject->has_products() );
	}

	private function get_test_sitepress_mock() {
		$wp_api_mock = $this->get_wp_api_mock();
		$wp_api_mock
			->expects( $this->once() )->method( 'wp_safe_redirect' )
			->with( $this->isType( 'string' ), $this->equalTo( 301 ) );
		$sitepress = $this->get_sitepress_mock( $wp_api_mock );

		return $sitepress;
	}

	private function get_current_user_id() {
		global $current_user;

		if ( ! isset( $current_user ) || (bool) get_current_user_id() === false ) {
			$user_factory = new WP_UnitTest_Factory_For_User();
			$current_user = $user_factory->create_and_get();
		}
		return get_current_user_id();
	}

	private function get_test_subject( $sitepress ) {
		return new WCML_Products_Screen_Options( $sitepress );
	}
}
