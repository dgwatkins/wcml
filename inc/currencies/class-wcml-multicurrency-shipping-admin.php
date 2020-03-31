<?php

class WCML_Multi_Currency_Shipping_Admin {
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var WCML_Multi_Currency */
	private $wcml_multi_currency;

	public function __construct( WCML_Multi_Currency $wcml_multi_currency, woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wcml_multi_currency = $wcml_multi_currency;
	}

	public function add_hooks() {
		add_filter( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $this, 'woocommerce_shipping_instance_form_fields_flat_rate' ], 10, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_js' ] );
	}

	public function woocommerce_shipping_instance_form_fields_flat_rate( $field ) {
		$field = $this->add_enable_field( $field );
		$field = $this->add_currencies_fields( $field );
		return $field;
	}

	private function add_enable_field( $field ) {
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

	private function add_currencies_fields( $field ) {
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

	public function load_js() {
		wp_enqueue_script(
			'wcml-admin-shipping-currency-selector',
			$this->woocommerce_wpml->plugin_url() .
			'/res/js/wcml-admin-shipping-currency-selector' . $this->woocommerce_wpml->js_min_suffix() . '.js',
			[ 'jquery' ],
			$this->woocommerce_wpml->version(),
			true
		);
	}
}