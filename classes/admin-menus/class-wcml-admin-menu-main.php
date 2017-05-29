<?php

class WCML_Admin_Menu_Main {
	/** @var bool */
	private $check_dependencies;

	/** @var WPML_Translation_Management */
	private $translation_management;

	/** @var WCML_Admin_Menu_Display */
	private $render_strategy;

	/** @var WPML_WP_API */
	private $wp_api;

	/**
	 * @param bool $check_dependencies
	 * @param WPML_Translation_Management $translation_management
	 * @param WCML_Admin_Menu_Display $render_strategy
	 * @param WPML_WP_API $wp_api
	 */
	public function __construct(
		$check_dependencies,
		WPML_Translation_Management $translation_management,
		WCML_Admin_Menu_Display $render_strategy,
		WPML_WP_API $wp_api
	) {
		$this->check_dependencies = $check_dependencies;
		$this->translation_management = $translation_management;
		$this->render_strategy = $render_strategy;
		$this->wp_api = $wp_api;
	}

	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ), 80 );
	}

	public function register_menus() {
		if ( $this->check_dependencies || class_exists( 'WooCommerce' ) ) {

			add_submenu_page(
				'woocommerce',
				__( 'WooCommerce Multilingual', 'woocommerce-multilingual' ),
				__( 'WooCommerce Multilingual', 'woocommerce-multilingual' ),
				'wpml_operate_woocommerce_multilingual',
				'wpml-wcml',
				array( $this->render_strategy, 'render' )
			);

			if ( $this->can_user_translate() ) {
				$menu               = array();
				$menu['order']      = 400;
				$menu['page_title'] = __( 'Translations', 'wpml-translation-management' );
				$menu['menu_title'] = __( 'Translations', 'wpml-translation-management' );
				$menu['capability'] = 'wpml_operate_woocommerce_multilingual';
				$menu['menu_slug']  = WPML_TM_FOLDER . '/menu/translations-queue.php';
				$menu['function']   = array( $this->translation_management , 'translation_queue_page' );
				$menu['icon_url']   = ICL_PLUGIN_URL . '/res/img/icon16.png';
				do_action( 'wpml_admin_menu_register_item', $menu );

			}
		} else {

			add_menu_page(
				__( 'WooCommerce Multilingual', 'woocommerce-multilingual' ),
				__( 'WooCommerce Multilingual', 'woocommerce-multilingual' ),
				'wpml_manage_woocommerce_multilingual',
				'wpml-wcml',
				array( $this->render_strategy, 'render' ),
				WCML_PLUGIN_URL . '/res/images/icon16.png'
			);

		}
	}

	/**
	 * @return bool
	 */
	private function can_user_translate() {
		return ! current_user_can( 'wpml_manage_woocommerce_multilingual' ) &&
		       current_user_can( 'wpml_operate_woocommerce_multilingual' ) &&
		       ! $this->wp_api->current_user_can( 'wpml_manage_translation_management' );
	}
}
