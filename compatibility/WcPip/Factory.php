<?php

namespace WCML\Compatibility\WcPip;

use WCML\Compatibility\ComponentFactory;
use WCML_Pip;

/**
 * @see https://woocommerce.com/products/print-invoices-packing-lists/
 */
class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_Pip();
	}
}
