<?php

namespace WCML\User\Store;


class Store implements Strategy {

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return $this->getStrategy( $key )->get( $key );
	}

	/**
	 * @param string   $key
	 * @param mixed    $value
	 */
	public function set( $key, $value ) {
		$this->getStrategy( $key )->set( $key, $value );
	}

	/**
	 * @param string $key
	 *
	 * @return Strategy
	 */
	private function getStrategy( $key ) {
		/**
		 * This filter hook allows to override the storage strategy.
		 *
		 * @since 4.11.0
		 *
		 * @param string 'session' Storage strategy
		 * @param string $key      The key operating the storage
		 */
		switch ( apply_filters( 'wcml_user_store_strategy', 'session', $key ) ) {
			case 'cookie':
				$store = new Cookie();
				break;

			case 'session':
			default:
				$store = new WcSession();
		}

		return $store;
	}
}
