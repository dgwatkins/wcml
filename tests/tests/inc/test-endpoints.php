<?php

class Test_WCML_Endpoints extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;

	function setUp(){
		parent::setUp();

		global $WPML_String_Translation;
		$WPML_String_Translation->init_active_languages();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
	}

	function test_register_endpoints_translations(){

		$string_id = icl_register_string( 'WooCommerce Endpoints', 'order-pay', 'order-pay', false, $this->default_language );
		icl_add_string_translation( $string_id, 'es', 'order-pay-'.$this->second_language , ICL_TM_COMPLETE );
		$this->wcml_helper->icl_clear_and_init_cache( $this->second_language );

		$query_vars = $this->woocommerce_wpml->endpoints->register_endpoints_translations( $this->second_language );
		$this->assertEquals( 'order-pay-'.$this->second_language , $query_vars['order-pay'] );
	}

	public function test_get_translated_edit_address_slug(){
		do_action('wpml_register_single_string', 'woocommerce', 'edit-address-slug: shipping', 'shipping' );

		$string_id = icl_get_string_id( 'shipping', 'woocommerce', 'edit-address-slug: shipping' );
		icl_add_string_translation( $string_id, 'es', 'shipping-'.$this->second_language , ICL_TM_COMPLETE );
		$this->wcml_helper->icl_clear_and_init_cache( $this->second_language );
		$trnsl_slug = $this->woocommerce_wpml->endpoints->get_translated_edit_address_slug( 'shipping' , $this->second_language );
		$this->assertEquals( 'shipping-'.$this->second_language, $trnsl_slug );

		//get translation from .mo
		$trnsl_slug = $this->woocommerce_wpml->endpoints->get_translated_edit_address_slug( 'billing' , $this->second_language );
		$this->assertEquals( 'facturacion', sanitize_title( $trnsl_slug ) );
	}

	function test_endpoint_permalink_filter(){
		global $wp, $post;

		$this->woocommerce_wpml->endpoints->register_endpoint_string( 'orders', 'orders' );
		$string_id = icl_get_string_id( 'orders', 'WooCommerce Endpoints', 'orders' );
		icl_add_string_translation( $string_id, 'es', 'orders-'.$this->second_language , ICL_TM_COMPLETE );

		$this->wcml_helper->icl_clear_and_init_cache( $this->second_language );
		WC_Install::create_pages();

		$my_account_page_id = wc_get_page_id( 'myaccount' );
		$this->sitepress->set_element_language_details( $my_account_page_id, 'post_page', false, $this->default_language );
		$trid = wpml_test_get_element_trid( wc_get_page_id( 'myaccount' ), 'post_page' );
		$trnsl_my_account_page_id = wpml_test_insert_post( $this->second_language, 'page', $trid, 'My account es' );

		$this->sitepress->switch_lang( $this->second_language );
		$permalink = get_permalink( $trnsl_my_account_page_id );
		$wp->query_vars[ 'orders' ] = '';
		set_current_screen( 'front' );
		$post = get_post( $my_account_page_id );
		$permalink = $this->woocommerce_wpml->endpoints->endpoint_permalink_filter( $permalink , $trnsl_my_account_page_id );

		$this->assertEquals( get_permalink( $trnsl_my_account_page_id ), $permalink );
	}
}