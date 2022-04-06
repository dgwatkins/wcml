<?php

namespace WCML\Compatibility\WcProductAddons;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new \WCML_Product_Addons( getSitePress(), getWooCommerceWpml() );
	}
}
