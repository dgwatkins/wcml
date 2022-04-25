<?php

namespace WCML\Multicurrency\Transient;

use WCML\MultiCurrency\Settings as McSettings;
use WCML\Utilities\InMemoryLock;
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

		$lock = new InMemoryLock();

		WpHooks::onFilter( 'pre_transient_' . $key )
			->then( compose( 'get_transient', $getKeyWithClientCurrency ) );

		WpHooks::onAction( 'set_transient_' . $key )
			->then( spreadArgs( function( $value ) use ( $key, $getKeyWithClientCurrency, &$lock ) {
				$lock->lock();
				delete_transient( $key );
				$lock->release();

				return set_transient( $getKeyWithClientCurrency(), $value );
			} ) );

		WpHooks::onAction( 'delete_transient_' . $key )
			->then( function() use ( $getKeyWithCurrency, &$lock ) {
				if ( ! $lock->isLocked() ) {
					foreach ( McSettings::getActiveCurrencyCodes() as $code ) {
						delete_transient( $getKeyWithCurrency( $code ) );
					}
				}
			} );
	}

}
