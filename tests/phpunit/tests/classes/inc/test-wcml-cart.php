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


	public function setUp(){
		parent::setUp();

		\WP_Mock::wpPassthruFunction( '__' );
		\WP_Mock::wpPassthruFunction( 'esc_html__' );
		\WP_Mock::wpPassthruFunction( 'esc_url' );

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

		\WP_Mock::wpFunction( 'is_ajax', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'woocommerce_get_cart_item_from_session', array( $subject, 'translate_cart_contents' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_needs_payment', array( $subject, 'use_cart_contents_total_for_needs_payment' ), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_correct_hooks_when_clean_cart_is_enabled(){

		\WP_Mock::wpFunction( 'wp_enqueue_script', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'wp_enqueue_style', array( 'return' => true ) );

		$this->cart_clear_constant = 0;
		$cart_sync_constant = 1;
		$this->cookie_setting_field = rand_str();

		$that = $this;
		$this->wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' == $const ) {
				return $that->cookie_setting_field;
			} else if ( 'WCML_CART_CLEAR' == $const ) {
				return $that->cart_clear_constant;
			}
		} );

		$this->sitepress->method( 'get_setting' )->with( $this->cookie_setting_field )->willReturn( true );

		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $this->cart_clear_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $cart_sync_constant;

		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'wcml_removed_cart_items', array( $subject, 'wcml_removed_cart_items_widget' ) );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function translate_cart_contents(){

		$check_wc_version = '3.0.0';
		$wc_version = '3.0.0';
		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );

		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_wc_version, '>=' )
			->willReturn( true );

		$subject = $this->get_subject();

		$product_id = rand( 1, 100 );
		$product_title = rand_str();

		$cart_item = array();
		$cart_item[ 'product_id' ] = $product_id;
		$cart_item[ 'variation_id' ] = '';

		\WP_Mock::wpFunction( 'get_the_title', array(
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

	}

	/**
	 * @test
	 */
	public function translate_cart_contents_for_deprecated_wc(){

		$product_id = rand( 1, 100 );
		$variation_id = rand( 1, 100 );
		$variation_title = rand_str();
		$check_wc_version = '3.0.0';
		$wc_version = '2.7.0';

		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );

		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_wc_version, '>=' )
			->willReturn( false );

		$subject = $this->get_subject();

		$cart_item = array();
		$cart_item[ 'product_id' ] = $product_id;
		$cart_item[ 'variation_id' ] = $variation_id;

		\WP_Mock::wpFunction( 'get_the_title', array(
			'args' => $variation_id,
			'return' => $variation_title
		) );

		$cart_item[ 'data' ] = new stdClass();
		$cart_item[ 'data' ]->post = new stdClass();
		$cart_item[ 'data' ]->post->post_title = rand_str();

		$translated_cart_item = $subject->translate_cart_contents( $cart_item );

		$this->assertEquals( $variation_title, $translated_cart_item[ 'data' ]->post->post_title );

	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	public function add_to_cart_sold_individually_exception(){

		$qt = mt_rand( 1, 100 );
		$quantity = mt_rand( 1, 100 );
		$product_id = mt_rand( 1, 100 );
		$variation_id = mt_rand( 1, 100 );
		$cart_item_data = array();
		$post_type = 'product_variation';

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args' => $variation_id,
			'return' => $post_type
		) );

		$this->sitepress->method('get_element_trid')->with( $variation_id, 'post_'.$post_type );

		$woocommerce = $this->getMockBuilder( 'woocommerce' )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$woocommerce->cart = $this->getMockBuilder( 'WC_Cart' )
		                          ->disableOriginalConstructor()
		                          ->getMock();

		$cart_item = array();
		$cart_item['variation_id'] = $variation_id;
		$cart_item['quantity'] = mt_rand( 1, 10 );

		$woocommerce->cart->cart_contents = array( $cart_item );

		\WP_Mock::wpFunction( 'WC', array(
			'return' => $woocommerce,
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'get_the_title', array(
			'args' => array( $variation_id ),
			'return' => rand_str(),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'wc_get_cart_url', array(
			'return' => rand_str(),
			'times' => 1
		) );

		$subject = $this->get_subject();
		$subject->add_to_cart_sold_individually_exception( $qt, $quantity, $product_id, $variation_id, $cart_item_data );

	}

	/**
	 * @test
	 */
	public function use_cart_contents_total_for_needs_payment_does_need() {
		$subject = $this->get_subject();
		$needs = false;
		$cart = $this->getMockBuilder( 'WC_Cart' )->disableOriginalConstructor()->getMock();

		$cart->cart_contents_total = random_int( 1, 100 );

		$wc = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.1';
		WP_Mock::userFunction( 'WC', ['times' => 1, 'return' => $wc] );

		$this->assertTrue( $subject->use_cart_contents_total_for_needs_payment( $needs, $cart ) );

		unset($cart->cart_contents_total);
		$cart->total = random_int( 1, 100 );
		WP_Mock::userFunction( 'WC', ['times' => 1, 'return' => $wc] );
		$this->assertTrue( $subject->use_cart_contents_total_for_needs_payment( $needs, $cart ) );
	}

	/**
	 * @test
	 */
	public function use_cart_contents_total_for_needs_payment_doesnt_need() {
		$subject = $this->get_subject();
		$needs = false;
		$cart = $this->getMockBuilder( 'WC_Cart' )->disableOriginalConstructor()->getMock();

		$wc = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.1';
		WP_Mock::userFunction( 'WC', ['times' => 1, 'return' => $wc] );

		$cart->cart_contents_total = 0;

		$this->assertFalse( $subject->use_cart_contents_total_for_needs_payment( $needs, $cart ) );
	}

	/**
	 * @test
	 */
	public function use_cart_contents_total_for_needs_payment_dont_filter_when_woocommerce_32() {
		$subject = $this->get_subject();
		$needs = rand_str( 32 );
		$cart = $this->getMockBuilder( 'WC_Cart' )->disableOriginalConstructor()->getMock();

		$wc = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.2';
		WP_Mock::userFunction( 'WC', ['times' => 1, 'return' => $wc] );
		$cart->cart_contents_total = random_int( 1, 100 );

		$this->assertSame( $needs, $subject->use_cart_contents_total_for_needs_payment( $needs, $cart ) );
	}

	/**
	 * @test
	 */
	public function use_cart_contents_total_for_needs_payment_doesnt_filter() {
		$subject = $this->get_subject();
		$needs = rand_str( 32 );
		$cart = $this->getMockBuilder( 'WC_Cart' )->disableOriginalConstructor()->getMock();

		$wc = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.2';
		WP_Mock::userFunction( 'WC', ['times' => 1, 'return' => $wc] );

		$this->assertSame( $needs, $subject->use_cart_contents_total_for_needs_payment( $needs, $cart ) );
	}

	/**
	 * @test
	 */
	public function use_cart_contents_total_for_needs_payment_recurring_payment() {
		$subject = $this->get_subject();
		$needs = false;
		$cart = $this->getMockBuilder( 'WC_Cart' )->disableOriginalConstructor()->getMock();

		$wc = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.1';
		WP_Mock::userFunction( 'WC', ['times' => 1, 'return' => $wc] );

		$cart->recurring_carts = array();

		$this->assertTrue( $subject->use_cart_contents_total_for_needs_payment( $needs, $cart ) );
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


	private function clean_cart_subject_mock(){

		$this->cart_clear_constant = 0;
		$this->cookie_setting_field = rand_str();

		$that = $this;
		$this->wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' == $const ) {
				return $that->cookie_setting_field;
			} else if ( 'WCML_CART_CLEAR' == $const ) {
				return $that->cart_clear_constant;
			}
		} );

		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $this->cart_clear_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $this->cart_clear_constant;

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

		\WP_Mock::wpFunction( 'get_permalink', array(
			'args' => array( $cart_item['product_id'] ),
			'return' => $translated_permalink,
		) );

		$this->sitepress->method( 'get_setting' )->with( 'auto_adjust_ids' )->willReturn( false );

		$this->assertEquals( $translated_permalink, $subject->cart_item_permalink( $permalink, $cart_item ) );
	}

}
