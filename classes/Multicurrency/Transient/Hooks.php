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
				if ( wp_using_ext_object_cache() || wp_installing() ) {
					wp_cache_delete( $key, 'transient' );
				} else {
					$option_timeout = '_transient_timeout_' . $key;
					$option         = '_transient_' . $key;
					$result         = delete_option( $option );
					if ( $result ) {
						delete_option( $option_timeout );
					}
				}

				return set_transient( $getKeyWithClientCurrency(), $value );
			} ) );

		WpHooks::onAction( 'delete_transient_' . $key )
			->then( function() use ( $getKeyWithCurrency ) {
				foreach ( McSettings::getActiveCurrencyCodes() as $code ) {
					delete_transient( $getKeyWithCurrency( $code ) );
				}
			} );
	}

}
