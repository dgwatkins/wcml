<?php

namespace WCML\Compatibility\KlarnaPayments;

use WCML\Compatibility\ComponentFactory;

/**
 * @see https://wordpress.org/plugins/klarna-payments-for-woocommerce/
 */
class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new \WCML_Klarna_Gateway();
	}
}
