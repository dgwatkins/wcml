<?php

/**
 * Class Test_WCML_Multi_Currency_Price
 */
class Test_WCML_Multi_Currency_Prices extends OTGS_TestCase {

	private function get_subject( $multi_currency ){
		return new WCML_Multi_Currency_Prices( $multi_currency );
	}

	private function get_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
					->setMethods( ['get_client_currency', 'set_client_currency'] )
		            ->getMock();
	}

	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_wc_cart_mock() {
		return $this->getMockBuilder( 'WC_Cart' )
		            ->disableOriginalConstructor()
					->setMethods( ['woocommerce_calculate_totals', 'get_cart_subtotal'] )
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

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_filters_loaded() {

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->load_filters = true;

		$subject = $this->get_subject( $multi_currency );

		\WP_Mock::expectFilterAdded( 'init', array( $subject, 'prices_init' ), 5 );

		\WP_Mock::expectFilterAdded( 'woocommerce_currency', array( $subject, 'currency_filter' ) );

		\WP_Mock::expectFilterAdded( 'wcml_price_currency', array( $subject, 'price_currency_filter' ) );      // WCML filters
		\WP_Mock::expectFilterAdded( 'wcml_product_price_by_currency', array(
			$subject,
			'get_product_price_in_currency'
		), 10, 2 );  // WCML filters


		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'product_price_filter' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'variation_prices_filter' ), 12, 4 ); // second

		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_max_amount', array( $subject, 'raw_price_filter' ), 99 );
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_min_amount', array( $subject, 'raw_price_filter' ), 99 );

		\WP_Mock::expectFilterAdded( 'woocommerce_adjust_price', array( $subject, 'raw_price_filter' ), 10 );

		\WP_Mock::expectFilterAdded( 'wcml_formatted_price', array( $subject, 'formatted_price' ), 10, 2 ); // WCML filters

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

		//filters for wc-widget-price-filter
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_results', array( $subject, 'filter_price_filter_results' ), 10, 3 );
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_amount', array( $subject, 'filter_price_filter_widget_amount' ) );

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

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

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

		$subject = $this->get_subject( $this->get_multi_currency_mock() );

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->product_price_filter( array(), $product_id, $meta_key, true );
	}

}
