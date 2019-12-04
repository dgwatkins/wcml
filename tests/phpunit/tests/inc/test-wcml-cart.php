<?php

/**
 * Class Test_WCML_Cart
 */
class Test_WCML_Cart extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var woocommerce */
	private $woocommerce;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;


	private $cart_clear_constant;
	private $cookie_setting_field;
	private $mc_independent;


	public function setUp(){
		parent::setUp();

		\WP_Mock::passthruFunction( '__' );
		\WP_Mock::passthruFunction( 'esc_html__' );
		\WP_Mock::passthruFunction( 'esc_url' );

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_element_trid', 'get_setting' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();

		$this->woocommerce = $this->getMockBuilder( 'woocommerce' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_subject( ){

		return new WCML_Cart( $this->woocommerce_wpml, $this->sitepress, $this->woocommerce );

	}

	/**
	 * @test
	 */
	public function it_adds_correct_hooks_when_clean_cart_is_disabled(){

		\WP_Mock::userFunction( 'is_ajax', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'woocommerce_get_cart_item_from_session', array( $subject, 'translate_cart_contents' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_correct_hooks_when_clean_cart_is_enabled(){

		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wp_enqueue_style', array( 'return' => true ) );

		$this->cart_clear_constant = 0;
		$this->mc_independent = 2;
		$cart_sync_constant = 1;
		$this->cookie_setting_field = rand_str();

		$that = $this;
		$this->wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' == $const ) {
				return $that->cookie_setting_field;
			} else if ( 'WCML_CART_CLEAR' == $const ) {
				return $that->cart_clear_constant;
			} else if ( 'WCML_MULTI_CURRENCIES_INDEPENDENT' == $const ) {
				return $that->mc_independent;
			}
		} );

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( true );

		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $this->cart_clear_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $cart_sync_constant;
		$this->woocommerce_wpml->settings['enable_multi_currency'] = $this->mc_independent;

		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'wcml_removed_cart_items', array( $subject, 'wcml_removed_cart_items_widget' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_correct_hooks_when_clean_cart_is_enabled_for_currency_switching(){

		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wp_enqueue_style', array( 'return' => true ) );

		$this->cart_clear_constant = 0;
		$this->mc_independent = 2;
		$cart_sync_constant = 1;
		$this->cookie_setting_field = rand_str();

		$that = $this;
		$this->wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' == $const ) {
				return $that->cookie_setting_field;
			} else if ( 'WCML_CART_CLEAR' == $const ) {
				return $that->cart_clear_constant;
			} else if ( 'WCML_MULTI_CURRENCIES_INDEPENDENT' == $const ) {
				return $that->mc_independent;
			}
		} );

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( true );

		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $cart_sync_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $this->cart_clear_constant;
		$this->woocommerce_wpml->settings['enable_multi_currency'] = $this->mc_independent;

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_switch_currency_exception', array( $subject, 'cart_switching_currency' ), 10, 4 );
		\WP_Mock::expectActionAdded( 'wcml_before_switch_currency', array( $subject, 'switching_currency_empty_cart_if_needed' ), 10, 2 );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function translate_cart_contents(){
		$subject = $this->get_subject();

		$product_id = rand( 1, 100 );
		$product_title = rand_str();

		$cart_item = array();
		$cart_item[ 'product_id' ] = $product_id;
		$cart_item[ 'variation_id' ] = '';

		\WP_Mock::userFunction( 'get_the_title', array(
			'args' => $product_id,
			'return' => $product_title
		) );

		$product_object = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->setMethods( array( 'set_name' ) )
			->getMock();

		$product_object->expects( $this->once() )->method( 'set_name' )->with( $product_title )->willReturn( true );

		$cart_item[ 'data' ] = $product_object;

		$translated_cart_item = $subject->translate_cart_contents( $cart_item );

		$expected_cart_item = $cart_item;
		$expected_cart_item[ 'data_hash' ] = '';

		$this->assertEquals( $expected_cart_item, $translated_cart_item );
	}

	/**
	 * @test
	 */
	public function it_should_add_to_cart_sold_individually_exception() {

		$product_id     = 10;
		$variation_id   = 11;
		$trid           = 12;
		$cart_item_data = [];
		$post_type      = 'product_variation';

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $variation_id,
			'return' => $post_type
		] );

		$this->sitepress->method( 'get_element_trid' )->with( $variation_id, 'post_' . $post_type )->willReturn( $trid );

		$woocommerce = $this->getMockBuilder( 'woocommerce' )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$woocommerce->cart = $this->getMockBuilder( 'WC_Cart' )
		                          ->disableOriginalConstructor()
		                          ->getMock();

		$cart_item                 = [];
		$cart_item['variation_id'] = $variation_id;
		$cart_item['quantity']     = 1;

		$woocommerce->cart->cart_contents = [ $cart_item ];

		\WP_Mock::userFunction( 'WC', [
			'return' => $woocommerce,
			'times'  => 1
		] );

		$subject = $this->get_subject();
		$this->assertTrue( $subject->add_to_cart_sold_individually_exception( false, $product_id, $variation_id, $cart_item_data ) );
	}


	/**
	 * @test
	 */
	public function it_should_not_add_to_cart_sold_individually_exception() {

		$product_id     = 10;
		$variation_id   = '';
		$cart_item_data = [];
		$post_type      = 'product';

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $product_id,
			'return' => $post_type
		] );

		$woocommerce = $this->getMockBuilder( 'woocommerce' )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$woocommerce->cart = $this->getMockBuilder( 'WC_Cart' )
		                          ->disableOriginalConstructor()
		                          ->getMock();

		$cart_item               = [];
		$cart_item['product_id'] = 11;
		$cart_item['quantity']   = 1;

		$woocommerce->cart->cart_contents = [ $cart_item ];

		$this->sitepress->method( 'get_element_trid' )->willReturnCallback(
			function ( $product_id ) {
				return $product_id;
			}
		);

		\WP_Mock::userFunction( 'WC', [
			'return' => $woocommerce,
			'times'  => 1
		] );

		$subject = $this->get_subject();
		$this->assertFalse( $subject->add_to_cart_sold_individually_exception( false, $product_id, $variation_id, $cart_item_data ) );
	}

	/**
	 * @test
	 */
	function cart_alert_hide_dialog(){

		$this->wp_api->expects( $this->once() )
		             ->method( 'constant' )
		             ->with( 'WCML_CART_CLEAR' )
		             ->willReturn( rand_str() );

		\WP_Mock::onFilter( 'wcml_hide_cart_alert_dialog' )->with( false )->reply( true );

		$subject = $this->get_subject();

		$alert_dialog = $subject->cart_alert( rand_str(), rand_str(), rand_str(), rand_str(), rand_str() );

		$this->assertFalse( $alert_dialog );

	}

	/**
	 * @test
	 */
	public function is_clean_cart_enabled_wpml_cookies_enabled() {

		$subject = $this->clean_cart_subject_mock();

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( true );

		$this->assertTrue( $subject->is_clean_cart_enabled() );
	}

	/**
	 * @test
	 */
	public function is_clean_cart_enabled_wpml_cookies_disabled() {

		$subject = $this->clean_cart_subject_mock();

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( false );

		$this->assertFalse( $subject->is_clean_cart_enabled() );
	}

	/**
	 * @test
	 */
	public function is_clean_cart_enabled_wpml_mc_disabled() {

		$subject = $this->clean_cart_subject_mock();

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( true );
		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = 1;
 		$this->woocommerce_wpml->settings['enable_multi_currency'] = 0;

		$this->assertFalse( $subject->is_clean_cart_enabled() );
	}

	/**
	 * @test
	 */
	public function is_clean_cart_enabled_wpml_mc_enabled() {

		$subject = $this->clean_cart_subject_mock();

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( true );

		$this->assertTrue( $subject->is_clean_cart_enabled() );
	}

	private function clean_cart_subject_mock(){

		$this->cart_clear_constant = 0;
		$this->cookie_setting_field = rand_str();
		$this->mc_independent = 2;

		$that = $this;
		$this->wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' == $const ) {
				return $that->cookie_setting_field;
			} else if ( 'WCML_CART_CLEAR' == $const ) {
				return $that->cart_clear_constant;
			} else if ( 'WCML_MULTI_CURRENCIES_INDEPENDENT' == $const ) {
				return $that->mc_independent;
			}
		} );


		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $this->cart_clear_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $this->cart_clear_constant;
		$this->woocommerce_wpml->settings['enable_multi_currency'] = $this->mc_independent;

		return $this->get_subject();

	}


	/**
	 * @test
	 */
	public function cart_item_permalink_auto_adjust_ids_on() {

		$subject = $this->get_subject();
		$permalink = rand_str();

		$this->sitepress->method( 'get_setting' )->with( 'auto_adjust_ids' )->willReturn( true );

		$this->assertEquals( $permalink, $subject->cart_item_permalink( $permalink, array() ) );
	}

	/**
	 * @test
	 */
	public function cart_item_permalink_auto_adjust_ids_off() {

		$subject = $this->get_subject();
		$permalink = rand_str();
		$translated_permalink = rand_str();
		$cart_item = array();
		$cart_item['product_id'] = mt_rand( 1, 100 );

		\WP_Mock::userFunction( 'get_permalink', array(
			'args' => array( $cart_item['product_id'] ),
			'return' => $translated_permalink,
		) );

		$this->sitepress->method( 'get_setting' )->with( 'auto_adjust_ids' )->willReturn( false );

		$this->assertEquals( $translated_permalink, $subject->cart_item_permalink( $permalink, $cart_item ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_get_cart_attribute_translation_when_variation_is_empty() {

		$attr_key = rand_str();
		$attribute = rand_str();
		$current_language = rand_str();
		$variation_id = 0;
		$product_id = mt_rand( 1, 10 );
		$tr_product_id = mt_rand( 11, 20 );

		\WP_Mock::userFunction( 'taxonomy_exists', array(
			'args' => array( $attr_key ),
			'return' => false
		) );

		$subject = $this->get_subject();
		$this->assertEquals( $attribute, $subject->get_cart_attribute_translation( 'attribute_'.$attr_key, $attribute, $variation_id, $current_language, $product_id, $tr_product_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_data_cart_hash_from_variation_if_exists() {

		$cart_item[ 'variation_id' ] = 10;
		$cart_item[ 'product_id' ] = 20;

		$product = $this->getMockBuilder( 'WC_Product_Variation' )
		                ->disableOriginalConstructor()
		                ->getMock();

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args' => array( $cart_item[ 'variation_id' ] ),
			'return' => $product
		) );

		$hash = rand_str();

		\WP_Mock::userFunction( 'wc_get_cart_item_data_hash', array(
			'args' => array( $product ),
			'return' => $hash
		) );

		$subject = $this->get_subject();
		$this->assertEquals( $hash, $subject->get_data_cart_hash( $cart_item ) );

	}

	/**
	 * @test
	 */
	public function it_should_get_data_cart_hash_from_product_if_variation_does_not_exists() {

		$cart_item[ 'variation_id' ] = '';
		$cart_item[ 'product_id' ] = 10;

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->getMock();

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args' => array( $cart_item[ 'product_id' ] ),
			'return' => $product
		) );

		$hash = rand_str();

		\WP_Mock::userFunction( 'wc_get_cart_item_data_hash', array(
			'args' => array( $product ),
			'return' => $hash
		) );

		$subject = $this->get_subject();
		$this->assertEquals( $hash, $subject->get_data_cart_hash( $cart_item ) );

	}

	/**
	 * @test
	 * @dataProvider get_cart_contents
	 *
	 * @param array $cart_contents
	 */
	public function it_should_get_formatted_cart_total_in_currency( $cart_contents ) {

		$currency        = 'EUR';
		$client_currency = 'USD';
		$converted_product_price  = 10;
		$shipping_total           = 10;
		$converted_shipping_total = 100;
		$converted_cart_total     = ( $converted_product_price * $cart_contents['quantity'] ) + $converted_shipping_total;
		$formatted_cart_total     = $converted_cart_total . ' €';

		$wc = $this->getMockBuilder( 'WC' )
		           ->disableOriginalConstructor()
		           ->getMock();

		$wc->cart = $this->getMockBuilder( 'WC_Cart' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_cart_contents', 'get_shipping_total' ) )
		                 ->getMock();

		$wc->cart->method( 'get_cart_contents' )->willReturn( [ $cart_contents ] );
		$wc->cart->method( 'get_shipping_total' )->willReturn( $shipping_total );

		WP_Mock::userFunction( 'WC', array(
			'return' => $wc
		) );


		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$this->woocommerce_wpml->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                                       ->disableOriginalConstructor()
		                                                       ->setMethods( array(
			                                                       'format_price_in_currency',
			                                                       'get_product_price_in_currency',
			                                                       'unconvert_price_amount',
			                                                       'apply_rounding_rules',
			                                                       'convert_price_amount'
		                                                       ) )
		                                                       ->getMock();

		$item_product_id = $cart_contents[ 'variation_id' ] ? $cart_contents[ 'variation_id' ] : $cart_contents[ 'product_id' ];
		$this->woocommerce_wpml->multi_currency->prices->method( 'get_product_price_in_currency' )->with( $item_product_id, $currency )->willReturn( $converted_product_price );
		$this->woocommerce_wpml->multi_currency->prices->method( 'unconvert_price_amount' )->with( $shipping_total )->willReturn( $shipping_total );
		$this->woocommerce_wpml->multi_currency->prices->method( 'convert_price_amount' )->with( $shipping_total, $currency )->willReturn( $converted_shipping_total );
		$this->woocommerce_wpml->multi_currency->prices->method( 'apply_rounding_rules' )->with( $converted_cart_total, $currency )->willReturn( $converted_cart_total );
		$this->woocommerce_wpml->multi_currency->prices->method( 'format_price_in_currency' )->with( $converted_cart_total, $currency )->willReturn( $formatted_cart_total );

		$subject = $this->get_subject();
		$this->assertEquals( $formatted_cart_total, $subject->get_formatted_cart_total_in_currency( $currency ) );
	}

	public function get_cart_contents(){
		return [
			[
				[
					'product_id'   => 11,
					'variation_id' => 0,
					'quantity'     => 1
				]
			],
			[
				[
					'product_id'   => 12,
					'variation_id' => 14,
					'quantity'     => 2
				]
			]
		];
	}

}
