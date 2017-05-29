<?php

/**
 * @group refactoring
 * @runTestsInSeparateProcesses
 * @preserveGlobalState
 */
class Test_WCML_Admin_Menu extends OTGS_TestCase {
	/**
	 * @test
	 */
	public function it_setups_menus_in_fronted() {
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		define( 'ICL_SITEPRESS_VERSION', '4.6' );

		$main_menu = $this->createMock( 'WCML_Admin_Menu_Main' );
		$main_menu->expects( $this->once() )->method( 'init_hooks' );

		$language_switcher = $this->createMock( 'WCML_Admin_Menu_Language_Switcher' );
		$language_switcher->expects( $this->once() )->method( 'remove_hook' );

		$documentation_link = $this->createMock( 'WCML_Admin_Menu_Documentation_Links' );
		$documentation_link->expects( $this->never() )->method( 'init_hooks' );

		$menu_warnings = $this->createMock( 'WCML_Admin_Menu_Warning' );
		$menu_warnings->expects( $this->once() )->method( 'add_hooks' );

		$subject = new WCML_Admin_Menu( true, $main_menu, $language_switcher, $documentation_link, $menu_warnings );

		$this->expectActionAdded( 'admin_head', array( $subject, 'hide_multilingual_content_setup_box' ), 10, 0, 0 );

		$subject->set_up_menus();
	}

	/**
	 * @test
	 */
	public function it_setups_menus_when_dependencies_are_incorrect() {
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		define( 'ICL_SITEPRESS_VERSION', '4.6' );

		$main_menu = $this->createMock( 'WCML_Admin_Menu_Main' );
		$main_menu->expects( $this->once() )->method( 'init_hooks' );

		$language_switcher = $this->createMock( 'WCML_Admin_Menu_Language_Switcher' );
		$language_switcher->expects( $this->once() )->method( 'remove_hook' );

		$documentation_link = $this->createMock( 'WCML_Admin_Menu_Documentation_Links' );
		$documentation_link->expects( $this->never() )->method( 'init_hooks' );

		$menu_warnings = $this->createMock( 'WCML_Admin_Menu_Warning' );
		$menu_warnings->expects( $this->once() )->method( 'add_hooks' );

		$subject = new WCML_Admin_Menu( false, $main_menu, $language_switcher, $documentation_link, $menu_warnings );

		$this->expectActionAdded( 'admin_head', array( $subject, 'hide_multilingual_content_setup_box' ), 10, 0, 0 );

		$subject->set_up_menus();
	}

	public function it_setups_menu_when_wpml_core_is_not_activated() {
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$main_menu = $this->createMock( 'WCML_Admin_Menu_Main' );
		$main_menu->expects( $this->once() )->method( 'init_hooks' );

		$language_switcher = $this->createMock( 'WCML_Admin_Menu_Language_Switcher' );
		$language_switcher->expects( $this->once() )->method( 'remove_hook' );

		$documentation_link = $this->createMock( 'WCML_Admin_Menu_Documentation_Links' );
		$documentation_link->expects( $this->never() )->method( 'init_hooks' );

		$menu_warnings = $this->createMock( 'WCML_Admin_Menu_Warning' );
		$menu_warnings->expects( $this->once() )->method( 'add_hooks' );

		$subject = new WCML_Admin_Menu( true, $main_menu, $language_switcher, $documentation_link, $menu_warnings );

		$this->expectActionAdded( 'admin_head', array( $subject, 'hide_multilingual_content_setup_box' ), 10, 0, 0 );

		$subject->set_up_menus();
	}

	/**
	 * @test
	 */
	public function it_setups_menus_in_admin() {
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		define( 'ICL_SITEPRESS_VERSION', '4.6' );

		$main_menu = $this->createMock( 'WCML_Admin_Menu_Main' );
		$main_menu->expects( $this->once() )->method( 'init_hooks' );

		$language_switcher = $this->createMock( 'WCML_Admin_Menu_Language_Switcher' );
		$language_switcher->expects( $this->once() )->method( 'remove_hook' );

		$documentation_link = $this->createMock( 'WCML_Admin_Menu_Documentation_Links' );
		$documentation_link->expects( $this->once() )->method( 'init_hooks' );

		$menu_warnings = $this->createMock( 'WCML_Admin_Menu_Warning' );
		$menu_warnings->expects( $this->once() )->method( 'add_hooks' );

		$subject = new WCML_Admin_Menu( true, $main_menu, $language_switcher, $documentation_link, $menu_warnings );

		$this->expectActionAdded( 'admin_head', array( $subject, 'hide_multilingual_content_setup_box' ), 10, 0, 1 );

		$subject->set_up_menus();
	}
}
