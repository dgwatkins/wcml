<?php

namespace WCML\Compatibility\WcBookings;

class Prices implements \IWPML_Action {

	public function add_hooks() {
		add_filter( 'wcml_product_has_custom_prices', [ $this, 'checkCustomCosts' ], 10, 2 );
	}

	/**
	 * @param bool $check
	 * @param int  $productId
	 *
	 * @return bool
	 */
	public function checkCustomCosts( $check, $productId ) {
		if ( ! $check ) {
			$product = wc_get_product( $productId );
			if ( $product && 'booking' === $product->get_type() ) {
				$check = get_post_meta( $productId, '_wcml_custom_costs_status', true );
			}
		}

		return $check;
	}

}
