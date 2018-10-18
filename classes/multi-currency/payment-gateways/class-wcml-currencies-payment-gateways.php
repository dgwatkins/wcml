<?php

/**
 * Class WCML_Currencies_Payment_Gateways
 */
class WCML_Currencies_Payment_Gateways {

	const OPTION_KEY = 'wcml_custom_payment_gateways_for_currencies';

	private $available_gateways = array();
	private $supported_gateways = array();
	private $payment_gateways  = array();

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
			'bacs' => 'WCML_Payment_Gateway_Bacs',
		);
		$this->supported_gateways = apply_filters( 'wcml_supported_currency_payment_gateways', $this->supported_gateways );

		$this->store_supported_gateways();
		$this->store_non_supported_gateways();


		return $this->payment_gateways;
	}

	/**
	 * @return array
	 */
	private function get_available_payment_gateways() {
		return WC()->payment_gateways()->get_available_payment_gateways();
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	private function is_a_valid_gateway( $id ) {
		return is_subclass_of( $this->supported_gateways, 'WCML_Payment_Gateway' ) && array_key_exists( $id, $this->available_gateways );
	}

	private function store_supported_gateways() {
		if ( is_array( $this->supported_gateways ) ) {
			/** @var \WCML_Payment_Gateway $supported_gateway */
			foreach ( $this->supported_gateways as $id => $supported_gateway ) {
				if ( $this->is_a_valid_gateway( $id ) ) {
					$this->payment_gateways[ $id ] = new $supported_gateway( $this->available_gateways[ $id ] );
				}
			}
		}
	}

	private function store_non_supported_gateways() {
		$non_supported_gateways = array_diff( array_keys( $this->available_gateways ), array_keys( $this->payment_gateways ) );

		/** @var int $non_supported_gateway */
		foreach ( $non_supported_gateways as $non_supported_gateway ) {
			$this->payment_gateways[ $non_supported_gateway ] = new WCML_Not_Supported_Payment_Gateway( $this->available_gateways[ $non_supported_gateway ] );
		}
	}

}
