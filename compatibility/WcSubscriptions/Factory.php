<?php

namespace WCML\Compatibility\WcSubscriptions;

use WCML\Compatibility\ComponentFactory;
use WCML_WC_Subscriptions;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_WC_Subscriptions( getWooCommerceWpml(), self::getWpdb() );
	}
}