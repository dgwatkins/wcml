<?php

namespace WCML\Utilities;

use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WCML\functions\isStandAlone;

class AdminPages {

	/**
	 * @param string|array $tabs A single tab (string) or one of multiple tabs (array).
	 *
	 * @return bool
	 */
	public static function isTab( $tabs ) {
		return Lst::includes( Obj::prop( 'tab', $_GET ), (array) $tabs );
	}

	/**
	 * @param string $page
	 *
	 * @return bool
	 */
	public static function isPage( $page ) {
		return Relation::propEq( 'page', $page, $_GET );
	}

	/**
	 * @return bool
	 */
	public static function isWcmlSettings() {
		return self::isPage( 'wpml-wcml' );
	}

	/**
	 * @return bool
	 */
	public static function isMultiCurrency() {
		$tabs = [ 'multi-currency' ];

		if ( isStandAlone() ) {
			$tabs[] = null; // Also the default tab in Standalone mode.
		}

		return self::isWcmlSettings() && self::isTab( $tabs );
	}

	/**
	 * @return bool
	 */
	public static function isTranslationQueue() {
		return ! isStandAlone() && self::isPage( WPML_TM_FOLDER . '/menu/translations-queue.php' );
	}

	/**
	 * @return bool
	 */
	public static function isTranslationsDashboard() {
		return ! isStandAlone() && self::isPage( WPML_TM_FOLDER . '/menu/main.php' );
	}
}
