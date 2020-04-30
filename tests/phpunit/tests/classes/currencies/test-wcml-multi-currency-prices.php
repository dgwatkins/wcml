<?php

/**
 * Class Test_WCML_Multi_Currency_Price
 */
class Test_WCML_Multi_Currency_Prices extends OTGS_TestCase {

	private function get_subject( $multi_currency ){
		return new WCML_Multi_Currency_Prices( $multi_currency, array() );
	}

	private function get_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
					->setMethods( ['get_client_currency', 'set_client_currency', 'get_exchange_rates', 'get_currencies_without_cents', 'are_filters_need_loading' ] )
		            ->getMock();
	}

	private function get_wc_cart_mock() {
		return $this->getMockBuilder( 'WC_Cart' )
		            ->disableOriginalConstructor()
					->setMethods( ['woocommerce_calculate_totals', 'get_cart_subtotal', 'calculate_totals'] )
		            ->getMock();
	}

	private function get_wc_mock() {
		return $this->getMockBuilder( 'woocommerce' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function add_hooks_filters_not_loaded() {

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->load_filters = false;

		$subject = $this->get_subject( $multi_currency );

		\WP_Mock::expectActionAdded( 'woocommerce_cart_loaded_from_session', [
			$subject,
			'recalculate_totals'
		] );

		\WP_Mock::expectFilterAdded( 'wcml_raw_price_amount', array( $subject, 'raw_price_filter' ), 10, 2 );

		\WP_Mock::expectFilterAdded( 'option_woocommerce_price_thousand_sep', array(
			$subject,
			'filter_currency_thousand_sep_option'
		) );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_price_decimal_sep', array(
			$subject,
			'filter_currency_decimal_sep_option'
		) );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_price_num_decimals', array(
			$subject,
			'filter_currency_num_decimals_option'
		) );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_currency_pos', array(
			$subject,
			'filter_currency_position_option'
		) );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'save_order_currency_for_filter' ), 10, 4 );

		\WP_Mock::expectFilterAdded( 'wcml_formatted_price', array( $subject, 'formatted_price' ), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_filters_loaded() {

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->load_filters = true;

		$subject = $this->get_subject( $multi_currency );

		\WP_Mock::expectFilterAdded( 'woocommerce_currency', array( $subject, 'currency_filter' ) );

		\WP_Mock::expectFilterAdded( 'wcml_price_currency', array( $subject, 'price_currency_filter' ) );
		\WP_Mock::expectFilterAdded( 'wcml_product_price_by_currency', array(
			$subject,
			'get_product_price_in_currency'
		), 10, 2 );


		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'product_price_filter' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'variation_prices_filter' ), 12, 4 ); // second

		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_max_amount', array( $subject, 'filter_widget_max_amount' ), 99 );
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_min_amount', array( $subject, 'filter_widget_min_amount' ), 99 );

		\WP_Mock::expectFilterAdded( 'woocommerce_adjust_price', array( $subject, 'raw_price_filter' ), 10 );

		// Shipping prices
		\WP_Mock::expectFilterAdded( 'woocommerce_paypal_args', array( $subject, 'filter_price_woocommerce_paypal_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_get_variation_prices_hash', array(
			$subject,
			'add_currency_to_variation_prices_hash'
		) );
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_contents_total', array(
			$subject,
			'filter_woocommerce_cart_contents_total'
		), 100 );
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_subtotal', array( $subject, 'filter_woocommerce_cart_subtotal' ), 100, 3 );

		\WP_Mock::expectFilterAdded( 'posts_clauses', array( $subject, 'price_filter_post_clauses' ), 100, 2 );

		\WP_Mock::expectActionAdded( 'woocommerce_cart_loaded_from_session', array(
			$subject,
			'filter_currency_num_decimals_in_cart'
		) );

		\WP_Mock::expectFilterAdded( 'wc_price_args', array( $subject, 'filter_wc_price_args' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function convert_raw_woocommerce_price_with_null_currency() {
		$multi_currency = $this->get_multi_currency_mock();
		$subject        = $this->get_subject( $multi_currency );

		$price          = random_int( 0, 1000 ) / random_int( 1, 10 );
		$currency       = rand_str();
		$expected_price = random_int( 0, 1000 ) / random_int( 1, 10 );

		$multi_currency->expects( $this->once() )
		               ->method( 'get_client_currency' )
		               ->willReturn( $currency );

		\WP_Mock::onFilter( 'wcml_raw_price_amount' )->with( $price, $currency )->reply( $expected_price );

		$this->assertSame( $expected_price, $subject->convert_raw_woocommerce_price( $price ) );

	}

	/**
	 * @test
	 */
	public function convert_raw_woocommerce_price_with_given_currency() {
		$multi_currency = $this->get_multi_currency_mock();
		$subject        = $this->get_subject( $multi_currency );

		$price          = random_int( 0, 1000 ) / random_int( 1, 10 );
		$currency       = rand_str();
		$expected_price = random_int( 0, 1000 ) / random_int( 1, 10 );

		$multi_currency->expects( $this->exactly( 0 ) )
		               ->method( 'get_client_currency' );

		\WP_Mock::onFilter( 'wcml_raw_price_amount' )->with( $price, $currency )->reply( $expected_price );

		$this->assertSame( $expected_price, $subject->convert_raw_woocommerce_price( $price, $currency ) );

	}

	/**
	 * @test
	 */
	public function get_original_product_price() {
		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$product_id = random_int(1, 1000);
		$price          = round( random_int( 1, 1000 ) / random_int( 1, 100 ), 2 );
		$expected_price = round( random_int( 1, 1000 ) / random_int( 1, 100 ), 2 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( ['get_id'] )
		                ->getMock();

		$product->expects( $this->once() )->method('get_id')->willReturn( $product_id );

		\WP_Mock::wpFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $product_id, '_price', 1 ],
			'return' => $expected_price
		] );

		$this->assertSame( $expected_price, $subject->get_original_product_price( $price, $product ) );

	}


	/**
	 * @test
	 */
	public function it_does_filter_woocommerce_cart_subtotal() {

		WP_Mock::passthruFunction( 'remove_filter' );

		$cart_subtotal = 10;
		$expected_subtotal = 20;
		$compound = false;
		$cart_object = $this->getMockBuilder( 'WC_Cart' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'get_cart_subtotal' ) )
		                    ->getMock();

		$cart_object->expects( $this->once() )->method( 'get_cart_subtotal' )->willReturn( $expected_subtotal );

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$this->assertSame( $expected_subtotal, $subject->filter_woocommerce_cart_subtotal( $cart_subtotal, $compound, $cart_object ) );

	}

	/**
	 * @test
	 * @group wcml_price_custom_fields
	 */
	public function it_should_call_wcml_price_custom_fields() {
		$product_id = 123;
		$post_type = 'product';
		$meta_key = null;

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->method( 'are_filters_need_loading' )->willReturn( true );

		$subject = $this->get_subject( $multi_currency );

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array(
			'times' => 1,
			'args'  => array( $product_id ),
		) );

		$subject->product_price_filter( array(), $product_id, $meta_key, true );
	}

	/**
	 * @test
	 * @group wcml_price_custom_fields
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_the_post_type_is_not_a_product_or_a_product_variation() {
		$product_id = 123;
		$post_type = 'post';
		$meta_key = null;

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->method( 'are_filters_need_loading' )->willReturn( true );

		$subject = $this->get_subject( $multi_currency );

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->product_price_filter( array(), $product_id, $meta_key, true );
	}

	/**
	 * @test
	 */
	public function it_should_NOT_filter_price_filter_post_clauses_when_price_not_entered() {

		$args     = array( 'where' => rand_str() );
		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_main_query' ) )
		                 ->getMock();
		$wp_query->method( 'is_main_query' )->willReturn( true );

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$this->assertSame( $args, $subject->price_filter_post_clauses( $args, $wp_query ) );
	}

	/**
	 * @test
	 */
	public function it_should_NOT_filter_price_filter_post_clauses_when_wp_query_is_not_main_query() {

		$args     = array( 'where' => rand_str() );
		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_main_query' ) )
		                 ->getMock();
		$wp_query->method( 'is_main_query' )->willReturn( false );

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$this->assertSame( $args, $subject->price_filter_post_clauses( $args, $wp_query ) );
	}

	/**
	 * @test
	 */
	public function it_should_NOT_filter_price_filter_post_clauses_when_is_default_currency() {

		WP_Mock::passthruFunction( 'wp_unslash' );

		$currency = 'USD';
		$args     = array( 'where' => rand_str() );

		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_main_query' ) )
		                 ->getMock();
		$wp_query->method( 'is_main_query' )->willReturn( true );

		$_GET['min_price'] = 10;
		$_GET['max_price'] = 20;

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->method( 'get_client_currency' )->willReturn( $currency );

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $currency
		));

		$subject = $this->get_subject( $multi_currency );

		$this->assertSame( $args, $subject->price_filter_post_clauses( $args, $wp_query ) );

		unset( $_GET['min_price'] );
		unset( $_GET['max_price'] );
	}

	/**
	 * @test
	 */
	public function it_should_filter_price_filter_post_clauses() {

		WP_Mock::passthruFunction( 'wp_unslash' );

		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_main_query' ) )
		                 ->getMock();
		$wp_query->method( 'is_main_query' )->willReturn( true );

		$currency                 = 'EUR';
		$exchange_rates           = array( $currency => 2 );
		$currencies_without_cents = array();
		$_GET['min_price']        = 10;
		$_GET['max_price']        = 20;
		$converted_min_price      = $_GET['min_price'] / $exchange_rates[ $currency ];
		$converted_max_price      = $_GET['max_price'] / $exchange_rates[ $currency ];

		$args          = array( 'where' => 'wc_product_meta_lookup.min_price >= ' . $_GET['min_price'] . ' AND wc_product_meta_lookup.max_price <= ' . $_GET['max_price'] );
		$expected_args = array( 'where' => 'wc_product_meta_lookup.min_price >= ' . $converted_min_price . ' AND wc_product_meta_lookup.max_price <= ' . $converted_max_price );

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->method( 'get_client_currency' )->willReturn( $currency );
		$multi_currency->method( 'get_exchange_rates' )->willReturn( $exchange_rates );
		$multi_currency->method( 'get_currencies_without_cents' )->willReturn( $exchange_rates );


		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => 'USD'
		));


		global $wpdb;
		$wpdb = $this->stubs->wpdb();

		$wpdb->method( 'prepare' )->will( $this->returnCallback(
			function ( $query, $value ) {
				return str_replace( '%f', $value, $query );
			}
		) );

		$subject = $this->get_subject( $multi_currency );

		$this->assertSame( $expected_args, $subject->price_filter_post_clauses( $args, $wp_query ) );

		unset( $_GET['min_price'] );
		unset( $_GET['max_price'] );
	}


	/**
	 * @test
	 *
	 */
	public function it_should_filter_widget_min_amount_given_step_price_of_10(){

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$min_price = 11.12;
		$expected_step_price = 10;

		$filtered_min_price = $subject->filter_widget_min_amount( $min_price );

		$this->assertEquals( $expected_step_price, $filtered_min_price );

	}

	/**
	 * @test
	 *
	 */
	public function it_should_filter_widget_max_amount_given_step_price_of_10(){

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$max_price = 12.22;
		$expected_step_price = 20;

		$filtered_max_price = $subject->filter_widget_max_amount( $max_price );

		$this->assertEquals( $expected_step_price, $filtered_max_price );

	}


	/**
	 * @test
	 */
	public function it_should_filter_pre_selected_widget_prices_in_second_currency() {

		$to_currency = 'EUR';
		$from_currency = 'USD';
		$min_price = 10;
		$max_price = 20;
		$params = [ 'min_price' => $min_price, 'max_price' => $max_price ];

		$exchange_rates           = array( $to_currency => 2 );
		$currencies_without_cents = array();

		$converted_min_price      = $min_price * $exchange_rates[ $to_currency ];
		$converted_max_price      = $max_price * $exchange_rates[ $to_currency ];
		$expected_params[ 'min_price' ] = $converted_min_price;
		$expected_params[ 'max_price' ] = $converted_max_price;

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->method( 'get_exchange_rates' )->willReturn( $exchange_rates );
		$multi_currency->method( 'get_currencies_without_cents' )->willReturn( $exchange_rates );

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $from_currency
		));

		$subject = $this->get_subject( $multi_currency );

		$this->assertSame( $expected_params, $subject->filter_pre_selected_widget_prices_in_new_currency( [], $to_currency, $from_currency, $params ) );
	}

	/**
	 * @test
	 */
	public function it_should_filter_pre_selected_widget_prices_in_default_currency() {

		$to_currency = 'EUR';
		$from_currency = 'USD';
		$min_price = 10;
		$max_price = 20;
		$params = [ 'min_price' => $min_price, 'max_price' => $max_price ];

		$exchange_rates           = array( $to_currency => 2 );
		$currencies_without_cents = array();

		$converted_min_price      = $min_price / $exchange_rates[ $to_currency ];
		$converted_max_price      = $max_price / $exchange_rates[ $to_currency ];
		$expected_params[ 'min_price' ] = $converted_min_price;
		$expected_params[ 'max_price' ] = $converted_max_price;

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->method( 'get_exchange_rates' )->willReturn( $exchange_rates );
		$multi_currency->method( 'get_currencies_without_cents' )->willReturn( $exchange_rates );

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $from_currency
		));

		$subject = $this->get_subject( $multi_currency );

		$this->assertSame( $expected_params, $subject->filter_pre_selected_widget_prices_in_new_currency( [], $from_currency, $to_currency, $params ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_pre_selected_widget_prices() {

		$to_currency   = 'EUR';
		$from_currency = 'USD';
		$params        = [];

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $from_currency
		) );

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$this->assertSame( [], $subject->filter_pre_selected_widget_prices_in_new_currency( [], $from_currency, $to_currency, $params ) );
	}

	/**
	 * @test
	 */
	public function it_should_recalculate_totals() {

		$woocommerce = $this->get_wc_mock();

		$woocommerce->cart = $this->get_wc_cart_mock();
		$woocommerce->cart->expects( $this->once() )->method( 'calculate_totals' )->willReturn( true );

		WP_Mock::userFunction( 'WC', array(
			'return' => $woocommerce
		) );

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		$subject->recalculate_totals();
	}

}
