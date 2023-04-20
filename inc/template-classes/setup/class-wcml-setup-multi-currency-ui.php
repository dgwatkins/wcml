<?php

use WPML\Core\Twig_SimpleFunction;

class WCML_Setup_Multi_Currency_UI extends WCML_Templates_Factory {

	/** @var string */
	private $next_step_url;

	/**
	 * WCML_Setup_Multi_Currency_UI constructor.
	 *
	 * @param string $next_step_url
	 */
	public function __construct( $next_step_url ) {
		parent::__construct();

		$this->next_step_url = $next_step_url;
	}

	public function get_model() {

		$model = [
			'strings'           => [
				'step_id'     => 'currency_step',
				'heading'     => __( 'Do you want to add more currencies to your store?', 'woocommerce-multilingual' ),
				'description' => __( 'You will be able to:', 'woocommerce-multilingual' ),
				'bullet1'     => __( 'Add a currency switcher to your store', 'woocommerce-multilingual' ),
				'bullet2'     => __( 'Set exchange rates', 'woocommerce-multilingual' ),
				'bullet3'     => __( 'Create custom pricing in each currency', 'woocommerce-multilingual' ),
				'bullet4'     => __( 'And more!', 'woocommerce-multilingual' ),
				'enable'      => __( 'Yes, enable multicurrency mode', 'woocommerce-multilingual' ),
				'continue'    => __( 'No, use only one currency', 'woocommerce-multilingual' ),
			],
			'documentation_url' => WCML_Tracking_Link::getWcmlMultiCurrencyDoc( '&utm_term=wcml-setup-wizard' ),
			'multi_currency_on' => wcml_is_multi_currency_on(),
			'continue_url'      => $this->next_step_url,
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/multi-currency.twig';
	}

}
