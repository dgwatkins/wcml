<?php

namespace WCML\Compatibility\WcMixAndMatch;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getSitePress;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new \WCML_Mix_And_Match_Products( getSitePress() );
	}
}
