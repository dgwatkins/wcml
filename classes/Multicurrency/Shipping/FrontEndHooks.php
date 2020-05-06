<?php

namespace WCML\Multicurrency\Shipping;

use IWPML_Action;

class FrontEndHooks implements IWPML_Action {

	private $multiCurrency;

	public function __construct( $multiCurrency ) {
		$this->multiCurrency = $multiCurrency;
	}

	public function add_hooks() {
		ShippingModeProvider::getAll()->each( function( ShippingMode $shippingMode ) {
			add_filter(
				'woocommerce_shipping_' . $shippingMode->getMethodId() . '_instance_option',
				$this->getShippingCost( $shippingMode ),
				10,
				3
			);
		}
		);
	}

	public function getShippingCost( ShippingMode $shippingMode ) {
		return function( $rate, $key, $wcShippingMethod ) use ( $shippingMode ) {
			if ( $shippingMode->isManualPricingEnabled( $wcShippingMethod ) ) {
				if ( 'cost' === $key ) {
					$rate = $shippingMode->getShippingCostValue( $wcShippingMethod, $this->multiCurrency->get_client_currency() );
				} elseif ( $shippingMode->supportsShippingClasses() && 'class_cost_' === substr( $key, 0, 11 ) ) {
					$rate = $shippingMode->getShippingClassCostValue( $wcShippingMethod, $this->multiCurrency->get_client_currency(), $key );
				} elseif ( $shippingMode->supportsShippingClasses() && 'no_class_cost' === substr( $key, 0, 13 ) ) {
					$rate = $shippingMode->getNoShippingClassCostValue( $wcShippingMethod, $this->multiCurrency->get_client_currency() );
				}
			}
			return $rate;
		};
	}

}
