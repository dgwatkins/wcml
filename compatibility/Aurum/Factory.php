<?php

namespace WCML\Compatibility\Aurum;

use WCML\Compatibility\ComponentFactory;
use WCML_Aurum;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new WCML_Aurum();
	}
}
