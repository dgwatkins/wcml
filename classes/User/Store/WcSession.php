<?php

namespace WCML\User\Store;


class WcSession implements Strategy {

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {

		return ( new \WC_Session_Handler() )->get( $key );
	}

	/**
	 * @param string   $key
	 * @param mixed    $value
	 */
	public function set( $key, $value ) {

		( new \WC_Session_Handler() )->set( $key, $value );
	}
}
