<?php

namespace WCML\Multicurrency\Shipping;

class UnsupportedShipping implements ShippingMode {

	public function getMethodId() {
		// TODO: Implement getMethodId() method.
	}

	public function getFieldTitle( $currencyCode ) {
		// TODO: Implement getFieldTitle() method.
	}

	public function getFieldDescription( $currencyCode ) {
		// TODO: Implement getFieldDescription() method.
	}

	public function getSettingsFormKey( $currencyCode ) {
		// TODO: Implement getSettingsFormKey() method.
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return apply_filters( 'wcml_unsupported_shipping_manual_min_amount', $amount, $shipping, $currency);
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return apply_filters( 'wcml_unsupported_shipping_manual_cost', $rate->cost, $rate, $currency);
	}
}
