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
		return $amount;
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
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
}