<?php

namespace WCML\Multicurrency\Transient;

use WCML\MultiCurrency\Settings as McSettings;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks as WpHooks;
use function WCML\functions\getClientCurrency;
use function WPML\FP\spreadArgs;

class Hooks {

	/**
	 * @param string $key
	 */
	public static function addHooks( $key ) {
		$getKeyWithCurrency = Str::concat( $key . '_' );

		WpHooks::onFilter( 'pre_transient_' . $key )
			->then( function() use ( $getKeyWithCurrency ) {
				return get_transient( $getKeyWithCurrency( getClientCurrency() ) );
			} );

		WpHooks::onFilter( 'set_transient_' . $key )
			->then( spreadArgs( function( $value ) use ( $getKeyWithCurrency ) {
				return set_transient( $getKeyWithCurrency( getClientCurrency() ), $value );
			} ) );

		WpHooks::onAction( 'delete_transient_' . $key )
			->then( function() use ( $getKeyWithCurrency ) {
				foreach ( McSettings::getActiveCurrencyCodes() as $code ) {
					delete_transient( $getKeyWithCurrency( $code ) );
				}
			} );
	}

}
