<?php

namespace WCML\Rest\Store;

use WCML\Rest\Functions;

class Hooks implements \IWPML_REST_Action {

	const BEFORE_REST_API_LOADED = 0;

	public function add_hooks() {
		if ( wcml_is_multi_currency_on() && Functions::isStoreAPIRequest() ) {
			add_action( 'init', [ $this, 'initializeSession' ], self::BEFORE_REST_API_LOADED );
		}
	}

	public function initializeSession() {
		if ( ! isset( WC()->session ) ) {
			WC()->initialize_session();
		}
	}

}
