<?php

namespace WCML\Multicurrency\Shipping;

class FlatRateShipping implements ShippingMode {
	use ShippingModeBase;
	use VariableCost;

	public function getMethodId() {
		return 'flat_rate';
	}

	public function supportsShippingClasses() {
		return true;
	}

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getCostKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return apply_filters( 'wcml_flat_rate_manual_min_amount', $amount, $shipping, $currency);
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
		return apply_filters( 'wcml_flat_rate_manual_cost', $rate->cost, $rate, $currency);
	}

	public function isManualPricingEnabled( $instance ) {
		$rate_settings = $this->getWpOption( $instance->method_id, $instance->instance_id );
		return isset( $rate_settings['wcml_shipping_costs'] ) && 'manual' === $rate_settings['wcml_shipping_costs'];
	}

	private function getWpOption( $method_id, $instance_id ) {
		if ( null === self::$wpOption ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $method_id, $instance_id );
			self::$wpOption = get_option( $option_name );
		}
		return self::$wpOption;
	}
}