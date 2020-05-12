<?php

namespace WCML\Multicurrency\Shipping;

trait ShippingModeBase {
	private $multiCurrencyPrices;

	public function __construct( \WCML_Multi_Currency_Prices $multiCurrencyPrices ) {
		$this->multiCurrencyPrices = $multiCurrencyPrices;
	}

	/**
	 * @param array|object
	 *
	 * @return bool
	 */
	public static function isEnabled( $rate_settings ) {
		return isset( $rate_settings[ AdminHooks::WCML_SHIPPING_COSTS ] ) && 'manual' === $rate_settings[ AdminHooks::WCML_SHIPPING_COSTS ];
	}
}
