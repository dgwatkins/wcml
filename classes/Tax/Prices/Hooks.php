<?php

namespace WCML\Tax\Prices;

use IWPML_Backend_Action;
use IWPML_DIC_Action;
use IWPML_Frontend_Action;
use WC_Shipping_Rate;
use WCML_Multi_Currency_Shipping;
use woocommerce_wpml;
use WPML\FP\Obj;

/**
 * Hooks applied with enabled multicurrency mode & taxes
 */
class Hooks implements IWPML_Frontend_Action, IWPML_Backend_Action, IWPML_DIC_Action {
	/** @var woocommerce_wpml  */
	private $wcml;

	public function __construct( woocommerce_wpml $wcml ) {
		$this->wcml = $wcml;
	}

	public function add_hooks() {
		if ( wcml_is_multi_currency_on() ) {
			add_filter( 'woocommerce_get_price_excluding_tax', [ $this, 'applyRoundingRules' ] );
			add_filter( 'woocommerce_get_price_including_tax', [ $this, 'applyRoundingRules' ] );
			add_filter( 'woocommerce_shipping_packages', [ $this, 'applyShippingRoundingRules' ], WCML_Multi_Currency_Shipping::PRIORITY_SHIPPING + 1 );
		}
	}

	/**
	 * @param float $price
	 * @return int|float
	 */
	public function applyRoundingRules( $price ) {
		$multiCurrency = $this->wcml->get_multi_currency();
		$currency      = $multiCurrency->get_client_currency();

		if ( $this->isRoundingEnabled( $currency ) ) {
			return $multiCurrency->prices->apply_rounding_rules( $price, $currency );
		}
		return $price;
	}

	/**
	 * @param array $packages
	 * @return array
	 */
	public function applyShippingRoundingRules( $packages ) {
		foreach ( $packages as $index => $package ) {
			$package            = $this->applySubtotalRoundingRules( $package );
			$package            = $this->applyRatesRoundingRules( $package );
			$packages[ $index ] = $package;
		}
		return $packages;
	}

	private function applySubtotalRoundingRules( $package ) {
		if ( ! empty( $package['cart_subtotal'] ) ) {
			$package['cart_subtotal'] = $this->applyRoundingRules( $package['cart_subtotal'] );
		}
		return $package;
	}

	/**
	 * @param array $package
	 * @return array $package
	 */
	private function applyRatesRoundingRules( $package ) {
		foreach ( $package['rates'] as $key => $rate ) {
			/** @var WC_Shipping_Rate $rate */
			$rate->taxes              = $this->applyTaxRoundingRules( $rate->taxes );
			$rate->cost               = $this->applyRoundingRules( $rate->cost );
			$package['rates'][ $key ] = $rate;
		}
		return $package;
	}

	/**
	 * @param array $taxes
	 * @return array $taxes
	 */
	private function applyTaxRoundingRules( $taxes ) {
		foreach ( $taxes as $index => $tax ) {
			$taxes[ $index ] = $this->applyRoundingRules( $tax );
		}
		return $taxes;
	}

	/**
	 * @param string $currency
	 * @return bool
	 */
	private function isRoundingEnabled( $currency ) {
		$currencyOptions    = $this->wcml->get_setting( 'currency_options' );
		$getRoundingSetting = Obj::path( [ $currency, 'rounding' ] );
		return $getRoundingSetting( $currencyOptions ) !== 'disabled';
	}
}
