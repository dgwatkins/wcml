<?php

namespace WCML\Compatibility\WoofWcProductFilter;

use WCML\Compatibility\ComponentFactory;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new Hooks();
	}
}
