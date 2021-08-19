<?php

namespace WCML\Options;

use WPML\Setup\Option;

class WPML {

	/** @return bool */
	public static function shouldTranslateEverything() {
		return method_exists( Option::class, 'shouldTranslateEverything' )
		       && Option::shouldTranslateEverything();
	}
}
