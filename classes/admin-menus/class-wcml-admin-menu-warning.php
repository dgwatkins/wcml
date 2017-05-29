<?php

class WCML_Admin_Menu_Warning {

	const MENU_LABEL = 'WooCommerce';
	const SUBMENU_LABEL = 'WooCommerce Multilingual';
	const ICON = '<span class="wcml-menu-warn"><i class="otgs-ico-warning"></i></span>';

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var array */
	private $menu;

	/** @var array */
	private $submenu;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param array $menu
	 * @param array $submenu
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, &$menu, &$submenu ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->menu             = &$menu;
		$this->submenu          = &$submenu;
	}

	public function add_hooks() {
		add_action( 'admin_head', array( $this, 'add_menu_warning' ), 10, 0 );
	}

	public function add_menu_warning() {
		if ( class_exists( 'WooCommerce' ) && empty( $this->woocommerce_wpml->settings['set_up_wizard_run'] ) ) {
			if ( isset( $this->submenu['woocommerce'] ) ) {
				foreach ( $this->submenu['woocommerce'] as $key => $menu_item ) {
					if ( __( self::SUBMENU_LABEL, 'woocommerce-multilingual' ) === $menu_item[0] ) {
						$this->submenu['woocommerce'][ $key ][0] .= self::ICON;
						break;
					}
				}
			}

			foreach ( $this->menu as $key => $menu_item ) {
				if ( __( self::MENU_LABEL, 'woocommerce' ) === $menu_item[0] ) {
					$this->menu[ $key ][0] .= self::ICON ;
					break;
				}
			}
		}
	}
}
