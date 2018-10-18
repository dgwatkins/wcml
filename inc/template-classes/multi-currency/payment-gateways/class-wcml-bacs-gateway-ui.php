<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Bacs_Gateway_UI extends WPML_Templates_Factory {

	/**
	 * @var string
	 */
	private $current_currency;
	/**
	 * @var array
	 */
	private $active_currencies;
	/**
	 * @var WCML_Payment_Gateway
	 */
	private $gateway;

	function __construct( $current_currency, $active_currencies, $gateway ){
		parent::__construct();

		$this->current_currency  = $current_currency;
		$this->active_currencies = $active_currencies;
		$this->gateway           = $gateway;
	}

	public function get_model() {

		$model = array(
			'strings'           => array(
				'currency_label' => __( 'Currency', 'woocommerce-multilingual' ),
				'setting_label'  => __( 'Bank Account', 'woocommerce-multilingual' ),
				'all_label'      => __( 'All Accounts', 'woocommerce-multilingual' ),
				'tooltip'        => __( 'Set the currency in which your customer will see the final price in checkout. Choose to which accounts he will see in payment message', 'woocommerce-multilingual' )
			),
			'gateway_id'        => $this->gateway->get_id(),
			'gateway_title'     => $this->gateway->get_title(),
			'current_currency'  => $this->current_currency,
			'gateway_settings'  => $this->gateway->get_settings(),
			'active_currencies' => $this->active_currencies,
			'account_details'   => $this->gateway->get_gateway()->account_details,
		);

		return $model;
	}

	public function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/multi-currency/payment-gateways/',
		);
	}

	public function get_template() {
		return 'bacs.twig';
	}
}