<?php

namespace WCML\Multicurrency\Shipping;

class FlatRateShipping implements ShippingMode {
	use ShippingModeBase;
	use VariableCostModeBase;

	public function getMethodId() {
		return 'flat_rate';
	}
}