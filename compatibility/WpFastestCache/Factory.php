<?php

namespace WCML\Compatibility\WpFastestCache;

use WCML\Compatibility\ComponentFactory;
use WCML_WpFastest_Cache;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_WpFastest_Cache();
	}
}
