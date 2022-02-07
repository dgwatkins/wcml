<?php

namespace WCML\MultiCurrency;

use function WCML\functions\getSetting;
use function WCML\functions\isStandAlone;
use function WCML\functions\updateSetting;

class Settings {

	const MODE_BY_LANGUAGE = 'by_language';
	const MODE_BY_LOCATION = 'by_location';

	/**
	 * @return string|null
	 */
	public static function getMode() {
		$persistedMode = getSetting( 'currency_mode' );

		// Force location mode at runtime in standalone
		// to preserve settings in case of temporary change.
		if ( self::MODE_BY_LANGUAGE === $persistedMode && isStandAlone() ) {
			return self::MODE_BY_LOCATION;
		}

		return $persistedMode;
	}

	/**
	 * @return bool
	 */
	public static function isModeByLanguage() {
		return self::getMode() === self::MODE_BY_LANGUAGE;
	}

	/**
	 * @return bool
	 */
	public static function isModeByLocation() {
		return self::getMode() === self::MODE_BY_LOCATION;
	}

	/**
	 * @param string $mode
	 *
	 * @return void
	 */
	public static function setMode( $mode ) {
		updateSetting( 'currency_mode', $mode );
	}
}
