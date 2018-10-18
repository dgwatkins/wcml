<?php

/**
 * Class WCML_Payment_Gateway_PayPal
 */
class WCML_Payment_Gateway_PayPal extends WCML_Payment_Gateway {

	const TEMPLATE = 'paypal.twig';

	protected function get_output_model() {
		$currencies_details = $this->get_currencies_details( $this->active_currencies );

		return array(
			'strings'                => array(
				'currency_label' => __( 'Currency', 'woocommerce-multilingual' ),
				'setting_label'  => __( 'PayPal email', 'woocommerce-multilingual' ),
				'not_supported'  => sprintf( __( 'This gateway does not support %s. To show this gateway please select another currency.', 'woocommerce-multilingual' ), $this->current_currency )
			),
			'gateway_id'             => $this->get_id(),
			'gateway_title'          => $this->get_title(),
			'current_currency'       => $this->current_currency,
			'gateway_settings'       => $this->get_setting( $this->current_currency ),
			'currencies_details'     => $currencies_details,
			'current_currency_valid' => $currencies_details[ $this->current_currency ]['is_valid']
		);
	}

	protected function get_output_template() {
		return self::TEMPLATE;
	}

	/**
	 * @param $currency
	 *
	 * @return bool
	 */
	public function is_valid_for_use( $currency ) {
		return in_array(
			$currency,
			apply_filters(
				'woocommerce_paypal_supported_currencies',
				array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB', 'INR' )
			),
			true
		);
	}

	/**
	 * @param array $active_currencies
	 *
	 * @return array
	 */
	public function get_currencies_details( $active_currencies ){

		$currencies_details = array();
		$default_currency   = get_option( 'woocommerce_currency' );

		foreach ( $active_currencies as $code => $currency ) {

			if ( $default_currency === $code ) {
				$currencies_details[ $code ]['value'] = $this->get_gateway()->settings['email'];
				$currencies_details[ $code ]['is_valid'] = $this->is_valid_for_use( $default_currency );
			} else {
				$currency_gateway_setting    = $this->get_setting( $code );
				$currencies_details[ $code ]['value'] = $currency_gateway_setting ? $currency_gateway_setting['value'] : '';
				$currencies_details[ $code ]['is_valid'] = $this->is_valid_for_use( $code );
			}
		}

		return $currencies_details;

	}

}