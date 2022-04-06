<?php

namespace WCML\Compatibility\WcProductBundles;

use WCML\Compatibility\ComponentFactory;
use WCML_Product_Bundles;
use WCML_WC_Product_Bundles_Items;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_Product_Bundles( getSitePress(), getWooCommerceWpml(), new WCML_WC_Product_Bundles_Items(), self::getWpdb() );
	}
}
