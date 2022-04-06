<?php

namespace WCML\Compatibility\TableRateShipping;

use WCML\Compatibility\ComponentFactory;
use WCML_Table_Rate_Shipping;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {
	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_Table_Rate_Shipping( getSitePress(), getWooCommerceWpml(), self::getWpdb() );
	}
}
