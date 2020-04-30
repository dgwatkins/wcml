<?php

namespace WCML\Multicurrency\Shipping;

class FreeShipping implements ShippingMode {
	use ShippingModeBase;

	public function getFieldTitle( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'Minimal order amount in %s',
			'The label for the field with minimal order amount in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getFieldDescription( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'The minimal order amount if customer choose %s as a purchase currency.',
			'The description for the field with minimal order amount in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getMethodId() {
		return 'free_shipping';
	}

	/**
	 * Returns minimal amount key for given currency.
	 *
	 * @param string $currencyCode Currency code.
	 *
	 * @return string
	 */
	private function getMinimalOrderAmountKey( $currencyCode ) {
		$currencyCode = is_string( $currencyCode ) ? $currencyCode : '';
		return sprintf( 'min_amount_%s', $currencyCode );
	}

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getMinimalOrderAmountKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		if ( $this->isManualPricingEnabled( $shipping ) ) {
			$key = $this->getMinimalOrderAmountKey( $currency );
			if ( isset( $shipping[ $key ] ) ) {
				$amount = $shipping[ $key ];
			}
		}
		return apply_filters( 'wcml_free_shipping_manual_min_amount', $amount, $shipping, $currency);
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getShippingCostValue
	 *
	 * @param array|object $rate
	 * @param string       $currency
	 *
	 * @return int|mixed|string
	 */
	public function getShippingCostValue( $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return $rate->cost;
	}

	public function isManualPricingEnabled( $instance ) {
		return is_array( $instance ) && self::isEnabled( $instance );
	}

	public function supportsShippingClasses() {
		return false;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getShippingClassCostValue
	 *
	 * @param array|object $rate
	 * @param string $currency
	 * @param string $shippingClassKey
	 *
	 * @return int|mixed|string
	 */
	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey ) {
		if ( ! $this->supportsShippingClasses() ) {
			throw new \Exception( 'Method should not be called because this class does not support shipping classes.' );
		}
		return 0;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getNoShippingClassCostValue
	 *
	 * @param array|object $rate
	 * @param string $currency
	 *
	 * @return int|mixed|string
	 */
	public function getNoShippingClassCostValue( $rate, $currency ) {
		if ( ! $this->supportsShippingClasses() ) {
			throw new \Exception( 'Method should not be called because this class does not support shipping classes.' );
		}
		return 0;
	}

	public function isManualPricingEnabled( $instance ) {
		return is_array( $instance ) && isset( $instance['wcml_shipping_costs'] ) && 'manual' === $instance['wcml_shipping_costs'];
	}
}