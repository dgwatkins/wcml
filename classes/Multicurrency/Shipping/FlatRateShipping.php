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
		return self::isEnabled( $this->getWpOption( $instance->method_id, $instance->instance_id ) );
	}

	private function getWpOption( $method_id, $instance_id ) {
		if ( null === $this->wpOption ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $method_id, $instance_id );
			$this->wpOption = get_option( $option_name );
		}
		return $this->wpOption;
	}
}