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
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getShippingCostValue
	 *
	 * @param $rate
	 * @param string            $currency
	 *
	 * @return int|mixed|string
	 */
	public function getShippingCostValue( $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		if ( isset( $rate->instance_id ) ) {
			if ( $this->isManualPricingEnabled( $rate ) ) {
				$rateSettings = $this->getWpOption( $this->getMethodId(), $rate->instance_id );
				$costName = $this->getCostKey( $currency );
				if ( isset( $rateSettings[ $costName ] ) ) {
					$rate->cost = $rateSettings[ $costName ];
				}
			}
		}
		return $rate->cost;
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
