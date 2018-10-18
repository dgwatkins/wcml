<?php

/**
 * Class WCML_Payment_Gateway_Stripe
 */
class WCML_Payment_Gateway_Stripe extends WCML_Payment_Gateway {

	const TEMPLATE = 'stripe.twig';

	protected function get_output_model() {
		return array(
			'strings'            => array(
				'currency_label'    => __( 'Currency', 'woocommerce-multilingual' ),
				'publishable_label' => __( 'Live Publishable Key', 'woocommerce-multilingual' ),
				'secret_label'      => __( 'Live Secret Key', 'woocommerce-multilingual' ),
			),
			'gateway_id'         => $this->get_id(),
			'gateway_title'      => $this->get_title(),
			'current_currency'   => $this->current_currency,
			'gateway_settings'   => $this->get_setting( $this->current_currency ),
			'currencies_details' => $this->get_currencies_details( $this->active_currencies )
		);
	}

	protected function get_output_template() {
		return self::TEMPLATE;
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