<?php

class Test_WCML_Custom_Prices extends OTGS_TestCase {

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_subject( $woocommerce_wpml = false, $wpdb = false ) {

		if( !$woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}
		if ( ! $wpdb ) {
			$wpdb = $this->stubs->wpdb();
		}
		return new WCML_Custom_Prices( $woocommerce_wpml, $wpdb );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'init', array( $subject, 'custom_prices_init' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function custom_prices_init_admin_hooks() {

		\WP_Mock::userFunction( 'is_admin', [ 'return' => time() ] );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_variation_options', [
			$subject,
			'add_individual_variation_nonce'
		], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_product_options_pricing', [
			$subject,
			'woocommerce_product_options_custom_pricing'
		] );
		\WP_Mock::expectActionAdded( 'woocommerce_product_after_variable_attributes', [
			$subject,
			'woocommerce_product_after_variable_attributes_custom_pricing'
		], 10, 3 );


		\WP_Mock::expectFilterAdded( 'woocommerce_product_import_process_item_data', [
			$subject,
			'product_import_uppercase_currency_codes_in_item_data'
		] );

		$subject->custom_prices_init();
	}


	/**
	 * @test
	 */
	public function it_should_set_regular_price_as_price_and_update_custom_prices() {

		$post_id = 101;

		$regular_price = 10;
		$sale_price = '';
		$schedule = '';
		$date_from = time();
		$date_to = time();

		$custom_prices = array(
			'_regular_price'         => $regular_price,
			'_sale_price'            => $sale_price,
			'_wcml_schedule'         => $schedule,
			'_sale_price_dates_from' => $date_from,
			'_sale_price_dates_to'   => $date_to
		);
		$code = 'USD';

		\WP_Mock::userFunction( 'current_time', [ 'return' => time() ] );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_regular_price_'.$code, $regular_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_'.$code, $sale_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_wcml_schedule_'.$code, $schedule ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_from_'.$code, $date_from ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_to_'.$code, $date_to ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_price_'.$code, $regular_price ),
			'times' => 1
		) );

		$subject = $this->get_subject();

		$this->assertEquals( $regular_price, $subject->update_custom_prices( $post_id, $custom_prices, $code ) );
	}

	/**
	 * @test
	 */
	public function it_should_set_sale_price_as_price_and_update_custom_prices() {

		$post_id = 101;

		$regular_price = 10;
		$sale_price = 8;
		$schedule = '';
		$date_from = '';
		$date_to = '';

		$custom_prices = array(
			'_regular_price'         => $regular_price,
			'_sale_price'            => $sale_price,
			'_wcml_schedule'         => $schedule,
			'_sale_price_dates_from' => $date_from,
			'_sale_price_dates_to'   => $date_to
		);
		$code = 'USD';

		\WP_Mock::userFunction( 'current_time', [ 'return' => time() ] );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_regular_price_'.$code, $regular_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_'.$code, $sale_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_wcml_schedule_'.$code, $schedule ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_from_'.$code, $date_from ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_to_'.$code, $date_to ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_price_'.$code, $sale_price ),
			'times' => 1
		) );

		$subject = $this->get_subject();

		$this->assertEquals( $sale_price, $subject->update_custom_prices( $post_id, $custom_prices, $code ) );
	}

	/**
	 * @test
	 * @dataProvider product_custom_prices
	 */
	public function it_should_get_product_custom_prices( $price, $regular_price, $sale_price, $schedule, $date_from, $date_to, $origin_date_from, $origin_date_to, $expected_custom_prices ) {

		\WP_Mock::passthruFunction( 'wp_cache_set' );
		\WP_Mock::passthruFunction( 'wp_cache_get' );

		$product_id = 101;
		$currency = 'USD';

		$wcml_price_custom_fields = array(
			'_price',
			'_regular_price',
			'_sale_price',
		);

		$product_meta = array(
			'_price_' . $currency                 => array( $price ),
			'_regular_price_' . $currency         => array( $regular_price ),
			'_sale_price_' . $currency            => array( $sale_price ),
			'_wcml_schedule'                      => array( $schedule ),
			'_sale_price_dates_from_' . $currency => array( $date_from ),
			'_sale_price_dates_to_' . $currency   => array( $date_to ),
			'_sale_price_dates_from'              => array( $origin_date_from ),
			'_sale_price_dates_to'                => array( $origin_date_to ),
			'_wcml_custom_prices_status'          => array( '1' )
		);

		\WP_Mock::userFunction( 'current_time', [ 'return' => time() ] );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'times' => 1,
			'return' => 'EUR'
		) );

		\WP_Mock::userFunction( 'wcml_price_custom_fields', array(
			'times' => 1,
			'return' => $wcml_price_custom_fields
		) );

		\WP_Mock::userFunction( 'get_post_custom', array(
			'args' => array( $product_id ),
			'times' => 1,
			'return' => $product_meta
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$woocommerce_wpml->products = $this->getMockBuilder( 'wcml_products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'get_original_product_id'))
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'get_original_product_id' )->with( $product_id )->willReturn( $product_id );


		$subject = $this->get_subject( $woocommerce_wpml );

		$this->assertEquals( $expected_custom_prices, $subject->get_product_custom_prices( $product_id, $currency ) );
	}

	/**
	 * @return array
	 */
	public function product_custom_prices(){

		return array(
			array(
				8,
				10,
				8,
				'',
				'',
				'',
				'',
				'',
				array(
					'_price'         => 8,
					'_regular_price' => 10,
					'_sale_price'    => 8,
				)
			),
			array(
				10,
				10,
				'',
				'',
				'',
				'',
				'',
				'',
				array(
					'_price'         => 10,
					'_regular_price' => 10,
					'_sale_price'    => '',
				)
			),
			array(
				8,
				10,
				8,
				1,
				strtotime( date( 'Y-m-d H:i:s', strtotime("-1 day") ) ),
				strtotime( date( 'Y-m-d H:i:s', strtotime("+1 day") ) ),
				'',
				'',
				array(
					'_price'         => 8,
					'_regular_price' => 10,
					'_sale_price'    => 8,
				)
			),
			array(
				8,
				10,
				8,
				1,
				strtotime( date( 'Y-m-d H:i:s', strtotime("-5 day") ) ),
				strtotime( date( 'Y-m-d H:i:s', strtotime("-1 day") ) ),
				'',
				'',
				array(
					'_price'         => 10,
					'_regular_price' => 10,
					'_sale_price'    => '',
				)
			),
			array(
				8,
				10,
				8,
				'',
				'',
				'',
				strtotime( date( 'Y-m-d H:i:s', strtotime("-1 day") ) ),
				strtotime( date( 'Y-m-d H:i:s', strtotime("+1 day") ) ),
				array(
					'_price'         => 8,
					'_regular_price' => 10,
					'_sale_price'    => 8,
				)
			),
			array(
				10,
				10,
				8,
				'',
				'',
				'',
				strtotime( date( 'Y-m-d H:i:s', strtotime("-5 day") ) ),
				strtotime( date( 'Y-m-d H:i:s', strtotime("-1 day") ) ),
				array(
					'_price'         => 10,
					'_regular_price' => 10,
					'_sale_price'    => '',
				)
			),
		);
	}


	/**
	 * @test
	 */
	public function it_should_uppercase_currency_codes_in_item_data() {

		$data['meta_data'] = [
			[ 'key' => '_price_usd' ],
			[ 'key' => '_regular_price_usd' ],
			[ 'key' => '_price_eur' ],
			[ 'key' => '_regular_price_eur' ],
		];

		$expected_data['meta_data'] = [
			[ 'key' => '_price_USD' ],
			[ 'key' => '_regular_price_USD' ],
			[ 'key' => '_price_EUR' ],
			[ 'key' => '_regular_price_EUR' ],
		];

		$currency_codes = [ 'USD', 'EUR' ];

		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( [ 'get_currency_codes' ] )
		                                         ->getMock();

		$woocommerce_wpml->multi_currency->method( 'get_currency_codes' )->willReturn( $currency_codes );

		$subject = $this->get_subject( $woocommerce_wpml );

		$this->assertEquals( $expected_data, $subject->product_import_uppercase_currency_codes_in_item_data( $data ) );
	}

}
