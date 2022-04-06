<?php

namespace WCML\Compatibility\YithWcQuickView;

use WCML\Compatibility\ComponentFactory;

/**
 * @see https://wordpress.org/plugins/yith-woocommerce-quick-view/
 */
class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new \WCML_YITH_WCQV();
	}
}
