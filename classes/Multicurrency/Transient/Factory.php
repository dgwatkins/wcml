<?php

namespace WCML\Multicurrency\Transient;

class Factory {

	/**
	 * @param string $key
	 */
	public static function create( $key ) {
		return new Hooks( $key );
	}

}
