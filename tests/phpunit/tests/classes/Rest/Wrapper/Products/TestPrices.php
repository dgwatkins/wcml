<?php

namespace WCML\Rest\Wrapper\Products;

/**
 * @group rest
 * @group rest-products
 */
class TestPrices extends \OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $multi_currency;
	/** @var array */
	private $currencies_order_settings;
	/** @var WPML_Post_Translation */
	private $wpml_post_translations;

	public function setUp(){
		parent::setUp();
		$this->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->currencies_order_settings = [];

		$this->wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( [ 'get_element_trid', 'get_element_translations', 'get_element_lang_code', 'get_original_post_ID' ] )
		                                     ->getMock();
	}

	function get_subject() {
		return new Prices( $this->multi_currency, $this->currencies_order_settings, $this->wpml_post_translations );
	}


	/**
	 * @test
	 */
	function set_product_custom_prices() {

		$custom_prices = [
			'custom_prices' => [
				'RON' => [
					'price'         => 1999,
					'regular_price' => 1999,
					'sale_price'    => 999
				]
			]
		];

		$expected_prices = [
			'_price'         => 1999,
			'_regular_price' => 1999,
			'_sale_price'    => 999
		];

		$original_element_id = 1;

		$post = $this->getMockBuilder( 'WC_Simple_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();

		$post->ID = 77;
		$post->method( 'get_id' )->willReturn( $post->ID );

		$trid = 12;

		$this->wpml_post_translations->method( 'get_element_trid' )->with( $post->ID )->willReturn( $trid );
		$this->wpml_post_translations->method( 'get_original_post_ID' )->with( $trid )->willReturn( $original_element_id );

		\WP_Mock::userFunction( 'update_post_meta', [
				'args' => [ $original_element_id, '_wcml_custom_prices_status', 1 ],
				'times' => 1,
				'return' => true
			]
		 );

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( $custom_prices );

		$this->multi_currency->custom_prices = $this->getMockBuilder( 'WCML_Custom_Prices' )
		                                                              ->disableOriginalConstructor()
		                                                              ->setMethods( [ 'update_custom_prices' ] )
		                                                              ->getMock();

		$this->multi_currency->custom_prices
			->method( 'update_custom_prices' )->with( $original_element_id, $expected_prices, 'RON' )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );
	}


	/**
	 * @test
	 */

	public function append_product_secondary_prices_without_custom_prices() {

		$currencies = [ 'EUR', 'RON' ];

		$product_id         = rand( 1, 100 );
		$product_data       = $this->getMockBuilder( 'WP_REST_Response' )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$product_data->data = [
			'id'            => $product_id,
			'regular_price' => round( rand( 100, 10000 ) / 100, 2 ),
			'sale_price'    => round( rand( 100, 10000 ) / 100, 2 )
		];

		$this->currency_exchange_mock = round( rand( 1, 10 ) / 10, 2 );
		$this->custom_price_mock      = [
			'_price'         => $this->currency_exchange_mock * $product_data->data['regular_price'] * 1.5,
			'_regular_price' => $this->currency_exchange_mock * $product_data->data['regular_price'] * 1.5,
			'_sale_price'    => $this->currency_exchange_mock * $product_data->data['regular_price'] * 0.75
		];

		$this->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                                       ->disableOriginalConstructor()
		                                                       ->setMethods( [ 'raw_price_filter' ] )
		                                                       ->getMock();

		$this->multi_currency->prices->method( 'raw_price_filter' )
		                                               ->will( $this->returnCallback(
			                                               function ( $price ) {
				                                               return $price * $this->currency_exchange_mock;
			                                               }
		                                               ) );

		$this->currencies_order_settings = $currencies;

		\WP_Mock::userFunction( 'get_post_meta', [
				'args' => [ $product_id, '_wcml_custom_prices_status', 1 ],
				'times' => 1,
				'return' => false
			]
		);

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
				'return' => 'USD'
			]
		);

		$subject = $this->get_subject();
		$product_data_out = $subject->prepare( $product_data,
			$this->getMockBuilder( 'WC_Data' )
			     ->disableOriginalConstructor()
			     ->getMock(),
			$this->getMockBuilder( 'WP_REST_Request' )
			     ->disableOriginalConstructor()
			     ->getMock()
		);

		$this->assertArrayHasKey( 'multi-currency-prices', $product_data_out->data );
		$this->assertArrayHasKey( 'RON', $product_data_out->data['multi-currency-prices'] );

		$this->assertEquals(
			$product_data_out->data['regular_price'] * $this->currency_exchange_mock,
			$product_data_out->data['multi-currency-prices']['RON']['regular_price']
		);
		$this->assertEquals(
			$product_data_out->data['sale_price'] * $this->currency_exchange_mock,
			$product_data_out->data['multi-currency-prices']['RON']['sale_price']
		);
	}

	/**
	* @test
	*/
	public function append_product_secondary_prices_with_custom_prices() {
		$currencies = [ 'EUR', 'RON' ];

		$product_id         = rand( 1, 100 );
		$product_data       = $this->getMockBuilder( 'WP_REST_Response' )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$product_data->data = [
			'id'            => $product_id,
			'regular_price' => round( rand( 100, 10000 ) / 100, 2 ),
			'sale_price'    => round( rand( 100, 10000 ) / 100, 2 )
		];

		$this->currency_exchange_mock = round( rand( 1, 10 ) / 10, 2 );
		$this->custom_price_mock      = [
			'_price'         => $this->currency_exchange_mock * $product_data->data['regular_price'] * 1.5,
			'_regular_price' => $this->currency_exchange_mock * $product_data->data['regular_price'] * 1.5,
			'_sale_price'    => $this->currency_exchange_mock * $product_data->data['regular_price'] * 0.75
		];

		$this->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                                       ->disableOriginalConstructor()
		                                                       ->setMethods( [ 'raw_price_filter' ] )
		                                                       ->getMock();

		$this->multi_currency->prices->method( 'raw_price_filter' )
		                                               ->will( $this->returnCallback(
			                                               function ( $price ) {
				                                               return $price * $this->currency_exchange_mock;
			                                               }
		                                               ) );

		$this->currencies_order_settings = $currencies;

		$this->multi_currency->custom_prices = $this->getMockBuilder( 'WCML_Custom_Prices' )
		                                                              ->disableOriginalConstructor()
		                                                              ->setMethods( [ 'get_product_custom_prices' ] )
		                                                              ->getMock();

		$this->multi_currency->custom_prices
			->method( 'get_product_custom_prices' )->willReturn( $this->custom_price_mock );

		\WP_Mock::userFunction( 'get_post_meta', [
				'args' => [ $product_id, '_wcml_custom_prices_status', 1 ],
				'times' => 1,
				'return' => true
			]
		);

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
				'return' => 'USD'
			]
		);

		$subject = $this->get_subject();
		$product_data_out = $subject->prepare( $product_data,
			$this->getMockBuilder( 'WC_Data' )
			     ->disableOriginalConstructor()
			     ->getMock(),
			$this->getMockBuilder( 'WP_REST_Request' )
			     ->disableOriginalConstructor()
			     ->getMock()
		);
		$this->assertArrayHasKey( 'multi-currency-prices', $product_data_out->data );
		$this->assertArrayHasKey( 'RON', $product_data_out->data['multi-currency-prices'] );

		$this->assertEquals(
			$this->custom_price_mock['_regular_price'],
			$product_data_out->data['multi-currency-prices']['RON']['regular_price']
		);
		$this->assertEquals(
			$this->custom_price_mock['_sale_price'],
			$product_data_out->data['multi-currency-prices']['RON']['sale_price']
		);

	}

}
