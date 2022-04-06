<?php

namespace WCML\Compatibility\LiteSpeedCache;

use WCML\Compatibility\ComponentFactory;
use WCML_LiteSpeed_Cache;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_LiteSpeed_Cache();
	}
}
