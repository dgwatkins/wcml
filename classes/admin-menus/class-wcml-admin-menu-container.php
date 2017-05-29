<?php

class WCML_Admin_Menu_Container {
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var wpdb */
	private $wpdb;

	/** @var SitePress */
	private $sitepress;

	/** @var WPML_WP_API */
	private $wp_api;

	/** @var WPML_Translation_Management */
	private $tanslation_management;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		global $wpdb, $sitepress, $WPML_Translation_Management, $wp_api;
		$this->woocommerce_wpml = $woocommerce_wpml;

		$this->wpdb = $wpdb;
		$this->sitepress = $sitepress;
		$this->wp_api = $wp_api;
		$this->tanslation_management = $WPML_Translation_Management;
	}

	/**
	 * @return WCML_Admin_Menu
	 */
	public function get_admin_menus() {
		return new WCML_Admin_Menu(
			$this->woocommerce_wpml->check_dependencies,
			$this->get_main_menu(),
			$this->get_admin_menu_language_switcher(),
			$this->get_admin_menu_documentation_links(),
			$this->get_menu_warnings()
		);
	}

	/**
	 * @return WCML_Admin_Menu_Main
	 */
	public function get_main_menu() {
		return new WCML_Admin_Menu_Main(
			$this->woocommerce_wpml->check_dependencies,
			$this->tanslation_management,
			$this->get_main_menu_render_strategy(),
			$this->wp_api
		);
	}

	/**
	 * @return WCML_Menus_Wrap|WCML_Plugins_Wrap
	 */
	private function get_main_menu_render_strategy() {
		if ( $this->woocommerce_wpml->check_dependencies ) {
			$render_strategy = new WCML_Menus_Wrap( $this->woocommerce_wpml );
		} else {
			$render_strategy = new WCML_Plugins_Wrap( $this->woocommerce_wpml, $this->sitepress );
		}

		return $render_strategy;
	}

	/**
	 * @return WCML_Admin_Menu_Language_Switcher
	 */
	private function get_admin_menu_language_switcher() {
		global $pagenow;

		return new WCML_Admin_Menu_Language_Switcher( $pagenow, $this->sitepress );
	}

	/**
	 * @return WCML_Admin_Menu_Documentation_Links
	 */
	private function get_admin_menu_documentation_links() {
		global $post, $pagenow;

		return new WCML_Admin_Menu_Documentation_Links( $post, $pagenow, new WCML_Tracking_Link() );
	}

	/**
	 * @return WCML_Admin_Menu_Warning
	 */
	private function get_menu_warnings() {
		global $menu, $submenu;

		return new WCML_Admin_Menu_Warning( $this->woocommerce_wpml, $menu, $submenu );
	}
}
