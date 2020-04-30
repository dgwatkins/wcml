<?php

namespace WCML\Multicurrency\Shipping;

class FlatRateShipping implements ShippingMode {
	public function getFieldTitle( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'Cost in %s',
			'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getFieldDescription( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
			'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getMethodId() {
		return 'flat_rate';
	}

	/**
	 * Returns cost key for given currency.
	 *
	 * @param string $currencyCode Currency code.
	 *
	 * @return string
	 */
	private function getCostKey( $currencyCode ) {
		return sprintf( 'cost_%s', $currencyCode );
	}

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getCostKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return apply_filters( 'wcml_flat_rate_manual_min_amount', $amount, $shipping, $currency);
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
		if ( isset( $rate->method_id, $rate->instance_id ) ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $rate->method_id, $rate->instance_id );
			$cost_name = $this->getCostKey( $currency );
			$rate_settings = get_option( $option_name );
			if ( isset( $rate_settings[ $cost_name ] ) ) {
				return $rate_settings[ $cost_name ];
			}
		}
		return apply_filters( 'wcml_flat_rate_manual_cost', $rate->cost, $rate, $currency);
	}
}