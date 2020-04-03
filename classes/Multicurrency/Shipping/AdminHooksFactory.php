<?php

namespace WCML\Multicurrency\Shipping;

class AdminHooksFactory implements \IWPML_Backend_Action_Loader {

	public function create() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		return new AdminHooks( $woocommerce_wpml->get_multi_currency(), $woocommerce_wpml );
	}
}
