<?php

/**
 * Class WCML_Currencies_Payment_Gateways
 */
class WCML_Currencies_Payment_Gateways {

	const OPTION_KEY  = 'wcml_custom_payment_gateways_for_currencies';

	/**
	 * @param string $currency
	 *
	 * @return bool
	 */
	public function is_enabled( $currency ) {

		$gateway_enabled_settings = $this->get_settings();

		if( isset( $gateway_enabled_settings[ $currency ] ) ){
			return $gateway_enabled_settings[ $currency ];
		}

		return false;
	}

	/**
	 * @param string $currency
	 * @param bool $value
	 */
	public function set_enabled( $currency, $value ) {

		$gateway_enabled_settings = $this->get_settings();
		$gateway_enabled_settings[ $currency ] = $value;

		update_option( self::OPTION_KEY, $gateway_enabled_settings );
	}

	/**
	 * @return array
	 */
	private function get_settings(){
		return get_option( self::OPTION_KEY, array() );
	}

	/**
	 * @return array
	 */
	public function get_gateways() {
		$available_gateways = $this->get_available_payment_gateways();

		$payment_gateways = array();
		foreach ( $available_gateways as $gateway ) {
			switch ( $gateway->id ) {
				default:
					$payment_gateways[ $gateway->id ] = new WCML_Not_Supported_Payment_Gateway( $gateway );
					break;
			}

		}

		return $payment_gateways;
	}

	/**
	 * @return array
	 */
	private function get_available_payment_gateways() {
		return WC()->payment_gateways->get_available_payment_gateways();
	}

}