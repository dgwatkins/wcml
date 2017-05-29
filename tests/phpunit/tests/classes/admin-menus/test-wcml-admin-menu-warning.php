<?php

/**
 * @group refactoring
 * @runTestsInSeparateProcesses
 * @preserveGlobalState
 */
class Test_WCML_Admin_Menu_Warning extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction( '__' );
	}

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$menu = array();
		$submenu = array();

		$subject = new WCML_Admin_Menu_Warning( $this->createMock( 'woocommerce_wpml' ), $menu, $submenu );
		\WP_Mock::expectActionAdded( 'admin_head', array( $subject, 'add_menu_warning' ), 10, 0 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_warning_icons() {
		$this->getMockBuilder( 'WooCommerce' )->getMock();

		$woocommerce_wpml           = $this->createMock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings = array( 'set_up_wizard_run' => array() );

		$menu = $this->prepare_main_menu();
		$submenu = $this->prepare_sub_menu();

		$subject = new WCML_Admin_Menu_Warning( $this->createMock( 'woocommerce_wpml' ), $menu, $submenu );
		$subject->add_menu_warning();

		$this->assertStringEndsWith( WCML_Admin_Menu_Warning::ICON, $menu['Woocommerce'][0] );
		$this->assertStringEndsWith( WCML_Admin_Menu_Warning::ICON, $submenu['woocommerce']['WCML'][0] );
	}

	private function prepare_main_menu() {
		return array(
			'Key1'        => array( 'Name 1' ),
			'Key2'        => array( 'Name 2' ),
			'Woocommerce' => array( WCML_Admin_Menu_Warning::MENU_LABEL ),
			'Key3'        => array( 'Name 3' ),
		);
	}

	private function prepare_sub_menu() {
		return array(
			'woocommerce' => array(
				'SKey1'        => array( 'SName 1' ),
				'SKey2'        => array( 'SName 2' ),
				'SKey3'        => array( 'SName 3' ),
				'WCML' => array( WCML_Admin_Menu_Warning::SUBMENU_LABEL ),
			),
			'some other'  => array(
				'SKey4' => array( 'SName 4' ),
			),
		);
	}
}
