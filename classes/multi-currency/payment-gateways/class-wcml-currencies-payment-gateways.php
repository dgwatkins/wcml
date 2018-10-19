<?php

/**
 * Class WCML_Currencies_Payment_Gateways
 */
class WCML_Currencies_Payment_Gateways {

	const OPTION_KEY      = 'wcml_custom_payment_gateways_for_currencies';
	const TEMPLATE_FOLDER = '/templates/multi-currency/payment-gateways/';

	private $available_gateways = array();
	private $supported_gateways = array();
	private $payment_gateways = array();

	/**
	 * @param string $currency
	 *
	 * @return bool
	 */
	public function is_enabled( $currency ) {

		$gateway_enabled_settings = $this->get_settings();

		if ( isset( $gateway_enabled_settings[ $currency ] ) ) {
			return $gateway_enabled_settings[ $currency ];
		}

		return false;
	}

	/**
	 * @param string $currency
	 * @param bool   $value
	 */
	public function set_enabled( $currency, $value ) {

		$gateway_enabled_settings              = $this->get_settings();
		$gateway_enabled_settings[ $currency ] = $value;

		update_option( self::OPTION_KEY, $gateway_enabled_settings );
	}

	/**
	 * @return array
	 */
	private function get_settings() {
		return get_option( self::OPTION_KEY, array() );
	}

	/**
	 * @return array
	 */
	public function get_gateways() {
		$this->available_gateways = $this->get_available_payment_gateways();

		$this->supported_gateways = array(
			'bacs'   => 'WCML_Payment_Gateway_Bacs',
			'stripe' => 'WCML_Payment_Gateway_Stripe',
		);
		$this->supported_gateways = apply_filters( 'wcml_supported_currency_payment_gateways', $this->supported_gateways );

		$this->store_supported_gateways();
		$this->store_non_supported_gateways();

		return $this->payment_gateways;
	}

	/**
	 * @param string $id
	 * @param object $supported_gateway
	 *
	 * @return bool
	 */
	private function is_a_valid_gateway( $id, $supported_gateway ) {
		return is_subclass_of( $supported_gateway, 'WCML_Payment_Gateway' ) && array_key_exists( $id, $this->available_gateways );
	}

	private function store_supported_gateways() {
		if ( is_array( $this->supported_gateways ) ) {
			/** @var \WCML_Payment_Gateway $supported_gateway */
			foreach ( $this->supported_gateways as $id => $supported_gateway ) {
				if ( $this->is_a_valid_gateway( $id, $supported_gateway ) ) {
					$this->payment_gateways[ $id ] = new $supported_gateway( $this->available_gateways[ $id ], $this->get_template_service() );
				}
			}
		}
	}

	private function store_non_supported_gateways() {
		$non_supported_gateways = array_diff( array_keys( $this->available_gateways ), array_keys( $this->payment_gateways ) );

		/** @var int $non_supported_gateway */
		foreach ( $non_supported_gateways as $non_supported_gateway ) {
			$this->payment_gateways[ $non_supported_gateway ] = new WCML_Not_Supported_Payment_Gateway( $this->available_gateways[ $non_supported_gateway ], $this->get_template_service() );
		}
	}

	/**
	 * @return \WPML_Twig_Template
	 */
	private function get_template_service() {
		$twig_loader = new WPML_Twig_Template_Loader( array( self::TEMPLATE_FOLDER ) );

		return $twig_loader->get_template();
	}

	/**
	 * @return array
	 */
	private function get_available_payment_gateways() {
		return WC()
			->payment_gateways()
			->get_available_payment_gateways();
	}

}
