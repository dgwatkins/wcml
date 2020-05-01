<?php

namespace WCML\Multicurrency\Shipping;

class ShippingClasses {
	/**
	 * Adds shipping classes for currencies fields to shipping method wp-admin GUI.
	 *
	 * @param array               $field
	 * @param \WCML_Multi_Currency $wcmlMultiCurrency
	 *
	 * @return array
	 */
	public static function addFields( array $field, \WCML_Multi_Currency $wcmlMultiCurrency ) {
		$shippingClasses = WC()->shipping()->get_shipping_classes();
		if ( ! empty( $shippingClasses ) ) {
			foreach ( $wcmlMultiCurrency->get_currency_codes() as $currencyCode ) {
				if ( $wcmlMultiCurrency->get_default_currency() === $currencyCode ) {
					continue;
				}
				foreach ( $shippingClasses as $shippingClass ) {
					$field = self::addShippingClassField( $field, $shippingClass, $currencyCode );
				}
				$field = self::addNoShippingClassField( $field, $currencyCode );
			}
		}
		return $field;
	}

	protected static function addShippingClassField( $field, $shippingClass, $currencyCode ) {
		$field[ 'class_cost_' . $shippingClass->term_id . '_' . $currencyCode ] = [
			'title'             => sprintf( __( '"%s" shipping class cost in %s', 'woocommerce-multilingual' ), esc_html( $shippingClass->name ), esc_html( $currencyCode ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce-multilingual' ),
			'class' => 'wcml-shipping-cost-currency'
		];
		return $field;
	}

	protected static function addNoShippingClassField( $field, $currencyCode ) {
		$field[ 'no_class_cost_' . $currencyCode ] = [
			'title'             => sprintf( __( 'No shipping class cost in %s', 'woocommerce-multilingual' ), esc_html( $currencyCode ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce-multilingual' ),
			'default'           => '',
			'class' => 'wcml-shipping-cost-currency'
		];
		return $field;
	}
}