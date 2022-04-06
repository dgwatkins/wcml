<?php

namespace WCML\Compatibility\WcNameYourPrice;

use WCML\Compatibility\ComponentFactory;
use WCML_WC_Name_Your_Price;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_WC_Name_Your_Price( getSitePress(), getWooCommerceWpml() );
	}
}