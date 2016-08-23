<?php

class Test_WCML_Orders extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();

		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;
	}

	function test_get_order_items(){

		$order_data = array(
			'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
			'customer_id'   => get_current_user_id(),
			'customer_note' => '',
			'cart_hash'     => md5( json_encode( 'test order' ) ),
			'created_via'   => 'admin'
		);

		$order = wc_create_order( $order_data );

		$item_id = wc_add_order_item( $order->get_id(), array(
			'order_item_name' 		=> 'product 1',
			'order_item_type' 		=> 'line_item'
		) );

		wc_add_order_item_meta( $item_id, '_qty', 1 );
		wc_add_order_item_meta( $item_id, '_product_id', $this->orig_product_id );
		wc_add_order_item_meta( $item_id, '_line_subtotal', 10 );
		wc_add_order_item_meta( $item_id, 'wpml_language', 'en' );

		$_GET['post'] = $order->get_id();

		$iclsettings = $this->sitepress->get_settings();

		$iclsettings['admin_default_language'] = 'es';

		$this->sitepress->save_settings( $iclsettings );

		$line_items = $this->woocommerce_wpml->orders->woocommerce_order_get_items( $order->get_items() );

		foreach( $line_items as $line_item ){
			$this->assertEquals( $this->es_product_id , $line_item['product_id'] );
		}
	}

}