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
				} elseif ( $shippingMode instanceof ShippingClassesMode ) {
					if ( $this->isShippingClass( $key ) ) {
						$rate = $shippingMode->getShippingClassCostValue( $wcShippingMethod, $this->multiCurrency->get_client_currency(), $key );
					} elseif ( $this->isNoShippingClass( $key ) ) {
						$rate = $shippingMode->getNoShippingClassCostValue( $wcShippingMethod, $this->multiCurrency->get_client_currency() );
					}
				}
			}
			return $rate;
		};
	}

	private function isShippingClass( $key ) {
		return 'class_cost_' === substr( $key, 0, 11 );
	}

	private function isNoShippingClass( $key ) {
		return 'no_class_cost' === substr( $key, 0, 13 );
	}

}
