<?php

namespace WCML\StandAlone\UI;

use WCML_Admin_Menus;
use WCML_Multi_Currency_UI;
use WCML_Templates_Factory;

class AdminMenu extends WCML_Templates_Factory {

	/** @var \SitePress|\WCML\StandAlone\NullSitePress */
	private $sitepress;

	/** @var \woocommerce_wpml */
	private $woocommerce_wpml;

	/**
	 * WCML_Menus_Wrap constructor.
	 *
	 * @param \SitePress|\WCML\StandAlone\NullSitePress $sitepress
	 * @param \woocommerce_wpml                          $woocommerce_wpml
	 */
	public function __construct( \WPML\Core\ISitePress $sitepress, $woocommerce_wpml ) {
		parent::__construct();

		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function get_model() {
		$current_tab = $this->get_current_tab();

		$model = [
			'strings'             => [
				'title'              => WCML_Admin_Menus::getWcmlLabel(),
			],
			'is_standalone'       => true,
			'menu'                => [
				'multilingual'          => [
					'title'  => __( 'Multilingual', 'woocommerce-multilingual' ),
					'active' => 'multilingual' === $current_tab ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=multilingual' ),
				],
				'multi_currency'    => [
					'name'   => __( 'Multicurrency', 'woocommerce-multilingual' ),
					'active' => 'multi-currency' === $current_tab ? 'nav-tab-active' : '',
					'url'    => admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' ),
				],
			],
			'can_operate_options' => current_user_can( 'wpml_operate_woocommerce_multilingual' ),
			'rate'                => [
				'on'        => $this->woocommerce_wpml->get_setting( 'rate-block', true ),
				'message'   => sprintf(
					// translators: Sorry but I don't know what this is.
					__( 'Thank you for using %1$sWooCommerce Multilingual & Multicurrency%2$s! You can express your love and support by %3$s rating our plugin and saying that %4$sit works%5$s for you.', 'woocommerce-multilingual' ),
					'<strong>',
					'</strong>',
					'<a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-multilingual?filter=5#postform" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
					'<a href="https://wordpress.org/plugins/woocommerce-multilingual/?compatibility[version]=' . $this->woocommerce_wpml->get_supported_wp_version() . '&compatibility[topic_version]=' . WCML_VERSION . '&compatibility[compatible]=1#compatibility" target="_blank">',
					'</a>'
				),
				'hide_text' => __( 'Hide', 'woocommerce-multilingual' ),
				'nonce'     => wp_nonce_field( 'wcml_settings', 'wcml_settings_nonce', true, false ),
			],
			'content'             => $this->get_current_menu_content( $current_tab ),
		];

		return $model;
	}

	protected function get_current_tab() {

		$current_tab = filter_input( INPUT_GET, 'tab' );
		if ( $current_tab ) {
			if ( ! current_user_can( 'wpml_manage_woocommerce_multilingual' ) && ! current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
				$current_tab = 'multi-currency';
			}
		} else {
			$current_tab = 'multi-currency';
		}

		return $current_tab;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'menus-wrap.twig';
	}

	protected function get_current_menu_content( $current_tab ) {
		$content = '';
		switch ( $current_tab ) {
			case 'multilingual':
				$content = "Multilingual content...";
				break;

			case 'multi-currency':
			default:
				if ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
					$wcml_mc_ui = new WCML_Multi_Currency_UI( $this->woocommerce_wpml, $this->sitepress );
					$content    = $wcml_mc_ui->get_view();
				}

				break;
		}

		return $content;

	}

}
