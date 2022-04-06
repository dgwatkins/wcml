<?php

namespace WCML\Compatibility\StripePayments;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new Hooks( getWooCommerceWpml()->get_multi_currency()->orders );
	}
}
