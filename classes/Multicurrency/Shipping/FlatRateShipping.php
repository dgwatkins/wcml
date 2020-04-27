<?php

namespace WCML\Multicurrency\Shipping;

class FlatRateShipping implements ShippingMode {
	protected $methodId = 'flat_rate';
	protected $costKeyPattern = 'cost_%s';

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
		return $this->methodId;
	}

	public function getCostKey( $currencyCode ) {
		return sprintf( $this->costKeyPattern, $currencyCode );
	}
}