<?php

namespace WCML\Multicurrency\Transient;

use WCML\MultiCurrency\Settings as McSettings;
use WPML\FP\Fns;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks as WpHooks;
use function WCML\functions\getClientCurrency;
use function WPML\FP\compose;
use function WPML\FP\spreadArgs;

class Hooks {

	/**
	 * @param string $key
	 */
	public static function addHooks( $key ) {
		$getKeyWithCurrency       = Str::concat( $key . '_' );
		$getKeyWithClientCurrency = function() use ( $getKeyWithCurrency ) {
			return $getKeyWithCurrency( getClientCurrency() );
		};

		WpHooks::onFilter( 'pre_transient_' . $key )
			->then( compose( 'get_transient', $getKeyWithClientCurrency ) );

		WpHooks::onAction( 'set_transient_' . $key )
			->then( spreadArgs( function( $value ) use ( $key, $getKeyWithClientCurrency ) {
				$true = Fns::always( true );

				add_filter( 'wcml_multi_currency_is_saving_transient', $true );
				delete_transient( $key );
				remove_filter( 'wcml_multi_currency_is_saving_transient', $true );

				return set_transient( $getKeyWithClientCurrency(), $value );
			} ) );

		WpHooks::onAction( 'delete_transient_' . $key )
			->then( function() use ( $getKeyWithCurrency ) {
				if ( ! apply_filters( 'wcml_multi_currency_is_saving_transient', false ) ) {
					foreach ( McSettings::getActiveCurrencyCodes() as $code ) {
						delete_transient( $getKeyWithCurrency( $code ) );
					}
				}
			} );
	}

}
