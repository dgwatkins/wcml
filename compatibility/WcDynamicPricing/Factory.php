<?php

namespace WCML\Compatibility\WcDynamicPricing;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getSitePress;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new \WCML_Dynamic_Pricing( getSitePress() );
	}
}
