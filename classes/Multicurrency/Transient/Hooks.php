<?php

namespace WCML\Multicurrency\Transient;

use WCML\MultiCurrency\Settings as McSettings;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks as WpHooks;
use function WPML\FP\compose;
use function WPML\FP\spreadArgs;

class Hooks {

	/**
	 * @param string $key
	 */
	public static function addHooks( $key ) {
		$getKeyWithCurrency       = Str::concat( $key . '_' );
		$getKeyWithClientCurrency = compose( $getKeyWithCurrency, '\WCML\functions\getClientCurrency' );

		WpHooks::onFilter( 'pre_transient_' . $key )
			->then( compose( 'get_transient', $getKeyWithClientCurrency ) );

		WpHooks::onAction( 'set_transient_' . $key )
			->then( spreadArgs( function( $value ) use ( $key, $getKeyWithClientCurrency ) {
				global $settingTransient;

				$settingTransient = true;
				delete_transient( $key );
				$settingTransient = false;

				return set_transient( $getKeyWithClientCurrency(), $value );
			} ) );

		WpHooks::onAction( 'delete_transient_' . $key )
			->then( function() use ( $getKeyWithCurrency ) {
				global $settingTransient;

				if ( ! $settingTransient ) {
					foreach ( McSettings::getActiveCurrencyCodes() as $code ) {
						delete_transient( $getKeyWithCurrency( $code ) );
					}
				}
			} );
	}

}
