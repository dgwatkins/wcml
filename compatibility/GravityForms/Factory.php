<?php

namespace WCML\Compatibility\GravityForms;

use WCML\Compatibility\ComponentFactory;
use WCML_gravityforms;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_gravityforms( getSitePress(), getWooCommerceWpml() );
	}
}