<?php

namespace WCML\Compatibility\WcCompositeProducts;

use WCML\Compatibility\ComponentFactory;
use WCML_Composite_Products;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_Composite_Products( getSitePress(), getWooCommerceWpml(), self::getElementTranslationPackage() );
	}
}
