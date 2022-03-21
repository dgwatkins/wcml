<?php

namespace WCML\StandAlone\UI;

use WCML\Utilities\AdminPages;
use WCML_Admin_Menus;
use WCML_Multi_Currency_UI;
use WCML_Templates_Factory;
use WPML\FP\Fns;
use WPML\FP\Str;

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
		$current_tab = AdminPages::getTabToDisplay();

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
				$inP       = Str::wrap( '<p>', '</p>' );
				$inWrapper = Str::wrap( '<div class="wcml-banner">', '</div>' );

				$wrapLink = function( $text, $url ) {
					return sprintf( $text, '<a href="' . $url . '" target="_blank" class="wpml-external-link">', '</a>' );
				};

				$content .= $inP( $wrapLink( esc_html__( 'To run your store in multiple languages, you need to use the %1$sWPML plugin%2$s.', 'woocommerce-multilingual' ), \WCML_Tracking_Link::getWpmlHome( true ) ) );
				$content .= $inP(
					$wrapLink( esc_html__( 'If you have it already, install and activate it. Otherwise, %1$sbuy WPML%2$s.', 'woocommerce-multilingual' ), \WCML_Tracking_Link::getWpmlPurchase( true ) )
					. ' ' . esc_html__( 'You will need either the Multilingual CMS or Multilingual Agency package to use WPML with WooCommerce.', 'woocommerce-multilingual' )
				);

				$content = $inWrapper( $content );
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
