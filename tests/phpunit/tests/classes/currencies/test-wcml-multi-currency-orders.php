<?php

class Test_WCML_Multi_Currency_Orders extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var WCML_Multi_Currency */
	private $wcml_multi_currency;
	/** @var Sitepress */
	private $sitepress;
	/** @var WC_Order */
	private $order;

	private $is_admin = false;
	private $current_screen = '';

	private $post_meta = [];
	private $options = [];

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array() )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wcml_multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->order = $this->getMockBuilder( 'WC_Order' )
		                    ->disableOriginalConstructor()
							->setMethods( array( 'get_id', 'set_id' ) )
		                    ->getMock();
		$this->order->method( 'get_id' )->will( $this->returnCallback(
			function (){
				return $this->id;
			}
		) );
		$this->order->method( 'set_id' )->will( $this->returnCallback(
			function ( $id ){
				return $this->id = $id;
			}
		) );

	}

	public function tearDown() {
		unset( $this->sitepress, $this->woocommerce, $this->order );
		parent::tearDown();
	}

	/**
	 * @return WCML_REST_API_Support
	 */
	private function get_subject(){
		return new WCML_Multi_Currency_Orders( $this->wcml_multi_currency, $this->woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function get_currency_for_new_order(){

		$that = $this;
		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => function () use ( $that ) {
				return $that->is_admin;
			},
		) );
		$this->current_screen = new stdClass();
		$this->current_screen->id = '';
		\WP_Mock::wpFunction( 'get_current_screen', array(
			'return' => function () use ( $that ) {
				return $that->current_screen;
			},
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) use ( $that ) {
				return $that->post_meta[ $id ][ $meta ] = $value;
			},
		) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'return' => function ( $id, $meta, $single ) use ( $that ) {
				return $that->post_meta[ $id ][ $meta ];
			},
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) use ( $that ) {
				return $that->options[$option_name];
			},
		) );

		\WP_Mock::wpFunction( 'update_option', array(
			'return' => function ( $option_name, $option_value ) use ( $that ) {
				$that->options[$option_name] = $option_value;
			},
		) );

		$subject = $this->get_subject();

		$original_currency = rand_str();

		\WP_Mock::wpFunction( 'did_action', array(
			'args' => array( 'current_screen' ),
			'return' => true
		) );

		// Not admin
		$this->is_admin = false;
		$this->current_screen->id = '';
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $original_currency, $filtered_currency );


		// Not order page
		$this->is_admin = false;
		$this->current_screen->id = 'show_order';
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $original_currency, $filtered_currency );

		// Admin, order page, meta set (return original)
		$this->is_admin = true;
		$this->current_screen->id = 'shop_order';
		$this->order->set_id( rand(1, 1000) );
		update_post_meta( $this->order->get_id(), '_order_currency', 'EUR' );
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $original_currency, $filtered_currency );

		// Admin, order page, meta empty (return cookie) - No COOKIE
		$this->is_admin = true;
		$this->current_screen->id = 'shop_order';
		$this->order->set_id( rand(1, 1000) );
		update_post_meta( $this->order->get_id(), '_order_currency', false );
		update_option( 'woocommerce_currency', $wocommerce_currency = rand_str() );
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $wocommerce_currency, $filtered_currency );

		// Admin, order page, meta empty (return cookie) - Yes COOKIE
		$this->is_admin = true;
		$this->current_screen->id = 'shop_order';
		$this->order->set_id( rand(1, 1000) );
		update_post_meta( $this->order->get_id(), '_order_currency', false );
		$_COOKIE['_wcml_order_currency'] = rand_str();
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $_COOKIE['_wcml_order_currency'], $filtered_currency );

	}


	/**
	 * @test
	 */
	public function action_different_than_woocommerce_add_order_item(){
		$_POST['action'] = rand_str();

		$items = array();

		$subject = $this->get_subject();
		$filtered_items = $subject->set_totals_for_order_items( $items );

		$this->assertSame( $items, $filtered_items );
	}

	/**
	 * @test
	 */
	public function it_sets_custom_totals_for_order_items_for_ajax_add_new_order_item_call(){
		$_POST['action'] = 'woocommerce_add_order_item';
		$_POST['order_id'] = 1;
		$_POST['item_to_add'][0] = 10;
		$original_product_id = 20;
		$converted_price = 100;

		$order_currency = 'EUR';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'set_subtotal', 'set_total', 'save', 'get_quantity', 'get_product' ) )
		              ->getMock();

		$items = array( $item );

		$product_obj =  $this->getMockBuilder( 'WC_Product' )
		              ->disableOriginalConstructor()
		              ->getMock();

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $_POST['order_id'], '_order_currency', true ),
			'return' => $order_currency
		) );

		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product_obj, array( 'price' => $converted_price , 'qty' => 1 ) ),
			'return' => $converted_price
		) );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_id' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $original_product_id );


		\WP_Mock::wpFunction( 'sanitize_text_field', array(
			'args' => array( $_POST['item_to_add'][0] ),
			'return' => $_POST['item_to_add'][0]
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_price_' . $order_currency, true ),
			'return' => $converted_price
		) );

		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'get_quantity' )->willReturn( 1 );
		$item->method( 'get_product' )->willReturn( $product_obj );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );


		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items );
	}

	/**
	 * @test
	 */
	public function it_sets_converted_totals_for_order_items_for_ajax_add_new_order_item_call(){
		$_POST['action'] = 'woocommerce_add_order_item';
		$_POST['order_id'] = 2;
		$_POST['item_to_add'][0] = 11;
		$original_product_id = 21;
		$subtotal = 100;
		$total = 101;
		$converted_price = 102;

		$order_currency = 'EUR';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'set_subtotal', 'set_total', 'save', 'meta_exists', 'add_meta_data', 'get_subtotal', 'get_total','get_quantity' ) )
		              ->getMock();

		$items = array( $item );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $_POST['order_id'], '_order_currency', true ),
			'return' => $order_currency
		) );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_id' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $original_product_id );


		$this->wcml_multi_currency->prices = $this->getMockBuilder( 'WCML_Prices' )
		                             ->disableOriginalConstructor()
		                             ->setMethods( array( 'raw_price_filter' ) )
		                             ->getMock();

		\WP_Mock::wpFunction( 'sanitize_text_field', array(
			'args' => array( $_POST['item_to_add'][0] ),
			'return' => $_POST['item_to_add'][0]
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_price_' . $order_currency, true ),
			'return' => false
		) );

		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'meta_exists' )->willReturn( false );
		$item->method( 'add_meta_data' )->willReturn( true );
		$item->method( 'get_quantity' )->willReturn( 1 );
		$item->method( 'get_subtotal' )->willReturn( $subtotal );
		$item->method( 'get_total' )->willReturn( $total );
		$this->wcml_multi_currency->prices->method( 'raw_price_filter' )->willReturn( $converted_price );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );

		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items );
	}

	/**
	 * @test
	 */
	public function it_adds_woocommerce_hidden_order_itemmeta(){

		$itemmeta = array();

		$subject = $this->get_subject();
		$filtered_itemmeta = $subject->add_woocommerce_hidden_order_itemmeta( $itemmeta );

		$this->assertSame( array( '_wcml_converted_subtotal', '_wcml_converted_total'), $filtered_itemmeta );
	}
}
