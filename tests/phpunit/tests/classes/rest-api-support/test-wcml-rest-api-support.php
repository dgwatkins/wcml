<?php

/**
 * Class Test_WCML_REST_API_Support
 * @group wcml-1979
 */
class Test_WCML_REST_API_Support extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** @var  WCML_REST_API_Query_Filters_Products */
	private $query_filters_posts;
	/** @var  WCML_REST_API_Query_Filters_Orders */
	private $query_filters_orders;
	/** @var  WCML_REST_API_Query_Filters_Terms */
	private $query_filters_terms;
	/** @var WPML_Frontend_Post_Actions */
	private $wpml_post_translations;


	private $test_data = [
		'post_meta' => []
	];

	public function setUp() {
		parent::setUp();

		$_SERVER['REQUEST_URI'] = '/wp-json/wc/';

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_settings',
			                        'save_settings',
			                        'get_element_trid',
			                        'get_element_translations',
			                        'get_original_element_id_filter',
			                        'copy_custom_fields'
		                        ) )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => function ( $tag, $function_to_remove, $priority ) {
				unset( $GLOBALS['wp_filter'][ $tag ][ $priority ]['hardcoded_callback'] );
			},
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'return' => function ( $id, $meta, $single ) {
				$value = null;
				if ( $meta === '_wcml_custom_prices_status' ) {
					if ( $id <= 100 ) {
						$value = false;
					} else {
						$value = true;
					}
				} elseif ( isset( $this->test_data['post_meta'][ $meta ] ) ) {
					$value = $this->test_data['post_meta'][ $meta ];
				}

				return $value;
			},
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) {
				$value = null;
				if ( $option_name === 'woocommerce_currency' ) {
					$value = 'EUR';
				}

				return $value;
			},
		) );

		\WP_Mock::wpPassthruFunction( '__' );

		\WP_Mock::wpFunction( 'get_query_var', array(
			'return' => function ( $var ) {
				return isset( $this->test_data['query_var'][ $var ] ) ?
					$this->test_data['query_var'][ $var ] : null;
			},
		) );

		\WP_Mock::wpFunction( 'get_post', array(
			'return' => function ( $id ) {
				if ( isset( $this->test_data['posts'][ $id ] ) ) {
					$post = $this->test_data['posts'][ $id ];
					// exception
					if ( $id == 4444 ) {
						$post_parent      = get_post( $post->post_parent );
						$post->post_title = $post_parent->post_title;
					}

					return $post;
				}
			},
		) );

		$this->wpdb = $this->stubs->wpdb();

		$this->query_filters_posts = $this->getMockBuilder( 'WCML_REST_API_Query_Filters_Products' )
		                                  ->disableOriginalConstructor()
		                                  ->getMock();

		$this->query_filters_orders = $this->getMockBuilder( 'WCML_REST_API_Query_Filters_Orders' )
		                                  ->disableOriginalConstructor()
		                                  ->getMock();

		$this->query_filters_terms = $this->getMockBuilder( 'WCML_REST_API_Query_Filters_Terms' )
		                                  ->disableOriginalConstructor()
		                                  ->getMock();

		$this->wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

	}

	public function tearDown() {
		unset( $this->sitepress, $this->woocommerce );
		parent::tearDown();
	}

	/**
	 * @return WCML_REST_API_Support
	 */
	private function get_subject() {
		return new WCML_REST_API_Support(
			$this->woocommerce_wpml,
			$this->sitepress,
			$this->wpdb,
			$this->query_filters_posts,
			$this->query_filters_orders,
			$this->query_filters_terms,
			$this->wpml_post_translations
		);
	}

	/**
	 * @test
	 */
	public function test_add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => '/wp-json'
		) );

		\WP_Mock::expectActionAdded( 'rest_api_init', array( $subject, 'set_language_for_request' ) );
		\WP_Mock::expectActionAdded( 'parse_query', array( $subject, 'auto_adjust_included_ids' ) );

		// Products
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_object', array( $subject, 'append_product_language_and_translations') );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_object', array( $subject, 'append_product_secondary_prices') );

		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_object', array( $subject, 'set_product_language'), 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_object', array( $subject, 'set_product_custom_prices'), 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_object', array( $subject, 'copy_product_custom_fields'), 10, 3 );

		// Orders
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_shop_order_object', array( $subject, 'set_order_language'), 10, 2 );

		 $subject->add_hooks() ;

	}

	/**
	 * @test
	 */
	public function set_language_for_request() {
		$subject = $this->get_subject();

		$default_language = 'en';
		$other_language   = 'ro';
		$wrong_language   = 'de';

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_active_languages', 'switch_lang' ) )
		                        ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array(
			$default_language => 1,
			$other_language   => 1
		) );
		$this->sitepress->method( 'switch_lang' )->will( $this->returnCallback(
			function ( $lang ) {
				$this->test_data['sitepress_current_language'] = $lang;
			}
		) );

		$this->test_data['sitepress_current_language'] = $default_language;

		$WP_REST_Server = $this->getMockBuilder( 'WP_REST_Server' )->disableOriginalConstructor()->getMock();

		// No lang - no switch
		unset( $_GET['lang'] );
		$subject->set_language_for_request( $WP_REST_Server );
		$this->assertEquals( $default_language, $this->test_data['sitepress_current_language'] );

		// Try to switch to inactive language - no switch
		$_GET['lang'] = $wrong_language;
		$subject = $this->get_subject();
		$subject->set_language_for_request( $WP_REST_Server );
		$this->assertEquals( $default_language, $this->test_data['sitepress_current_language'] );

		// Swicth to an active language - do switch
		$_GET['lang'] = $other_language;
		// Make is_request_to_rest_api return true
		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => function () {
				return 'wp-json';
			},
		) );
		//
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/';
		$subject->set_language_for_request( $WP_REST_Server );

		$this->assertEquals( $other_language, $this->test_data['sitepress_current_language'] );

		unset( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * @test
	 */

	public function append_product_language_and_translations() {

		$subject = $this->get_subject();

		$trid       = rand( 1, 100 );
		$product_id = rand( 1, 100 );

		// for original
		$product_data       = new stdClass();
		$product_data->data = [
			'id' => $product_id
		];

		$this->sitepress->method( 'get_element_trid' )->willReturn( $trid );

		$en_translation                = new stdClass();
		$en_translation->language_code = 'en';
		$en_translation->element_id    = $product_id;
		$translations['en']            = $en_translation;

		$fr_translation                = new stdClass();
		$fr_translation->language_code = 'fr';
		$fr_translation->element_id    = rand( 101, 200 );
		$translations['fr']            = $fr_translation;

		$this->sitepress->method( 'get_element_translations' )->willReturn( $translations );

		$product_data = $subject->append_product_language_and_translations( $product_data );

		$this->assertEquals( 'en', $product_data->data['lang'] );
		$this->assertEquals(
			array( 'fr' => $fr_translation->element_id ),
			$product_data->data['translations']
		);

		// for a translation
		$product_data->data['id'] = $fr_translation->element_id;
		$product_data             = $subject->append_product_language_and_translations( $product_data );

		$this->assertEquals( 'fr', $product_data->data['lang'] );
		$this->assertEquals(
			array( 'en' => $en_translation->element_id ),
			$product_data->data['translations']
		);

	}

	/**
	 * @test
	 */

	public function append_product_secondary_prices() {

		$subject = $this->get_subject();

		$currencies = array( 'EUR', 'RON' );

		$product_id         = rand( 1, 100 );
		$product_data       = new stdClass();
		$product_data->data = [
			'id'            => $product_id,
			'regular_price' => round( rand( 100, 10000 ) / 100, 2 ),
			'sale_price'    => round( rand( 100, 10000 ) / 100, 2 )
		];

		$this->currency_exchange_mock = round( rand( 1, 10 ) / 10, 2 );
		$this->custom_price_mock      = array(
			'_price'         => $this->currency_exchange_mock * $product_data->data['regular_price'] * 1.5,
			'_regular_price' => $this->currency_exchange_mock * $product_data->data['regular_price'] * 1.5,
			'_sale_price'    => $this->currency_exchange_mock * $product_data->data['regular_price'] * 0.75
		);

		// multi currency off
		$product_data_out = $subject->append_product_secondary_prices( $product_data );

		$this->assertArrayNotHasKey( 'multi-currency-prices', $product_data_out->data );

		// multi currency on - w/out custom prices ($id <= 100)
		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array() )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                                       ->disableOriginalConstructor()
		                                                       ->setMethods( array( 'raw_price_filter' ) )
		                                                       ->getMock();

		$this->woocommerce_wpml->multi_currency->prices->method( 'raw_price_filter' )
		                                               ->will( $this->returnCallback(
			                                               function ( $price ) {
				                                               return $price * $this->currency_exchange_mock;
			                                               }
		                                               ) );

		$this->woocommerce_wpml->settings['currencies_order'] = $currencies;

		$product_data_out = $subject->append_product_secondary_prices( $product_data );

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


		// multi currency on - w/ custom prices ($id > 100)
		$product_data->data['id'] = rand( 101, 200 ); // change id


		$this->woocommerce_wpml->multi_currency->custom_prices = $this->getMockBuilder( 'WCML_Custom_Prices' )
		                                                              ->disableOriginalConstructor()
		                                                              ->setMethods( array( 'get_product_custom_prices' ) )
		                                                              ->getMock();

		$this->woocommerce_wpml->multi_currency->custom_prices
			->method( 'get_product_custom_prices' )->willReturn( $this->custom_price_mock );

		$product_data_out = $subject->append_product_secondary_prices( $product_data );
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

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	function set_product_language_wrong_lang() {

		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params', 'get_method' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array( 'lang' => 'ru' ) );
		$request1->method( 'get_method' )->willReturn( 'POST' );

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_active_languages',
			                        'get_element_trid',
			                        'set_element_language_details'
		                        ) )
		                        ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'ro' => 1 ) );
		$this->sitepress->method( 'get_element_trid' )->willReturn( rand( 1, 100 ) );
		$this->sitepress->method( 'set_element_language_details' )->willReturn( true );

		$post     = new stdClass();
		$post->ID = rand( 1, 100 );

		$subject = $this->get_subject();
		$subject->set_product_language( $post, $request1 );

	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Product not found:
	 */
	function set_product_language_no_source_product() { // with translation_of

		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params', 'get_method' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'lang'           => 'ro',
			'translation_of' => rand( 1, 100 )
		) );
		$request1->method( 'get_method' )->willReturn( 'POST' );

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_active_languages',
			                        'get_element_trid',
			                        'set_element_language_details'
		                        ) )
		                        ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'ro' => 1 ) );
		$this->sitepress->method( 'get_element_trid' )->willReturn( false );
		$this->sitepress->method( 'set_element_language_details' )->willReturn( true );

		$post     = new stdClass();
		$post->ID = rand( 1, 100 );

		$subject = $this->get_subject();
		$subject->set_product_language( $post, $request1 );

	}

	/**
	 * @test
	 */
	function set_product_language_with_trid() {

		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params', 'get_method' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'lang'           => 'ro',
			'translation_of' => rand( 1, 100 )
		) );
		$request1->method( 'get_method' )->willReturn( 'POST' );

		$this->expected_trid = null;
		$this->actual_trid   = rand( 1, 100 );
		$this->sitepress     = $this->getMockBuilder( 'SitePress' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array(
			                            'get_active_languages',
			                            'get_element_trid',
			                            'set_element_language_details'
		                            ) )
		                            ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'ro' => 1 ) );
		$this->sitepress->method( 'get_element_trid' )->willReturn( $this->actual_trid );
		$this->sitepress->method( 'set_element_language_details' )->will( $this->returnCallback(
			function ( $post_id, $element_type, $trid, $lang ) {
				$this->expected_trid = $trid;

				return true;
			}
		) );

		$post = $this->getMockBuilder( 'WC_Product_Simple' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		if ( ! defined( 'ICL_TM_COMPLETE' ) ) {
			define( 'ICL_TM_COMPLETE', true );
		}

		$this->test_data['posts'][ $post->ID ] = $post;
		\WP_Mock::wpFunction( 'wpml_tm_save_post', array(
			'times' => 1,
			'args'  => [ $post->ID, $post, ICL_TM_COMPLETE ]
		) );

		$subject = $this->get_subject();
		$subject->set_product_language( $post, $request1 );
		$this->assertEquals( $this->expected_trid, $this->actual_trid );

	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Using "translation_of" requires providing a "lang" parameter too
	 */
	function set_product_language_missing_lang() {

		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array( 'translation_of' => rand( 1, 100 ) ) );

		$post     = new stdClass();
		$post->ID = rand( 1, 100 );

		$subject->set_product_language( $post, $request1 );

	}

	/**
	 * @test
	 */
	function set_product_language_new_product() { // no translation_of

		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params', 'get_method' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'lang' => 'ro'
		) );
		$request1->method( 'get_method' )->willReturn( 'POST' );

		$this->expected_trid = null;
		$this->actual_trid   = null;
		$this->sitepress     = $this->getMockBuilder( 'SitePress' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array(
			                            'get_active_languages',
			                            'get_element_trid',
			                            'set_element_language_details'
		                            ) )
		                            ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'ro' => 1 ) );
		$this->sitepress->method( 'get_element_trid' )->willReturn( null ); // no trid
		$this->sitepress->method( 'set_element_language_details' )->will( $this->returnCallback(
			function ( $post_id, $element_type, $trid, $lang ) {
				$this->expected_trid = null;

				return true;
			}
		) );

		$post = $this->getMockBuilder( 'WC_Product_Simple' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		$subject = $this->get_subject();
		$subject->set_product_language( $post, $request1 );
		$this->assertEquals( $this->expected_trid, $this->actual_trid );

	}

	/**
	 * @test
	 */
	function do_no_set_poduct_language_if_method_not_post(){

		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params', 'get_method' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'lang' => 'en'
		) );
		$request1->method( 'get_method' )->willReturn( 'PUT' );


		$post = new stdClass();
		$post->ID = rand(1,100);

		$subject->set_product_language( $post, $request1 );
	}

	/**
	 * @test
	 */
	function set_product_custom_prices() {

		$subject = $this->get_subject();

		$expected_prices = [
			'custom_prices' => [
				'RON' => [
					'price'         => 1999,
					'regular_price' => 1999,
					'sale_price'    => 999
				],
				'BGN' => [
					'price'         => 2001,
					'regular_price' => 2001,
					'sale_price'    => 1500
				]
			]
		];

		$original_element_id = rand( 1, 100 );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) {
				$this->test_data['post_meta'][ $meta ] = $value;
			},
		) );

		$post = $this->getMockBuilder( 'WC_Product_Simple' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = 77;
		$post->method( 'get_id' )->willReturn( $post->ID );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) {
				$this->test_data['post_meta'][ $meta ] = $value;
			},
		) );

		// 1) Empty Request
		$request0 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request0->method( 'get_params' )->willReturn( $expected_prices );
		$subject->set_product_custom_prices( $post, $request0 );

		$this->assertEmpty( get_post_meta( $original_element_id, '_wcml_custom_prices_status', true ) );


		// 2) Multi currency OFF
		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( $expected_prices );


		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_original_element_id_filter' ) )
		                        ->getMock();
		$this->sitepress->method( 'get_original_element_id_filter' )->willReturn( $original_element_id );


		$subject->set_product_custom_prices( $post, $request1 );

		$this->assertEmpty( get_post_meta( $original_element_id, '_wcml_custom_prices_status', true ) );

		// 3) Multi currency ON
		$this->woocommerce_wpml->multi_currency                = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                                              ->disableOriginalConstructor()
		                                                              ->setMethods( array() )
		                                                              ->getMock();
		$this->woocommerce_wpml->multi_currency->custom_prices = $this->getMockBuilder( 'WCML_Custom_Prices' )
		                                                              ->disableOriginalConstructor()
		                                                              ->setMethods( array( 'update_custom_prices' ) )
		                                                              ->getMock();

		$this->woocommerce_wpml->multi_currency->custom_prices
			->method( 'update_custom_prices' )->will(
				$this->returnCallback( function ( $original_post_id, $prices_uscore, $currency ) {
					update_post_meta( $original_post_id, 'custom_currencies_' . $currency, $prices_uscore ); //mock
				} ) );

		$subject = $this->get_subject();
		$subject->set_product_custom_prices( $post, $request1 );

		foreach ( $expected_prices['custom_prices'] as $currency => $prices ) {

			$actual_prices = get_post_meta( $original_element_id, 'custom_currencies_' . $currency, true );
			// remove underscores
			foreach ( $actual_prices as $key => $val ) {
				unset( $actual_prices[ $key ] );
				$actual_prices[ preg_replace( '/^_/', '', $key ) ] = $val;
			}

			$this->assertEquals( $expected_prices['custom_prices'][ $currency ], $actual_prices );

		}


	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	function set_order_language_exception() {
		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'lang' => 'de'
		) );

		$this->expected_trid = null;
		$this->actual_trid   = null;
		$this->sitepress     = $this->getMockBuilder( 'SitePress' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array(
			                            'get_active_languages',
		                            ) )
		                            ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'ro' => 1 ) );

		$post = $this->getMockBuilder( 'WC_Product_Simple' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		$subject = $this->get_subject();
		$subject->set_order_language( $post, $request1 );

	}

	/**
	 * @test
	 */
	function set_order_language() {
		$subject = $this->get_subject();

		$expected_language = 'ro';
		$request1          = $this->getMockBuilder( 'WP_REST_Request' )
		                          ->disableOriginalConstructor()
		                          ->setMethods( array( 'get_params' ) )
		                          ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'lang' => $expected_language
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) {
				$this->test_data['post_meta'][ $meta ] = $value;
			},
		) );

		$this->expected_trid = null;
		$this->actual_trid   = null;
		$this->sitepress     = $this->getMockBuilder( 'SitePress' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array(
			                            'get_active_languages',
		                            ) )
		                            ->getMock();
		$this->sitepress->method( 'get_active_languages' )->willReturn( array( 'en' => 1, $expected_language => 1 ) );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) {
				$this->test_data['post_meta'][ $meta ] = $value;
			},
		) );

		$subject = $this->get_subject();
		$subject->set_order_language( $post, $request1 );

		$actual_language = get_post_meta( $post->ID, 'wpml_language', true );
		$this->assertEquals( $expected_language, $actual_language );

	}


	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid currency parameter
	 */
	public function set_order_currency_throws_exception_if_a_currency_is_invalid(){
		$subject = $this->get_subject();

		$expected_currency = rand_str();
		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request->method( 'get_params' )->willReturn( array(
			'currency' => $expected_currency
		) );

		$woocommerce_currencies = [ rand_str() => rand_str(), rand_str() => rand_str(), rand_str() => rand_str() ];
		\WP_Mock::wpFunction( 'get_woocommerce_currencies', [ 'return' => $woocommerce_currencies ] );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = random_int( 1,100 );
		$post->method('get_id')->willReturn( $post->ID );

		$subject->set_order_currency( $post, $request );

	}

	/**
	 * @test
	 */
	public function set_order_currency_valid_currency(){
		$subject = $this->get_subject();

		$expected_currency = rand_str();
		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( array(
			'currency' => $expected_currency
		) );

		$woocommerce_currencies = [ $expected_currency => rand_str(), rand_str() => rand_str(), rand_str() => rand_str() ];
		\WP_Mock::wpFunction( 'get_woocommerce_currencies', [ 'return' => $woocommerce_currencies ] );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( array(
			             'get_id'
		             ) )
		             ->getMock();

		$post->ID = random_int( 1, 100 );
		$post->method('get_id')->willReturn( $post->ID );

		\WP_Mock::wpFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $post->ID, '_order_currency', $expected_currency ]
		] );

		$subject->set_order_currency( $post, $request1 );

	}

	/**
	 * @test
	 */
	public function set_order_currency_no_currency(){
		$subject = $this->get_subject();

		$expected_currency = rand_str();
		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [] );

		\WP_Mock::wpFunction( 'get_woocommerce_currencies', [ 'times' => 0 ] );
		\WP_Mock::wpFunction( 'update_post_meta', [ 'times' => 0 ] );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods()
		             ->getMock();

		$subject->set_order_currency( $post, $request1 );

	}

	/**
	 * @test
	 */
	function auto_adjust_included_ids() {

		$subject = $this->get_subject();

		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get', 'set' ) )
		                 ->getMock();

		$wp_query->method( 'get' )->will( $this->returnCallback(
			function ( $var ) use ( $wp_query ) {
				return $wp_query->query_vars[ $var ];
			}
		) );
		$wp_query->method( 'set' )->will( $this->returnCallback(
			function ( $var, $value ) use ( $wp_query ) {
				return $wp_query->query_vars[ $var ] = $value;
			}
		) );

		$posts = [
			'original'    => [ rand( 1, 100 ), rand( 101, 200 ) ],
			'translation' => [ rand( 201, 300 ), rand( 301, 400 ) ]
		];

		// no adjusting
		$wp_query->set( 'lang', '' );
		$wp_query->set( 'post__in', false );
		$subject->auto_adjust_included_ids( $wp_query );
		$this->assertFalse( $wp_query->get( 'post__in' ) );

		// no adjusting
		$wp_query->set( 'lang', 'en' );
		$wp_query->set( 'post__in', false );
		$subject->auto_adjust_included_ids( $wp_query );
		$this->assertFalse( $wp_query->get( 'post__in' ) );

		// no adjusting
		$wp_query->set( 'lang', 'en' );
		$wp_query->set( 'post__in', $posts['original'] );
		$subject->auto_adjust_included_ids( $wp_query );
		$this->assertEquals( $posts['original'], $wp_query->get( 'post__in' ) );

		// adjusting
		\WP_Mock::wpFunction( 'get_post_type', array(
			'times'  => count( $posts['original'] ),
			'return' => function ( $id ) {
				return 'product';
			}

		) );
		\WP_Mock::onFilter( 'translate_object_id' )->with( $posts['original'][0], 'product', true )->reply( $posts['translation'][0] );
		\WP_Mock::onFilter( 'translate_object_id' )->with( $posts['original'][1], 'product', true )->reply( $posts['translation'][1] );

		$wp_query->set( 'lang', '' );
		$wp_query->set( 'post__in', $posts['original'] );
		$subject->auto_adjust_included_ids( $wp_query );
		$this->assertEquals( $posts['translation'], $wp_query->get( 'post__in' ) );

	}

	/**
	 * @test
	 */
	public function copy_product_custom_fields() {
		$subject = $this->get_subject();

		$response = $this->getMockBuilder( 'WP_REST_Response' )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$object = $this->getMockBuilder( 'WC_Product_Simple' )
		               ->disableOriginalConstructor()
		               ->getMock();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_params' ) )
		                ->getMock();

		$post_id = rand( 1, 1000 );
		$request->method( 'get_params' )->willReturn( array( 'id' => $post_id ) );

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'copy_custom_fields' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'copy_custom_fields' );


		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();
		$post_translations      = [ rand( 1000, 2000 ), rand( 2000, 3000 ) ];
		$wpml_post_translations->method( 'get_element_translations' )->willReturn( array( $post_translations ) );

		$subject = new WCML_REST_API_Support(
			$this->woocommerce_wpml,
			$sitepress,
			$this->wpdb,
			$this->query_filters_posts,
			$this->query_filters_orders,
			$this->query_filters_terms,
			$wpml_post_translations
		);
		$subject->copy_product_custom_fields( $response, $object, $request );

	}

	/**
	 * @test
	 */
	function test_initialize_with_default_lang_parameters_in_get() {

		$lang = 'en';
		$_SERVER['REQUEST_URI'] .= '?lang=' . $lang;

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_default_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_default_language' )->willReturn( $lang );

		$expected_request_uri = str_replace( 'lang=' . $lang, '', $_SERVER['REQUEST_URI'] );

		$subject     = new WCML_REST_API_Support(
			$this->woocommerce_wpml,
			$sitepress,
			$this->wpdb,
			$this->query_filters_posts,
			$this->query_filters_orders,
			$this->query_filters_terms,
			$this->wpml_post_translations
		);

		$this->assertEquals( $expected_request_uri, $_SERVER['REQUEST_URI'] );

	}

	/**
	 * @test
	 */
	function test_initialize_with_not_default_lang_parameters_in_get() {

		$lang = 'en';
		$_SERVER['REQUEST_URI'] .= '?lang=' . $lang;

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_default_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_default_language' )->willReturn( 'non-' . $lang );

		$expected_request_uri = $_SERVER['REQUEST_URI'];

		$subject     = new WCML_REST_API_Support(
			$this->woocommerce_wpml,
			$sitepress,
			$this->wpdb,
			$this->query_filters_posts,
			$this->query_filters_orders,
			$this->query_filters_terms,
			$this->wpml_post_translations
		);

		$this->assertEquals( $expected_request_uri, $_SERVER['REQUEST_URI'] );

	}

}