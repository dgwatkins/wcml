<?php

namespace WCML\Multicurrency\Shipping;

class FreeShipping extends IGeneralShipping {
	protected $methodId = 'free_shipping';

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
}