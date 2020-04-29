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

	public function getCostKey( $currencyCode ) {
		// TODO: Implement getCostKey() method.
	}

	public function getMinimalOrderAmountKey( $currencyCode ) {
		// TODO: Implement getMinAmountKey() method.
	}

	public function getSettingsFormKey( $currencyCode ) {
		// TODO: Implement getSettingsFormKey() method.
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
		return $rate->cost;
	}
}
