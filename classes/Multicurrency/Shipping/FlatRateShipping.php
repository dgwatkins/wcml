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
		if ( isset( $rate->method_id, $rate->instance_id ) ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $rate->method_id, $rate->instance_id );
			$cost_name = $this->getCostKey( $currency );
			$rate_settings = get_option( $option_name );
			if ( isset( $rate_settings[ $cost_name ] ) ) {
				return $rate_settings[ $cost_name ];
			}
		}
		return apply_filters( 'wcml_flat_rate_manual_cost', $rate->cost, $rate, $currency);
	}
}