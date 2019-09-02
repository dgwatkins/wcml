<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WCML_Admin_Menus
 * @group wcml-2905
 */
class Test_WCML_Admin_Menus extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_sets_up_menus() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = false;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_adds_wpml_menu_page_filter_when_dependencies_are_ok() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = true;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_manage_woocommerce_multilingual' )
		        ->andReturn( false );
		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_operate_woocommerce_multilingual' )
		        ->andReturn( true );
		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'translate' )->andReturn( false );

		\WP_Mock::expectFilterAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_does_NOT_add_wpml_menu_page_filter_when_dependencies_are_not_ok() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = false;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'wpml_manage_woocommerce_multilingual' );
		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'wpml_operate_woocommerce_multilingual' );
		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'translate' );

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_does_NOT_add_wpml_menu_page_filter_if_user_can_wpml_manage_woocommerce_multilingual() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = true;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_manage_woocommerce_multilingual' )
		        ->andReturn( true );
		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'wpml_operate_woocommerce_multilingual' );
		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'translate' );

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_does_NOT_add_wpml_menu_page_filter_if_user_cannot_wpml_operate_woocommerce_multilingual() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = true;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_manage_woocommerce_multilingual' )
		        ->andReturn( false );
		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_operate_woocommerce_multilingual' )
		        ->andReturn( false );
		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'translate' );

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_does_NOT_add_wpml_menu_page_filter_if_user_can_translate() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = true;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_manage_woocommerce_multilingual' )
		        ->andReturn( false );
		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_operate_woocommerce_multilingual' )
		        ->andReturn( false );
		\WP_Mock::userFunction( 'current_user_can' )->never()->with( 'translate' );

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_calls_remove_wpml_admin_language_switcher() {
		$wpml_menu_page_filter_call_count = 0;

		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = false;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', true );
		FunctionMocker::replace(
			'WCML_Admin_Menus::remove_wpml_admin_language_switcher',
			function () use ( & $wpml_menu_page_filter_call_count ) {
				$wpml_menu_page_filter_call_count ++;
			}
		);

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( false );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );

		$this->assertSame( 1, $wpml_menu_page_filter_call_count );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_adds_admin_filters() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = true;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->once()->with( 'wpml_manage_woocommerce_multilingual' )
		        ->andReturn( true );
		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( true );

		\WP_Mock::expectActionAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_does_NOT_add_admin_filters_if_no_sitepress() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = false;

		$sitepress = null;
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( true );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_sets_up_menus_and_does_NOT_add_admin_filters_when_dependencies_are_not_ok() {
		$woocommerce_wpml                      = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->dependencies_are_ok = false;

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );

		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();
		\WP_Mock::userFunction( 'current_user_can' )->never();

		\WP_Mock::expectFilterNotAdded( 'wpml_menu_page', [ 'WCML_Admin_Menus', 'wpml_menu_page' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ 'WCML_Admin_Menus', 'register_menus' ], 80 );

		FunctionMocker::replace( 'WCML_Admin_Menus::is_page_without_admin_language_switcher', false );

		\WP_Mock::userFunction( 'is_admin' )->once()->with()->andReturn( true );

		\WP_Mock::expectActionNotAdded( 'admin_footer', [ 'WCML_Admin_Menus', 'documentation_links' ] );
		\WP_Mock::expectActionNotAdded( 'admin_head', [ 'WCML_Admin_Menus', 'hide_multilingual_content_setup_box' ] );
		\WP_Mock::expectActionNotAdded( 'admin_init', [ 'WCML_Admin_Menus', 'restrict_admin_with_redirect' ] );

		\WP_Mock::expectFilterAdded(
			'woocommerce_prevent_admin_access',
			[
				'WCML_Admin_Menus',
				'check_user_admin_access'
			]
		);
		\WP_Mock::expectActionAdded( 'admin_head', [ 'WCML_Admin_Menus', 'add_menu_warning' ] );

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	public function it_filters_wpml_menu_page() {
		$menu = [
			'capability' => 'translate',
			'menu_slug' => WPML_TM_FOLDER . '/menu/translations-queue.php',
		];

		$expected = [
			'capability' => 'wpml_operate_woocommerce_multilingual',
			'menu_slug' => WPML_TM_FOLDER . '/menu/translations-queue.php',
		];

		$this->assertSame( $expected, WCML_Admin_Menus::wpml_menu_page( $menu ) );
	}

	/**
	 * @test
	 */
	public function it_does_NOT_filter_wpml_menu_page() {
		$menu = [
			'capability' => 'translate',
			'menu_slug' => 'some slug',
		];

		$this->assertSame( $menu, WCML_Admin_Menus::wpml_menu_page( $menu ) );
	}
}
