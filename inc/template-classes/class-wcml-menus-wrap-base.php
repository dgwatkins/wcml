<?php

abstract class WCML_Menu_Wrap_Base extends WCML_Templates_Factory {

	/**
	 * @var \woocommerce_wpml $woocommerce_wpml
	 */
	protected $woocommerce_wpml;

	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	/**
	 * @return array
	 */
	public function get_model() {
		return array_merge(
			[
				'can_operate_options' => current_user_can( 'wpml_operate_woocommerce_multilingual' ),
				'rate'                => [
					'on'        => $this->woocommerce_wpml->get_setting( 'rate-block', true ),
					'message'   => sprintf(
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
			],
			$this->get_child_model()
		);
	}

	/**
	 * @return array
	 */
	abstract protected function get_child_model();

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'menus-wrap.twig';
	}

}
