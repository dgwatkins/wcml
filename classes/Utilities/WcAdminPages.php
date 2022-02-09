<?php

namespace WCML\Utilities;

use WPML\FP\Lst;
use WPML\FP\Obj;

class WcAdminPages {

	const SECTION_BACS = 'bacs';

	/**
	 * @param string|array $sections A single section (string) or one of multiple sections (array).
	 *
	 * @return bool
	 */
	public static function isSection( $sections ) {
		return Lst::includes( Obj::prop( 'section', $_GET ), (array) $sections );
	}

	/**
	 * @return bool
	 */
	private static function isSettingsPage() {
		global $pagenow;

		return is_admin() && 'admin.php' === $pagenow && AdminPages::isPage( 'wc-settings' );
	}

	/**
	 * @return bool
	 */
	public static function isPaymentSettings( ) {
		return self::isSettingsPage() &&  AdminPages::isTab( 'checkout' );
	}
}
