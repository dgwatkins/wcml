<?php

namespace WCML\Multicurrency\Shipping;

class AdminHooksFactory implements \IWPML_Deferred_Action_Loader, \IWPML_Backend_Action_Loader {

	public function get_load_action() {
		return 'init';
	}

	public function create() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		if ( wcml_is_multi_currency_on()
		     && $this->hasAdditionalCurrencyDefined()
		     && $this->isShippingPageRequest()
		) {
			return new AdminHooks( $woocommerce_wpml->get_multi_currency() );
		}

		return null;
	}

	/**
	 * Does user defined at least one additional currency in WCML.
	 *
	 * @return bool
	 */
	private function hasAdditionalCurrencyDefined() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		$available_currencies = $woocommerce_wpml->get_multi_currency()->get_currency_codes();

		return is_array( $available_currencies ) && count( $available_currencies ) > 1;
	}

	private function isShippingPageRequest() {
		return isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'shipping' === $_GET['tab']
		       || isset( $_GET['action'] ) && 'woocommerce_shipping_zone_methods_save_settings' === $_GET['action'];
	}
}