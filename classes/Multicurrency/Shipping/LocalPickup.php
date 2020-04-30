<?php

namespace WCML\Multicurrency\Shipping;

class LocalPickup implements ShippingMode {
	use ShippingModeBase;
	use VariableCostModeBase;

	public function getMethodId() {
		return 'local_pickup';
	}
}