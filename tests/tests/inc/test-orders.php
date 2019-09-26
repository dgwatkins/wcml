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

		$order = WCML_Helper_Orders::create_order( array( 'product_id' => $this->orig_product_id ) );
		$order_id = WCML_Helper_Orders::get_order_id( $order );

		$_GET['post'] = $order_id;

		$iclsettings = $this->sitepress->get_settings();

		$iclsettings['admin_default_language'] = 'es';

		$this->sitepress->save_settings( $iclsettings );

		$line_items = $this->woocommerce_wpml->orders->woocommerce_order_get_items( $order->get_items(), $order );

		foreach( $line_items as $line_item ){
			if( $line_item instanceof WC_Order_Item_Product ){
				$line_item_data = $line_item->get_data();
				$this->assertEquals( $this->es_product_id , $line_item->get_product_id() );
				$this->assertEquals( $this->es_product_id , $line_item_data['product_id'] );
			}else{
				$this->assertEquals( $this->es_product_id , $line_item['product_id'] );
			}
		}
	}

}