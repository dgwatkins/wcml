<?php

class WCML_Admin_Menu {
	/** @var bool */
    private $check_dependencies;

    /** @var WCML_Admin_Menu_Main */
    private $main_menu;

    /** @var WCML_Admin_Menu_Language_Switcher */
    private $language_switcher;

    /** @var WCML_Admin_Menu_Documentation_Links */
    private $documentation_links;

    /** @var WCML_Admin_Menu_Warning */
    private $menu_warnings;

	/**
	 * @param bool $check_dependencies
	 * @param WCML_Admin_Menu_Main $main_menu
	 * @param WCML_Admin_Menu_Language_Switcher $language_switcher
	 * @param WCML_Admin_Menu_Documentation_Links $documentation_links
	 * @param WCML_Admin_Menu_Warning $menu_warnings
	 */
	public function __construct(
		$check_dependencies,
		WCML_Admin_Menu_Main $main_menu,
		WCML_Admin_Menu_Language_Switcher $language_switcher,
		WCML_Admin_Menu_Documentation_Links $documentation_links,
		WCML_Admin_Menu_Warning $menu_warnings
	) {
		$this->check_dependencies = $check_dependencies;
		$this->main_menu = $main_menu;
		$this->language_switcher = $language_switcher;
		$this->documentation_links = $documentation_links;
		$this->menu_warnings = $menu_warnings;
	}


	public function set_up_menus() {
		$this->main_menu->init_hooks();
		$this->language_switcher->remove_hook();

		if ( is_admin() && defined( 'ICL_SITEPRESS_VERSION' ) && $this->check_dependencies ) {
			$this->documentation_links->init_hooks();
			add_action( 'admin_head', array( $this, 'hide_multilingual_content_setup_box' ), 10, 0 );
		}

		$this->menu_warnings->add_hooks();
    }

	public function hide_multilingual_content_setup_box() {
		remove_meta_box( 'icl_div_config', convert_to_screen( 'shop_order' ), 'normal' );
		remove_meta_box( 'icl_div_config', convert_to_screen( 'shop_coupon' ), 'normal' );
	}
}