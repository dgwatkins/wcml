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
		return null;
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return $rate->cost;
	}

	public function isManualPricingEnabled( $instance = false ) {
		return false;
	}

	public function supportsShippingClasses() {
		return false;
	}
}
