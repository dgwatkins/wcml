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

	public function isManualPricingEnabled( $instance = false ) {
		return false;
	}

	public function getMinimalOrderAmountKey( $currencyCode ) {
		// TODO: Implement getMinAmountKey() method.
	}

	public function getShippingCostValue( $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return $rate->cost;
	}

	public function supportsShippingClasses() {
		return false;
	}

	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey ) {
		if ( ! $this->supportsShippingClasses() ) {
			throw new Exception( 'Method should not be called because this class does not support shipping classes.' );
		}
		return 0;
	}

	public function getNoShippingClassCostValue( $rate, $currency ) {
		if ( ! $this->supportsShippingClasses() ) {
			throw new Exception( 'Method should not be called because this class does not support shipping classes.' );
		}
		return 0;
	}
}
