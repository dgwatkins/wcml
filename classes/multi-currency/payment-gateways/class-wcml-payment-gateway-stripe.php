<?php

/**
 * Class WCML_Payment_Gateway_Stripe
 */
class WCML_Payment_Gateway_Stripe extends WCML_Payment_Gateway {

	public function get_settings_output( $current_currency, $active_currencies ){

		$ui_settings = new WCML_Stripe_Gateway_UI( $current_currency, $active_currencies, $this );

		return $ui_settings->render();
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
				$currencies_details[ $code ]['publishable_key'] = $this->get_gateway()->settings['publishable_key'];
				$currencies_details[ $code ]['secret_key'] = $this->get_gateway()->settings['secret_key'];
			} else {
				$currency_gateway_setting    = $this->get_setting( $code );
				$currencies_details[ $code ]['publishable_key'] = $currency_gateway_setting ? $currency_gateway_setting['publishable_key'] : '';
				$currencies_details[ $code ]['secret_key'] = $currency_gateway_setting ? $currency_gateway_setting['secret_key'] : '';
			}
		}

		return $currencies_details;

	}


}