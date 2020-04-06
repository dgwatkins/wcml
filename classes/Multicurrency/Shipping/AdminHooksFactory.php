<?php

namespace WCML\Multicurrency\Shipping;

class AdminHooksFactory implements \IWPML_Deferred_Action_Loader, \IWPML_Backend_Action_Loader {

	public function get_load_action() {
		return 'init';
	}

	public function create() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		if ( $this->is_multicurrency_enabled() && $this->has_additional_currency_defined() ) {
			return new AdminHooks( $woocommerce_wpml->get_multi_currency() );
		}

		return null;
	}



	/**
	 * Is multicurrency feature enabled in WCML.
	 *
	 * @return bool
	 */
	private function is_multicurrency_enabled() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		return isset( $woocommerce_wpml->settings['enable_multi_currency'] )
			&& $woocommerce_wpml->settings['enable_multi_currency'] === WCML_MULTI_CURRENCIES_INDEPENDENT;
	}

	/**
	 * Does user defined at least one additional currency in WCML.
	 *
	 * @return bool
	 */
	private function has_additional_currency_defined() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		$available_currencies = $woocommerce_wpml->get_multi_currency()->get_currency_codes();

		return is_array( $available_currencies ) && count( $available_currencies ) > 1;
	}
}
