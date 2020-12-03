<?php

namespace WCML\User\Store;


class Cookie implements Strategy {

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {

		return ( new \WPML_Cookie() )->get_cookie( $key );
	}

	/**
	 * @param string   $key
	 * @param mixed    $value
	 */
	public function set( $key, $value ) {

		$cookieHandler = new \WPML_Cookie();

		if ( ! $cookieHandler->headers_sent() ) {

			$expiration = time() + (int) apply_filters( 'wcml_cookie_expiration', 60 * 60 * 48 ); // 48 Hours.

			$cookieHandler->set_cookie( $key, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
		}
	}
}
