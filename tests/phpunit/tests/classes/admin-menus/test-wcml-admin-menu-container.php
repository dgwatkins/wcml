<?php

/**
 * @group refactoring
 */
class Test_WCML_Admin_Menu_Container extends OTGS_TestCase {
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var WCML_Admin_Menu_Container */
	private $subject;

	public function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->createMock( 'woocommerce_wpml' );

		$GLOBALS['wpdb'] = $this->getMockBuilder( 'wpdb' )->getMock();
		$GLOBALS['sitepress'] = $this->getMockBuilder( 'SitePress' )->getMock();
		$GLOBALS['wp_api'] = $this->getMockBuilder( 'WPML_WP_API' )->getMock();
		$GLOBALS['WPML_Translation_Management'] = $this->getMockBuilder( 'WPML_Translation_Management' )->getMock();
		$GLOBALS['menu'] = array();
		$GLOBALS['submenu'] = array();

		\Mockery::namedMock( 'WCML_Menus_Wrap', 'WCML_Admin_Menu_Display' );
		\Mockery::namedMock( 'WCML_Plugins_Wrap', 'WCML_Admin_Menu_Display' );

		$this->subject = new WCML_Admin_Menu_Container( $this->woocommerce_wpml );
	}

	public function tearDown() {
		parent::tearDown();

		unset( $GLOBALS['wpdb'], $GLOBALS['sitepress'], $GLOBALS['wp_api'], $GLOBALS['WPML_Translation_Management'] );
	}

	/**
	 * @test
	 */
	public function it_gets_admin_menus() {
		$this->assertInstanceOf( 'WCML_Admin_Menu', $this->subject->get_admin_menus() );
	}

	/**
	 * @test
	 */
	public function it_get_main_menu() {
		$this->assertInstanceOf( 'WCML_Admin_Menu_Main', $this->subject->get_main_menu() );
	}
}
