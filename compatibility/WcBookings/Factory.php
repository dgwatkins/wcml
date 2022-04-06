<?php

namespace WCML\Compatibility\WcBookings;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		$bookings = new \WCML_Bookings(
			getSitePress(),
			getWooCommerceWpml(),
			self::getWooCommerce(),
			self::getWpdb(),
			self::getElementTranslationPackage(),
			self::getPostTranslations()
		);

		$hooks = [
			$bookings,
		];

		if ( defined( 'WC_ACCOMMODATION_BOOKINGS_VERSION' ) ) {
			$hooks[] = new \WCML_Accommodation_Bookings( getWooCommerceWpml(), $bookings );
		}

		return $hooks;
	}
}
