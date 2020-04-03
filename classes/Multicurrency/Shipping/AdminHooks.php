<?php

namespace WCML\Multicurrency\Shipping;

use IWPML_Action;
use WCML_Multi_Currency;

class AdminHooks implements IWPML_Action {

	/** @var WCML_Multi_Currency */
	private $wcmlMultiCurrency;

	/**
	 * WCML_Multi_Currency_Shipping_Admin constructor.
	 *
	 * @param WCML_Multi_Currency $wcmlMultiCurrency
	 */
	public function __construct( WCML_Multi_Currency $wcmlMultiCurrency ) {
		$this->wcmlMultiCurrency = $wcmlMultiCurrency;
	}

	/**
	 * Registers hooks.
	 */
	public function add_hooks() {
		add_filter( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $this, 'addCurrencyShippingFieldsToShippingMethodForm' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'loadJs' ] );
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
	public function addCurrencyShippingFieldsToShippingMethodForm( array $field ) {
		$field = $this->addEnableField( $field );
		$field = $this->addCurrenciesFields( $field );

		return $field;
	}

	/**
	 * Adds select field to enable/disable shipping costs in other currencies.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function addEnableField( array $field ) {
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
	private function addCurrenciesFields( array $field ) {
		foreach ( $this->wcmlMultiCurrency->get_currency_codes() as $currencyCode ) {
			if ( $this->wcmlMultiCurrency->get_default_currency() === $currencyCode ) {
				continue;
			}
			$fieldKey = sprintf( 'cost_%s', $currencyCode );
			$fieldValue = [
				'title' => sprintf( esc_html_x( 'Cost in %s',
					'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
					'woocommerce-multilingual' ), $currencyCode ),
				'type' => 'text',
				'description' => sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
					'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
					'woocommerce-multilingual' ), $currencyCode ),
				'default' => '0',
				'desc_tip' => true,
				'class' => 'wcml-shipping-cost-currency'
			];

			$field[ $fieldKey] = $fieldValue;
		}

		return $field;
	}

	/**
	 * Enqueues script responsible for JS actions on shipping fields.
	 */
	public function loadJs() {
		wp_enqueue_script(
			'wcml-admin-shipping-currency-selector',
			constant( 'WCML_PLUGIN_URL' ) . '/dist/js/multicurrencyShippingAdmin/app.js',
			[ 'jquery' ],
			constant( 'WCML_VERSION' ),
			true
		);
	}
}