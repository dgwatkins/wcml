<?php

namespace WCML\Compatibility\TheEventsCalendar;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new \WCML_The_Events_Calendar( getSitePress(), getWooCommerceWpml() );
	}
}
