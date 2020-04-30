<?php

namespace WCML\Multicurrency\Shipping;

class FlatRateShipping implements ShippingMode {
	private $wpOption = null;

	public function getFieldTitle( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'Cost in %s',
			'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getFieldDescription( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
			'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getMethodId() {
		return 'flat_rate';
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

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getCostKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	public function getShippingCostValue( \WC_Shipping_Rate $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		if ( isset( $rate->method_id, $rate->instance_id ) ) {
			if ( $this->isManualPricingEnabled( $rate ) ) {
				$rate_settings = $this->getWpOption( $rate->method_id, $rate->instance_id );
				$cost_name = $this->getCostKey( $currency );
				if ( isset( $rate_settings[ $cost_name ] ) ) {
					$rate->cost = $rate_settings[ $cost_name ];
				}
			}
		}
		return $rate->cost;
	}

	public function isManualPricingEnabled( $instance ) {
		$rate_settings = $this->getWpOption( $instance->method_id, $instance->instance_id );
		return isset( $rate_settings['wcml_shipping_costs'] ) && 'manual' === $rate_settings['wcml_shipping_costs'];
	}

	private function getWpOption( $method_id, $instance_id ) {
		if ( null === $this->wpOption ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $method_id, $instance_id );
			$this->wpOption = get_option( $option_name );
		}
		return $this->wpOption;
	}
}