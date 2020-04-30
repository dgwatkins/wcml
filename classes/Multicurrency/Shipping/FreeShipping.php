<?php

namespace WCML\Multicurrency\Shipping;

class FreeShipping implements ShippingMode {
	public function getFieldTitle( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'Minimal order amount in %s',
			'The label for the field with minimal order amount in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getFieldDescription( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'The minimal order amount if customer choose %s as a purchase currency.',
			'The description for the field with minimal order amount in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getMethodId() {
		return 'free_shipping';
	}

	/**
	 * Returns minimal amount key for given currency.
	 *
	 * @param string $currencyCode Currency code.
	 *
	 * @return string
	 */
	private function getMinimalOrderAmountKey( $currencyCode ) {
		$currencyCode = is_string( $currencyCode ) ? $currencyCode : '';
		return sprintf( 'min_amount_%s', $currencyCode );
	}

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getMinimalOrderAmountKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		$key = $this->getMinimalOrderAmountKey( $currency );
		if ( isset( $shipping[ $key ] ) ) {
			$amount = $shipping[ $key ];
		}
		return apply_filters( 'wcml_free_shipping_manual_min_amount', $amount, $shipping, $currency);
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return apply_filters( 'wcml_free_shipping_manual_cost', $rate->cost, $rate, $currency);
	}
}