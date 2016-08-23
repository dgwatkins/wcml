<?php

class Test_WCML_Emails extends WCML_UnitTestCase {
	private $order;
	private $order_id;
	private $payment_gateways;
	private $orig_product;
	private $shipping;


	function setUp(){
		parent::setUp();
		$this->add_order_for_tests();
	}

	function add_order_for_tests(){
		$this->order = new WC_Order();
		$this->order->set_created_via( 'checkout' );
		$this->order->set_status( 'pending' );
		$this->order->set_customer_id( get_current_user_id() );

		//set payment method for order
		$this->payment_gateways = WC()->payment_gateways->payment_gateways();
		$this->order->set_payment_method( $this->payment_gateways['bacs'] );

		// Add line item
		$this->orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$item  = new WC_Order_Item_Product( array(
			'qty'          => 1,
			'name'         => 'product 1',
			'tax_class'    => '',
			'product_id'   => $this->orig_product->id,
			'variation_id' => 0,
			'subtotal'     => 12,
			'total'        => 12,
			'subtotal_tax' => '',
			'total_tax'    => '',
			'taxes'        => '',
		) );

		$this->order->add_item( $item );
		$this->order_id = $this->order->save();

		//add shipping to order
		$this->shipping = new WC_Shipping_Rate( 'flat_rate', 'FLAT RATE', 10, array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping( array(
			'method_title' => $this->shipping->label,
			'method_id'    => $this->shipping->id,
			'total'        => wc_format_decimal( $this->shipping->cost ),
			'taxes'        => $this->shipping->taxes,
			'meta_data'    => $this->shipping->get_meta_data(),
			'order_id'     => $this->order_id,
		) );
		$item->save();
		$this->order->add_item( $item );
	}

	function test_filter_payment_method_string(){

		$this->sitepress->switch_lang('es');

		$_POST['bacs_enabled'] = 1;
		$this->woocommerce_wpml->gateways->register_gateway_strings( $this->payment_gateways['bacs']->settings );
		$string_id = icl_get_string_id( $this->payment_gateways['bacs']->settings['title'], 'woocommerce', 'bacs_gateway_title' );
		icl_add_string_translation( $string_id, 'es', 'Direct Bank Transfer ES', ICL_TM_COMPLETE );

		$this->wcml_helper->icl_clear_and_init_cache( 'es' );

		$trnsl_title = $this->woocommerce_wpml->emails->filter_payment_method_string( null, $this->order_id, '_payment_method_title', true );

		$this->assertEquals( "Direct Bank Transfer ES", "Direct Bank Transfer ES" );
		$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
	}

	function test_woocommerce_order_get_items(){

		$this->sitepress->switch_lang('es');

		//check if name of product in current language
		$es_product = $this->wcml_helper->add_product( 'es', $this->orig_product->trid, 'producto 1' );
		clean_post_cache( $this->order_id );
		wc_delete_shop_order_transients( $this->order_id );

		$order_products = $this->order->get_items();
		foreach( $order_products as $order_product ){
			$this->assertEquals( 'producto 1', $order_product['name'] );
		}

		//check if shipping title translated
		$this->woocommerce_wpml->shipping->register_shipping_title( $this->shipping->method_id, $this->shipping->label );
		$ship_string_id = icl_get_string_id( $this->shipping->label, 'woocommerce', $this->shipping->method_id.'_shipping_method_title' );
		icl_add_string_translation( $ship_string_id, 'es', 'FLAT RATE ES', ICL_TM_COMPLETE );

		$this->wcml_helper->icl_clear_and_init_cache( 'es' );

		$order_shippings = $this->order->get_items( 'shipping' );
		foreach( $order_shippings as $order_shipping ){
			if( $order_shipping instanceof WC_Order_Item_Shipping  ){
				//WC >= 2.7
				$this->assertEquals( 'FLAT RATE ES', $order_shipping->get_method_title() );
			}else{
				$this->assertEquals( 'FLAT RATE ES', $order_shipping['name'] );
			}

		}

		$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
	}

	function test_set_locale_for_emails() {
		$locale_dummy = rand_str( 5 );
		$domain_dummy = rand_str( 20 );
		$this->assertEquals( $locale_dummy, $this->woocommerce_wpml->emails->set_locale_for_emails( $locale_dummy, $domain_dummy ) );

		$this->woocommerce_wpml->emails->change_email_language( 'fr' );
		$this->assertEquals( 'fr_FR', $this->woocommerce_wpml->emails->set_locale_for_emails( $locale_dummy, 'woocommerce' ) );
	}



}