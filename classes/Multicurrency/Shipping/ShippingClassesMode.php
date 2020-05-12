<?php

namespace WCML\Multicurrency\Shipping;

interface ShippingClassesMode extends ShippingMode {
	/**
	 * @param array|object               $rate
	 * @param string                     $currency
	 * @param string                     $shippingClassKey
	 * @param WCML_Multi_Currency_Prices $multiCurrencyPrices
	 *
	 * @return int|mixed|string Shipping class cost for given currency.
	 */
	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey, $multiCurrencyPrices );

	/**
	 * @param array|object               $rate
	 * @param string                     $currency
	 * @param WCML_Multi_Currency_Prices $multiCurrencyPrices
	 *
	 * @return int|mixed|string "No shipping class" cost for given currency.
	 */
	public function getNoShippingClassCostValue( $rate, $currency, $multiCurrencyPrices );
}
