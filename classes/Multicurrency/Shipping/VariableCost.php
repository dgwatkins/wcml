<?php

namespace WCML\Multicurrency\Shipping;

trait VariableCost {
	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getFieldTitle
	 *
	 * @param string $currencyCode
	 *
	 * return string
	 */
	public function getFieldTitle( $currencyCode ) {
		return sprintf( esc_html_x( 'Cost in %s',
			'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	private $wpOption = null;

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getFieldDescription
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getFieldDescription( $currencyCode ) {
		return sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
			'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	/**
	 * Returns cost key for given currency.
	 *
	 * @param string $currencyCode Currency code.
	 *
	 * @return string
	 */
	private function getCostKey( $currencyCode ) {
		return sprintf( 'cost_%s', $currencyCode );
	}

	private function getShippingClassCostKey( $shippingClassKey, $currency ) {
		return $shippingClassKey . '_' . $currency;
	}

	private function getNoShippingClassCostKey( $currency ) {
		return 'no_class_cost_' . $currency;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getSettingsFormKey
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getSettingsFormKey( $currencyCode ) {
		return $this->getCostKey( $currencyCode );
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getMinimalOrderAmountValue
	 *
	 * @param integer|float|string $amount   The value as saved for original language.
	 * @param array                $shipping The shipping metadata.
	 * @param string               $currency Currency code.
	 *
	 * @return mixed
	 */
	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	/**
	 * @param array|object $rate
	 * @param string $currency
	 *
	 * @return int|mixed|string
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getShippingCostValue
	 *
	 */
	public function getShippingCostValue( $rate, $currency ) {
		$costName = $this->getCostKey( $currency );
		return $this->getCostValueForName( $rate, $currency, $costName );
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingClassesMode::getShippingClassCostValue
	 *
	 * @param array|object $rate
	 * @param string       $currency
	 * @param string       $shippingClassKey
	 *
	 * @return int Shipping class cost for given currency.
	 */
	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey ) {
		$costName = $this->getShippingClassCostKey( $shippingClassKey, $currency );
		return $this->getCostValueForName( $rate, $currency, $costName );
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingClassesMode::getNoShippingClassCostValue
	 *
	 * @param array|object $rate
	 * @param string       $currency
	 *
	 * @return int "No shipping class" cost for given currency.
	 */
	public function getNoShippingClassCostValue( $rate, $currency ) {
		$costName = $this->getNoShippingClassCostKey( $currency );
		return $this->getCostValueForName( $rate, $currency, $costName );
	}

	private function getCostValueForName( $rate, $currency, $costName ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		if ( isset( $rate->instance_id ) ) {
			if ( $this->isManualPricingEnabled( $rate ) ) {
				$rateSettings = $this->getWpOption( $this->getMethodId(), $rate->instance_id );
				if ( ! empty( $rateSettings[ $costName ] ) ) {
					$rate->cost = $rateSettings[ $costName ];
				} else {
					$rate->cost = $this->getCostForDefaultCurrency( $rate->cost, $rateSettings, $costName, $currency );
				}
			}
		}
		return $rate->cost;
	}

	/**
	 * Searches settings for the cost with name without currency code at the end.
	 *
	 * @param mixed  $cost         Filtered cost.
	 * @param array  $rateSettings Shipping cost array taken from get_option.
	 * @param string $costName     Cost name with currency code at the end.
	 * @param string $currencyCode Currency code.
	 *
	 * @return mixed
	 */
	private function getCostForDefaultCurrency( $cost, $rateSettings, $costName, $currencyCode ) {
		$currencyAppendix = '_' . $currencyCode;
		$currencyAppendixLength = strlen( $currencyAppendix );
		if ( substr($costName, -$currencyAppendixLength) === $currencyAppendix ) {
			$defaultCostName = substr( $costName, 0, -$currencyAppendixLength );
			if ( ! empty( $rateSettings[ $defaultCostName ] ) ) {
				$cost = $this->multiCurrencyPrices->raw_price_filter( $rateSettings[ $defaultCostName ], $currencyCode );
			}
		}
		return $cost;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::isManualPricingEnabled
	 *
	 * @param \WC_Shipping_Rate $instance
	 *
	 * @return mixed
	 */
	public function isManualPricingEnabled( $instance ) {
		return self::isEnabled( $this->getWpOption( $this->getMethodId(), $instance->instance_id ) );
	}

	/**
	 * Returns shipping data from wp_options table.
	 *
	 * @param string $methodId
	 * @param int    $instanceId
	 *
	 * @return bool|mixed|void|null
	 */
	private function getWpOption( $methodId, $instanceId ) {
		if ( null === $this->wpOption ) {
			$optionName = sprintf( 'woocommerce_%s_%d_settings', $methodId, $instanceId );
			$this->wpOption = get_option( $optionName );
		}
		return $this->wpOption;
	}
}
