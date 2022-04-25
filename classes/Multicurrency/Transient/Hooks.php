<?php

namespace WCML\Multicurrency\Transient;

use WCML\MultiCurrency\Settings as McSettings;

use function WCML\functions\getClientCurrency;

class Hooks {

	/**
	 * @var string
	 */
	private $key;

	/**
	 * @param string $key
	 */
	public function __construct( $key ) {
		$this->key = $key;
	}

	public function addHooks() {
		add_filter( 'pre_transient_' . $this->key, [ $this, 'getCurrencySpecificTransient' ] );
		add_filter( 'set_transient_' . $this->key, [ $this, 'setCurrencySpecificTransient' ], 10, 2 );
		add_action( 'delete_transient_' . $this->key, [ $this, 'deleteCurrencySpecificTransient' ] );
	}

	/**
	 * @return bool
	 */
	public function getCurrencySpecificTransient() {
		return get_transient( $this->getKeyWithCurrency() );
	}

	/**
	 * @param string $value
	 * @param string $expiration
	 *
	 * @return bool
	 */
	public function setCurrencySpecificTransient( $value, $expiration ) {
		delete_transient( $this->key );

		return set_transient( $this->getKeyWithCurrency(), $value, $expiration );
	}

	public function deleteCurrencySpecificTransient() {
		foreach ( McSettings::getActiveCurrencyCodes() as $code ) {
			delete_transient( $this->getKeyWithCurrency( $code ) );
		}
	}

	/**
	 * @param string|null $code
	 *
	 * @return string
	 */
	private function getKeyWithCurrency( $code = null ) {
		if ( null === $code ) {
			$code = getClientCurrency();
		}

		return $this->key . '_' . $code;
	}

}
