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
		$name = $label_1;
		$table_rate = new WCML_Table_Rate_Shipping( $this->sitepress, $this->woocommerce_wpml );
		$table_rate->init();

		$query = $this->wpdb->prepare( "SELECT id, value, gettext_context, name
										FROM {$this->wpdb->prefix}icl_strings
										WHERE context=%s", $domain, $context, $name );
		$res   = $this->wpdb->get_results( $query );
		$expected = array(
			$rate_title,
			$label_1,
			$label_2,
			$label_3,
		);

		foreach ( $res as $index => $registered_string ) {
			$this->assertEquals( $expected[ $index ], $registered_string->value );
			$this->assertEquals( $expected[ $index ] . '_shipping_method_title', $registered_string->name );
		}
	}

	/**
	 * @test
	 */
	public function default_shipping_class_id() {
		$amount = random_int( 1, 999 );
		$woocommerce_wpml = $this->get_wcml_mock();
		$woocommerce_wpml->settings = array();
		$woocommerce_wpml->settings['enable_multi_currency'] = 2;
		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();
		$woocommerce_wpml->multi_currency->prices = $this->get_wcml_multi_currency_prices_mock();
		$woocommerce_wpml->multi_currency->prices
			->method( 'unconvert_price_amount' )
			->willReturn( $amount / 2 );

		$table_rate = new WCML_Table_Rate_Shipping( $this->sitepress, $woocommerce_wpml );
		$shipping_class = wpml_test_insert_term( $this->default_language, 'product_shipping_class', false, 'shipping_class' . $this->default_language );
		$trid = $this->sitepress->get_element_trid( $shipping_class['term_id'], 'tax_product_shipping_class' );
		$tr_shipping_class = wpml_test_insert_term( $this->second_language, 'product_shipping_class', $trid, 'shipping_class' . $this->second_language );
		$args = array(
			'shipping_class_id' => $shipping_class['term_id'],
			'price'             => $amount,
		);

		$expected = $args;
		$expected['price'] = $args['price'] / 2;
		$this->assertEquals( $expected, $table_rate->default_shipping_class_id( $args ) );
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
}
