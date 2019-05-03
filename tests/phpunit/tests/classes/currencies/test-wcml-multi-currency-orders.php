<?php

class Test_WCML_Multi_Currency_Orders extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var WCML_Multi_Currency */
	private $wcml_multi_currency;
	/** @var WC_Order */
	private $order;
	/** @var WP $wp */
	private $wp;

	private $is_admin = false;
	private $current_screen = '';

	private $post_meta = [];
	private $options = [];

	public function setUp() {
		parent::setUp();

		$this->wp = $this->getMockBuilder( 'WP' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'add_query_var' ) )
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
		return new WCML_Multi_Currency_Orders( $this->wcml_multi_currency, $this->woocommerce_wpml, $this->wp );
	}

	/**
	 * @test
	 */
	public function it_adds_hooks(){

		WP_Mock::userFunction( 'is_admin', array(
			'return' => true,
			'times'  => 2
		) );

		WP_Mock::userFunction( 'current_user_can', array(
			'return' => false,
			'times'  => 3
		) );

		$subject = $this->get_subject();

		$this->expectFilterAdded( 'woocommerce_order_get_items', array( $subject, 'set_totals_for_order_items' ), 10, 2 );

		$subject->orders_init();
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
		$order =  $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_items' ) )
		              ->getMock();
		$order->method( 'get_items' )->with('coupon')->willReturn( array() );

		$subject = $this->get_subject();
		$filtered_items = $subject->set_totals_for_order_items( $items, $order );

		$this->assertSame( $items, $filtered_items );
	}

	/**
	 * @test
	 */
	public function it_sets_custom_totals_for_order_items_for_ajax_add_new_order_item_call(){
		\WP_Mock::passthruFunction( 'remove_filter' );
		$_POST['action'] = 'woocommerce_add_order_item';
		$_POST['order_id'] = 1;
		$product_id = 10;
		$original_product_id = 20;
		$original_price = 50;
		$converted_price = 100;

		$order_currency = 'EUR';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'get_total', 'get_subtotal', 'set_subtotal', 'set_total', 'save', 'get_quantity', 'get_product', 'get_product_id', 'get_variation_id', 'update_meta_data' ) )
		              ->getMock();

		$items = array( $item );

		$order =  $this->getMockBuilder( 'WC_Order' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_items' ) )
		               ->getMock();
		$order->method( 'get_items' )->with('coupon')->willReturn( array() );

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


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_price_' . $order_currency, true ),
			'return' => $converted_price
		) );

		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product_obj, array( 'price' => $converted_price ) ),
			'return' => $converted_price
		) );

		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'get_quantity' )->willReturn( 1 );
		$item->method( 'get_total' )->willReturn( $original_price );
		$item->method( 'get_subtotal' )->willReturn( $original_price );
		$item->method( 'get_product' )->willReturn( $product_obj );
		$item->method( 'get_product_id' )->willReturn( $product_id );
		$item->method( 'get_variation_id' )->willReturn( 0 );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );


		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items, $order );
	}

	/**
	 * @test
	 */
	public function it_sets_variation_custom_totals_for_order_items_for_ajax_add_new_order_item_call(){
		\WP_Mock::passthruFunction( 'remove_filter' );
		$_POST['action'] = 'woocommerce_add_order_item';
		$_POST['order_id'] = 1;
		$variation_id = 20;
		$original_variation_id = 30;
		$original_price = 50;
		$converted_price = 100;

		$order_currency = 'EUR';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'get_total', 'get_subtotal', 'set_subtotal', 'set_total', 'save', 'get_quantity', 'get_product', 'get_variation_id', 'update_meta_data' ) )
		              ->getMock();

		$items = array( $item );

		$order =  $this->getMockBuilder( 'WC_Order' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_items' ) )
		               ->getMock();
		$order->method( 'get_items' )->with('coupon')->willReturn( array() );

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

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $original_variation_id );


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_variation_id, '_price_' . $order_currency, true ),
			'return' => $converted_price
		) );

		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product_obj, array( 'price' => $converted_price ) ),
			'return' => $converted_price
		) );

		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'get_quantity' )->willReturn( 1 );
		$item->method( 'get_total' )->willReturn( $original_price );
		$item->method( 'get_subtotal' )->willReturn( $original_price );
		$item->method( 'get_product' )->willReturn( $product_obj );
		$item->method( 'get_variation_id' )->willReturn( $variation_id );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );


		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items, $order );
	}

	/**
	 * @test
	 */
	public function it_sets_discounted_total_price_for_converted_totals_for_order_items_for_ajax_add_new_order_item_call(){
		\WP_Mock::passthruFunction( 'remove_filter' );
		$_POST['action'] = 'woocommerce_add_order_item';
		$_POST['order_id'] = 2;
		$product_id = 11;
		$original_product_id = 21;
		$original_price = 110;
		$subtotal = $original_price;
		$discounted_total = 100;
		$converted_price = 120;

		$order_currency = 'EUR';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'set_subtotal', 'set_total', 'save', 'meta_exists', 'add_meta_data', 'update_meta_data', 'get_subtotal', 'get_total', 'get_quantity', 'get_product', 'get_product_id', 'get_variation_id' ) )
		              ->getMock();

		$items = array( $item );

		$order =  $this->getMockBuilder( 'WC_Order' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_items' ) )
		               ->getMock();
		$order->method( 'get_items' )->with('coupon')->willReturn( array() );

		$product_obj =  $this->getMockBuilder( 'WC_Product' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'get_price' ) )
		                     ->getMock();

		$product_obj->method( 'get_price' )->willReturn( $original_price );

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

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_price_' . $order_currency, true ),
			'return' => false
		) );


		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product_obj, array( 'price' => $converted_price ) ),
			'return' => $converted_price
		) );

		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'meta_exists' )->willReturn( false );
		$item->method( 'add_meta_data' )->willReturn( true );
		$item->method( 'update_meta_data' )->willReturn( true );
		$item->method( 'get_quantity' )->willReturn( 1 );
		$item->method( 'get_subtotal' )->willReturn( $subtotal );
		$item->method( 'get_total' )->willReturn( $discounted_total );
		$item->method( 'get_product' )->willReturn( $product_obj );
		$item->method( 'get_product_id' )->willReturn( $product_id );
		$item->method( 'get_variation_id' )->willReturn( 0 );
		$this->wcml_multi_currency->prices->method( 'raw_price_filter' )->willReturn( $converted_price );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $discounted_total )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );

		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items, $order );
	}

	/**
	 * @test
	 */
	public function it_updates_order_currency_and_converted_totals_for_order_items_for_ajax_save_order_items_call(){
		\WP_Mock::passthruFunction( 'remove_filter' );
		$_POST['action']     = 'woocommerce_save_order_items';
		$_POST['order_id']   = 3;

		$product_id          = 10;
		$original_product_id = 9;

		$this->subtotal      = 100;
		$this->total         = 100;
		$item_price          = 10;

		$this->old_quantity  = 10;
		$new_quantity        = 15;

		$expected_total      = 150;
		$expected_subtotal   = 150;

		$_COOKIE['_wcml_order_currency'] = 'USD';
		$order_currency = '';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'get_meta', 'set_subtotal', 'set_total', 'save', 'meta_exists', 'update_meta_data', 'get_subtotal', 'get_total', 'get_quantity', 'get_product_id', 'get_variation_id', 'get_product' ) )
		              ->getMock();

		$items = array( $item );

		$order =  $this->getMockBuilder( 'WC_Order' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_items' ) )
		               ->getMock();
		$order->method( 'get_items' )->with('coupon')->willReturn( array() );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $_POST['order_id'], '_order_currency', true ),
			'return' => $order_currency
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args' => array( $_POST['order_id'], '_order_currency', $_COOKIE['_wcml_order_currency'] ),
			'times' => 1,
			'return' => true
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

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_price' ) )
		                ->getMock();
		$product->method( 'get_price' )->willReturn( $item_price );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_price_' . $order_currency, true ),
			'return' => false
		) );

		$that = $this;
		$item->method( 'get_meta' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( '_wcml_converted_total' === $const ) {
				return $that->total;
			} else if ( '_wcml_converted_subtotal' === $const ) {
				return $that->subtotal;
			} else if ( '_wcml_total_qty' === $const ) {
				return $that->old_quantity;
			}
		} );

		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product, array( 'price' => $item_price ) ),
			'return' => $item_price
		) );

		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'meta_exists' )->willReturn( true );
		$item->method( 'update_meta_data' )->willReturn( true );
		$item->method( 'get_quantity' )->willReturn( $new_quantity );
		$item->method( 'get_subtotal' )->willReturn( $this->subtotal );
		$item->method( 'get_total' )->willReturn( $this->total );
		$item->method( 'get_product_id' )->willReturn( $product_id );
		$item->method( 'get_product' )->willReturn( $product );
		$item->method( 'get_variation_id' )->willReturn( 0 );
		$this->wcml_multi_currency->prices->method( 'raw_price_filter' )->willReturn( $item_price );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $expected_total )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $expected_subtotal )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );

		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items, $order );
	}

	/**
	 * @test
	 */
	public function it_sets_custom_totals_with_discount_for_order_items_if_coupon_applied(){
		\WP_Mock::passthruFunction( 'remove_filter' );
		$_POST['action'] = 'woocommerce_add_order_item';
		$_POST['order_id'] = 1;
		$product_id = 10;
		$original_product_id = 20;
		$original_price = 100;
		$coupon_discount = 11;
		$converted_price = 110;
		$price_with_coupon_discount = $converted_price - $coupon_discount;

		$order_currency = 'EUR';

		$item =  $this->getMockBuilder( 'WC_Order_Item_Product' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_type', 'get_total', 'get_subtotal', 'set_subtotal', 'set_total', 'save', 'get_quantity', 'get_product', 'get_product_id', 'get_variation_id', 'update_meta_data', 'meta_exists' ) )
		              ->getMock();

		$items = array( $item );

		$coupon_data['code'] = 'coupon1';
		$order_item_coupon =  $this->getMockBuilder( 'WC_Order_Item_Coupon' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( array( 'get_data' ) )
		                      ->getMock();
		$order_item_coupon->method( 'get_data' )->willReturn( $coupon_data );

		$wc_coupon = \Mockery::mock( 'overload:WC_Coupon' );
		$wc_coupon->shouldReceive( 'is_type' )->with( 'percent' )->andReturn( true );
		$wc_coupon->shouldReceive( 'get_discount_amount' )->with( $converted_price )->andReturn( $coupon_discount );

		$order =  $this->getMockBuilder( 'WC_Order' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_items' ) )
		               ->getMock();
		$order->method( 'get_items' )->with('coupon')->willReturn( array( $order_item_coupon ) );

		$product_obj =  $this->getMockBuilder( 'WC_Product' )
		                     ->disableOriginalConstructor()
							 ->setMethods( array( 'get_price' ) )
		                     ->getMock();
		$product_obj->method( 'get_price' )->willReturn( $original_price );

		$this->wcml_multi_currency->prices = $this->getMockBuilder( 'WCML_Prices' )
		                                          ->disableOriginalConstructor()
		                                          ->setMethods( array( 'raw_price_filter' ) )
		                                          ->getMock();

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $_POST['order_id'], '_order_currency', true ),
			'return' => $order_currency
		) );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_id' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $original_product_id );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_price_' . $order_currency, true ),
			'return' => false
		) );

		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product_obj, array( 'price' => $converted_price ) ),
			'return' => $converted_price
		) );

		\WP_Mock::wpFunction( 'wc_get_price_excluding_tax', array(
			'args' => array( $product_obj, array( 'price' => $price_with_coupon_discount ) ),
			'return' => $price_with_coupon_discount
		) );

		$item->method( 'meta_exists' )->willReturn( false );
		$item->method( 'get_type' )->willReturn( 'line_item' );
		$item->method( 'get_quantity' )->willReturn( 1 );
		$item->method( 'get_total' )->willReturn( $original_price );
		$item->method( 'get_subtotal' )->willReturn( $original_price );
		$item->method( 'get_product' )->willReturn( $product_obj );
		$item->method( 'get_product_id' )->willReturn( $product_id );
		$item->method( 'get_variation_id' )->willReturn( 0 );
		$this->wcml_multi_currency->prices->method( 'raw_price_filter' )->willReturn( $converted_price );
		$item->expects( $this->once() )->method( 'set_subtotal' )->with( $converted_price )->willReturn( true );
		$item->expects( $this->once() )->method( 'set_total' )->with( $price_with_coupon_discount )->willReturn( true );
		$item->expects( $this->once() )->method( 'save' )->willReturn( true );


		$subject = $this->get_subject();
		$subject->set_totals_for_order_items( $items, $order );
	}

	/**
	 * @test
	 */
	public function it_adds_woocommerce_hidden_order_itemmeta(){

		$itemmeta = array();

		$subject = $this->get_subject();
		$filtered_itemmeta = $subject->add_woocommerce_hidden_order_itemmeta( $itemmeta );

		$this->assertSame( array( '_wcml_converted_subtotal', '_wcml_converted_total', '_wcml_total_qty' ), $filtered_itemmeta );
	}
}
