<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Bacs_Gateway_UI extends WPML_Twig_Template_Loader {

	const TEMPLATE = 'bacs.twig';
	const TEMPLATE_FOLDER = '/templates/multi-currency/payment-gateways/';

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

	/**
	 * WCML_Bacs_Gateway_UI constructor.
	 *
	 * @param string $current_currency
	 * @param array $active_currencies
	 * @param WCML_Payment_Gateway $gateway
	 */
	function __construct( $current_currency, array $active_currencies, WCML_Payment_Gateway $gateway ){
		parent::__construct( array( WCML_PLUGIN_PATH . self::TEMPLATE_FOLDER ) );

		$this->current_currency  = $current_currency;
		$this->active_currencies = $active_currencies;
		$this->gateway           = $gateway;
	}

	public function render() {

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
			'gateway_settings'  => $this->gateway->get_setting( $this->current_currency ),
			'active_currencies' => $this->active_currencies,
			'account_details'   => $this->gateway->get_gateway()->account_details,
		);

		return $this->get_template()->show( $model, self::TEMPLATE );
	}

}