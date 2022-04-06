<?php

namespace WCML\Compatibility\WcCheckoutAddons;

use WCML\Compatibility\ComponentFactory;
use WCML_Checkout_Addons;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_Checkout_Addons();
	}
}
