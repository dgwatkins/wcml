<?php

class Test_WCML_Store_Pages extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();

		$this->orig_pages = array();
		$this->install_wc_pages();

	}

	function install_wc_pages(){
		WC_Install::create_pages();
		$check_pages = $this->woocommerce_wpml->store->get_wc_pages();
		foreach( $check_pages as $page ){
			$page_id = get_option($page);
			$this->orig_pages[ $page ] = $page_id;
			$this->sitepress->set_element_language_details( $page_id, 'post_page', false, $this->sitepress->get_default_language() );
		}
	}


	function test_get_wc_pages(){

		$wc_pages = $this->woocommerce_wpml->store->get_wc_pages();

		$this->assertTrue( in_array( 'woocommerce_shop_page_id', $wc_pages ) );
		$this->assertTrue( in_array( 'woocommerce_cart_page_id', $wc_pages ) );
		$this->assertTrue( in_array( 'woocommerce_checkout_page_id', $wc_pages ) );
		$this->assertTrue( in_array( 'woocommerce_myaccount_page_id', $wc_pages ) );
	}

	function test_get_missing_store_pages(){

		$missed_pages = $this->woocommerce_wpml->store->get_missing_store_pages();

		$this->assertTrue( !empty( $missed_pages['codes'] ) );
	}

	function test_create_missing_pages(){

		$this->woocommerce_wpml->store->create_missing_store_pages();
		$this->wcml_helper->icl_clear_and_init_cache();
		$missed_pages = $this->woocommerce_wpml->store->get_missing_store_pages();

		$this->assertFalse( $missed_pages );

	}

	function test_get_page_id(){
		$this->woocommerce_wpml->store->add_filter_to_get_shop_translated_page_id();
		$this->woocommerce_wpml->store->create_missing_store_pages();
		$this->wcml_helper->icl_clear_and_init_cache();
		$this->sitepress->switch_lang( 'es' );
		$page_id = wc_get_page_id( 'shop' );

		$language_details = $this->sitepress->get_element_language_details( $page_id, 'post_page' );
		$this->assertEquals( $language_details->language_code, 'es' );

		$this->sitepress->switch_lang( 'fr' );
		$page_id = wc_get_page_id( 'cart' );

		$language_details = $this->sitepress->get_element_language_details( $page_id, 'post_page' );
		$this->assertEquals( $language_details->language_code, 'fr' );

	}

	function test_get_checkout_url(){
		$this->woocommerce_wpml->store->create_missing_store_pages();
		$this->wcml_helper->icl_clear_and_init_cache();
		$this->sitepress->switch_lang( 'fr' );

		$checkout_url = $this->woocommerce_wpml->store->get_checkout_page_url();

		$this->assertContains( '&lang=fr', $checkout_url );

	}

	function test_get_shop_url(){
		$this->woocommerce_wpml->store->create_missing_store_pages();

		// shop page as front
		$shop_page = wc_get_page_id('shop');
		update_option( 'page_on_front',$shop_page );
		$this->woocommerce_wpml->store->init();
		$languages = $this->sitepress->get_active_languages();
		foreach( $languages as $key => $language ){
			$languages[ $key ]['language_code'] =  $language['code'];

		}
		$shop_urls = $this->woocommerce_wpml->store->translate_ls_shop_url( $languages, true );

		foreach( $shop_urls as $key => $shop_url ){
			if( $key !=  $this->sitepress->get_default_language() )
				$this->assertContains( 'lang='.$key, $shop_url['url'] );
		}
		$this->wcml_helper->icl_clear_and_init_cache();

		// normal post as front
		update_option( 'page_on_front', 1 );
		$this->woocommerce_wpml->store->init();

		$shop_urls = $this->woocommerce_wpml->store->translate_ls_shop_url( $languages, true );

		foreach( $shop_urls as $key => $shop_url ){
			$trnslt_shop_id = apply_filters( 'translate_object_id', $shop_page, 'page', true, $key);
			if( $key !=  $this->sitepress->get_default_language() ){
				$this->assertContains( 'lang='.$key, $shop_url['url'] );
				$this->assertContains( 'page_id='.$trnslt_shop_id, $shop_url['url'] );
			}
		}
	}
}