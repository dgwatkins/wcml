<?php

class Test_WCML_Emails extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();

		$this->add_order_for_tests();
	}

	function add_order_for_tests(){
		$this->order = wc_create_order( array(
			'status'		=> 'pending',
			'created_via'	=> 'checkout',
			'customer_id' 	=> get_current_user_id()
		) );

		//set payment method for order
		$this->payment_gateways = WC()->payment_gateways->payment_gateways();
		$this->order->set_payment_method( $this->payment_gateways['bacs'] );

		$this->orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );

		// Add line item
		$item_id = wc_add_order_item( $this->order->id, array(
			'order_item_name' 		=> 'product 1',
			'order_item_type' 		=> 'line_item'
		) );

		// Add line item meta
		if ( $item_id ) {
			wc_add_order_item_meta($item_id, '_qty', 1);
			wc_add_order_item_meta($item_id, '_tax_class', '');
			wc_add_order_item_meta($item_id, '_product_id', $this->orig_product->id);
			wc_add_order_item_meta($item_id, '_variation_id', '');
			wc_add_order_item_meta($item_id, '_line_subtotal', 12);
			wc_add_order_item_meta($item_id, '_line_subtotal_tax', '');
			wc_add_order_item_meta($item_id, '_line_total', 12);
			wc_add_order_item_meta($item_id, '_line_tax', '');
		}

		//add shipping to order
		$this->shipping = new WC_Shipping_Rate( 'flat_rate', 'FLAT RATE', 10, array(), 'flat_rate' );
		$this->order->add_shipping( $this->shipping );

	}

	function test_filter_payment_method_string(){

		$_POST['bacs_enabled'] = 1;
		$this->woocommerce_wpml->gateways->register_gateway_strings( $this->payment_gateways['bacs']->settings );
		$string_id = icl_get_string_id( $this->payment_gateways['bacs']->settings['title'], 'woocommerce', 'bacs_gateway_title' );
		icl_add_string_translation( $string_id, 'es', 'Direct Bank Transfer ES', ICL_TM_COMPLETE );

		$this->sitepress->switch_lang('es');

		$trnsl_title = $this->woocommerce_wpml->emails->filter_payment_method_string( null, $this->order->id, '_payment_method_title', true );

		$this->assertEquals( 'Direct Bank Transfer ES', $trnsl_title );
		$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
	}

	function test_woocommerce_order_get_items(){

		$this->sitepress->switch_lang('es');

		//check if name of product in current language
		$es_product = $this->wcml_helper->add_product( 'es', $this->orig_product->trid, 'producto 1' );
		$order_products = $this->order->get_items();
		foreach( $order_products as $order_product ){
			$this->assertEquals( 'producto 1', $order_product['name'] );
		}

		//check if shipping title translated
		$this->woocommerce_wpml->shipping->register_shipping_title( $this->shipping->method_id, $this->shipping->label );
		$ship_string_id = icl_get_string_id( $this->shipping->label, 'woocommerce', $this->shipping->method_id.'_shipping_method_title' );
		icl_add_string_translation( $ship_string_id, 'es', 'FLAT RATE ES', ICL_TM_COMPLETE );

		$this->wcml_helper->icl_clear_and_init_cache();

		$order_shippings = $this->order->get_items( 'shipping' );
		foreach( $order_shippings as $order_shipping ){
			$this->assertEquals( 'FLAT RATE ES', $order_shipping['name'] );
		}
	}

	function test_set_locale_for_emails() {
		$locale_dummy = rand_str( 5 );
		$domain_dummy = rand_str( 20 );
		$this->assertEquals( $locale_dummy, $this->woocommerce_wpml->emails->set_locale_for_emails( $locale_dummy, $domain_dummy ) );

		$this->woocommerce_wpml->emails->change_email_language( 'fr' );
		$this->assertEquals( 'fr_FR', $this->woocommerce_wpml->emails->set_locale_for_emails( $locale_dummy, 'woocommerce' ) );
	}



}