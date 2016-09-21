<?php

/**
 * Class Test_WCML_Table_Rate_Shipping
 */
class Test_WCML_Table_Rate_Shipping extends WCML_UnitTestCase {

	public $default_language;
	public $second_language;

	public function setUp() {
		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$active_languages = $this->sitepress->get_active_languages();
		unset( $active_languages[ $this->default_language ] );
		$this->second_language = array_rand( $active_languages );

		wpml_test_reg_custom_taxonomy( 'product_shipping_class', false, true );
	}

	/**
	 * @test
	 */
	public function init() {
		$rate_title = random_string();
		$label_1 = random_string();
		$label_2 = random_string();
		$label_3 = random_string();
		$_GET['page'] = 'shipping_zones';
		$_POST['shipping_label'] = array( $label_1, $label_2, $label_3 );
		$_POST['woocommerce_table_rate_title'] = $rate_title;

		$domain = 'woocommerce';
		$context = '';

		$table_rate = new WCML_Table_Rate_Shipping( $this->sitepress, $this->woocommerce_wpml );
		$table_rate->init();

		$expected = array(
			$rate_title,
			$label_1,
			$label_2,
			$label_3,
		);

		foreach ( $expected as $registered_string ) {
			$name = sanitize_text_field( $registered_string ) . '_shipping_method_title';

			$query = $this->wpdb->prepare( "SELECT id, value, gettext_context, name
										FROM {$this->wpdb->prefix}icl_strings
										WHERE context=%s AND name=%s", $domain, $name );
			$res   = $this->wpdb->get_row( $query );

			$this->assertEquals( $registered_string, $res->value );
			$this->assertEquals( $registered_string . '_shipping_method_title', $res->name );
		}
	}

	/**
	 * @test
	 */
	public function shipping_class_id_in_default_language() {
		$shipping_class = wpml_test_insert_term( $this->default_language, 'product_shipping_class', false, 'shipping_class' . $this->default_language );
		$trid = $this->sitepress->get_element_trid( $shipping_class['term_id'], 'tax_product_shipping_class' );
		$tr_shipping_class = wpml_test_insert_term( $this->second_language, 'product_shipping_class', $trid, 'shipping_class' . $this->second_language );


		$terms = array(
			$tr_shipping_class['term_id'] => get_term( $tr_shipping_class['term_id'], 'product_shipping_class' ),
		);

		$table_rate = new WCML_Table_Rate_Shipping( $this->sitepress, $this->woocommerce_wpml );
		$this->assertEquals( $terms, $table_rate->shipping_class_id_in_default_language( $terms, null, 'category' ) );

		$expected = array(
			$tr_shipping_class['term_id'] => get_term( $shipping_class['term_id'], 'product_shipping_class' ),
		);
		$this->assertEquals( $expected, $table_rate->shipping_class_id_in_default_language( $terms, null, 'product_shipping_class' ) );
	}

	/**
	 * @test
	 */
	public function filter_query_rates_args(){

		$settings_backup = $this->woocommerce_wpml->settings;
		$this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
		$this->multi_currency_helper->enable_multi_currency();
		$this->multi_currency_helper->setup_3_currencies();

		$this->woocommerce_wpml->multi_currency->set_client_currency('USD');

		$args['price'] = 134;
		$table_rate = new WCML_Table_Rate_Shipping( $this->sitepress, $this->woocommerce_wpml );
		// will unconvert the price (exchange rate of 1.34)
		$args = $table_rate->filter_query_rates_args( $args );

		$this->assertEquals( 100, $args['price'] );

		$this->woocommerce_wpml->settings = $settings_backup;
		$this->woocommerce_wpml->update_settings();

	}
}
