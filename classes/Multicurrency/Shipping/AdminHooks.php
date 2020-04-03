<?php

namespace WCML\Multicurrency\Shipping;

use IWPML_Action;
use WCML_Multi_Currency;

class AdminHooks implements IWPML_Action {

	/** @var WCML_Multi_Currency */
	private $wcml_multi_currency;

	/**
	 * WCML_Multi_Currency_Shipping_Admin constructor.
	 *
	 * @param WCML_Multi_Currency $wcml_multi_currency
	 */
	public function __construct( WCML_Multi_Currency $wcml_multi_currency ) {
		$this->wcml_multi_currency = $wcml_multi_currency;
	}

	/**
	 * Registers hooks.
	 */
	public function add_hooks() {
		add_filter( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $this, 'add_currency_shipping_fields_to_shipping_method_form' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_js' ] );
	}

	/**
	 * Adds fields to display screen for flat rate shipping method.
	 *
	 * Adds two kind of fields:
	 * - The select field to enable/disable shipping costs in other currencies.
	 * @see \WCML_Multi_Currency_Shipping_Admin::add_enable_field
	 * - The input field for each registered currency to provide shipping costs.
	 * @see \WCML_Multi_Currency_Shipping_Admin::add_currencies_fields
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function add_currency_shipping_fields_to_shipping_method_form( array $field ) {
		$field = $this->add_enable_field( $field );
		$field = $this->add_currencies_fields( $field );

		return $field;
	}

	/**
	 * Adds select field to enable/disable shipping costs in other currencies.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function add_enable_field( array $field ) {
		$enable_field = [
			'title' => esc_html__( 'Enable costs in custom currencies', 'woocommerce-multilingual' ),
			'type' => 'select',
			'class' => 'wcml-enable-shipping-custom-currency',
			'default' => 'auto',
			'options' => [
				'auto' => esc_html__( 'Calculate shipping costs in other currencies automatically', 'woocommerce-multilingual' ),
				'manual' => esc_html__( 'Set shipping costs in other currencies manually', 'woocommerce-multilingual' )
			]
		];
		$field['wcml_shipping_costs'] = $enable_field;

		return $field;
	}

	/**
	 * Adds input field for each registered currency to provide shipping costs.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function add_currencies_fields( array $field ) {
		foreach ( $this->wcml_multi_currency->get_currency_codes() as $currency_code ) {
			if ( $this->wcml_multi_currency->get_default_currency() === $currency_code ) {
				continue;
			}
			$field_key = sprintf( 'cost_%s', $currency_code );
			$field_value = [
				'title' => sprintf( esc_html_x( 'Cost in %s',
					'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
					'woocommerce-multilingual' ), $currency_code ),
				'type' => 'text',
				'description' => sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
					'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
					'woocommerce-multilingual' ), $currency_code ),
				'default' => '0',
				'desc_tip' => true,
				'class' => 'wcml-shipping-cost-currency'
			];

			$field[ $field_key] = $field_value;
		}

		return $field;
	}

	/**
	 * Enqueues script responsible for JS actions on shipping fields.
	 */
	public function load_js() {
		wp_enqueue_script(
			'wcml-admin-shipping-currency-selector',
			constant( 'WCML_PLUGIN_URL' ) . '/dist/js/multicurrencyShippingAdmin/app.js',
			[ 'jquery' ],
			constant( 'WCML_VERSION' ),
			true
		);
	}
}