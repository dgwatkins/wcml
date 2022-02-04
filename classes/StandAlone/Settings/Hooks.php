<?php

namespace WCML\StandAlone\Settings;

use WCML\MultiCurrency\Settings;
use WCML\StandAlone\IStandAloneAction;
use WPML\LIB\WP\Hooks as WpHooks;

class Hooks implements IStandAloneAction, \IWPML_Backend_Action {

	public function add_hooks() {
		if ( Settings::isModeByLanguage() ) {
			$forceMultiCurrencyByLocation = function() {
				Settings::setMode( Settings::MODE_BY_LOCATION );
			};

			WpHooks::onAction( 'admin_init' )
				->then( $forceMultiCurrencyByLocation );
		}
	}
}
