<?php

namespace WCML\Multicurrency\Shipping;

use IWPML_Action;
use WCML_Multi_Currency;

class AdminHooks implements IWPML_Action {

	/** @var WCML_Multi_Currency */
	private $wcmlMultiCurrency;

	/**
	 * @var AbstractShipping
	 */
	private $shippingType;

	/**
	 * AdminHooks constructor.
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
		add_filter( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $this, 'addCurrencyShippingFieldsToFlatRate' ], 10, 1 );
		add_filter( 'woocommerce_shipping_instance_form_fields_free_shipping', [ $this, 'addCurrencyShippingFieldsToFreeShipping' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'loadJs' ] );
	}

	/**
	 * Instantiate flat rate shipping method class and add field to the method GUI.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function addCurrencyShippingFieldsToFlatRate( array $field ) {
		$this->shippingType = new FlatRateShipping();
		return $this->addCurrencyShippingFieldsToShippingMethodForm( $field );
	}

	/**
	 * Instantiate free shipping method class and add field to the method GUI.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function addCurrencyShippingFieldsToFreeShipping( array $field ) {
		$this->shippingType = new FreeShipping();
		return $this->addCurrencyShippingFieldsToShippingMethodForm( $field );
	}

	/**
	 * Adds fields to display screen for shipping method.
	 *
	 * Adds two kind of fields:
	 * - The select field to enable/disable shipping costs in other currencies.
	 * @see \AdminHooks::add_enable_field
	 * - The input field for each registered currency to provide shipping costs.
	 * @see \AdminHooks::add_currencies_fields
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function addCurrencyShippingFieldsToShippingMethodForm( array $field ) {
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
	public function addCurrenciesFields( array $field ) {
		foreach ( $this->wcmlMultiCurrency->get_currency_codes() as $currencyCode ) {
			if ( $this->wcmlMultiCurrency->get_default_currency() === $currencyCode ) {
				continue;
			}
			$fieldKey = sprintf( 'cost_%s', $currencyCode );
			$fieldValue = [
				'title' => $this->shippingType->getFieldTitle( $currencyCode ),
				'type' => 'text',
				'description' => $this->shippingType->getFieldDescription( $currencyCode ),
				'default' => '0',
				'desc_tip' => true,
				'class' => 'wcml-shipping-cost-currency'
			];

			$field[ $fieldKey] = $fieldValue;
		}
		return $field;
	}

	/**
	 * Returns shipping cost key for given currency as is in shipping options.
	 *
	 * @param $currency
	 * @param $method_id
	 *
	 * @return string|null
	 */
	public static function getCostKey( $currency, $method_id ) {
		$patterns = [
			'flat_rate' => 'cost_%s',
			'free_shipping' => 'min_amount_%s',
		];
		if ( isset( $patterns[ $method_id ] ) ) {
			return sprintf( $patterns[ $method_id ], $currency );
		}
		return null;
	}

	/**
	 * Enqueues script responsible for JS actions on shipping fields.
	 */
	public function loadJs() {
		wp_enqueue_script(
			'wcml-admin-shipping-currency-selector',
			constant( 'WCML_PLUGIN_URL' ) . '/dist/js/multicurrencyShippingAdmin/app.js',
			[],
			constant( 'WCML_VERSION' ),
			true
		);
	}
}