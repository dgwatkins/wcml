<?php

/**
 * @group store-pages
 */
class Test_WCML_Store_Pages extends WCML_UnitTestCase {

	private $originalSitePressSetup;

	function setUp(){
		parent::setUp();

		$this->orig_pages = array();
		$this->install_wc_pages();

		$this->originalSitePressSetup = $GLOBALS['sitepress']->get_setting( 'setup_complete' );
		$GLOBALS['sitepress']->set_setting( 'setup_complete', true );

		$this->switch_to_langs_as_params();
	}

	public function tearDown() {
		$GLOBALS['sitepress']->set_setting( 'setup_complete', $this->originalSitePressSetup );
		return parent::tearDown();
	}

	function install_wc_pages(){
		WC_Install::create_pages();
		$check_pages = $this->woocommerce_wpml->store->get_wc_pages();
		foreach( $check_pages as $page ){
			$page_id = wc_get_page_id( $page );
			$this->orig_pages[ $page ] = $page_id;
			$this->sitepress->set_element_language_details( $page_id, 'post_page', false, $this->sitepress->get_default_language() );
		}
	}


	function test_get_wc_pages(){

		$wc_pages = $this->woocommerce_wpml->store->get_wc_pages();

		$this->assertTrue( in_array( 'shop', $wc_pages ) );
		$this->assertTrue( in_array( 'cart', $wc_pages ) );
		$this->assertTrue( in_array( 'checkout', $wc_pages ) );
		$this->assertTrue( in_array( 'myaccount', $wc_pages ) );
	}

	function test_get_missing_store_pages(){

		$missed_pages = $this->woocommerce_wpml->store->get_missing_store_pages();

		$this->assertTrue( !empty( $missed_pages['codes'] ) );
	}

	function test_create_missing_pages(){
		$this->create_missing_store_pages();
		$this->wcml_helper->icl_clear_and_init_cache( 'es' );
		$missed_pages = $this->woocommerce_wpml->store->get_missing_store_pages();

		$this->assertFalse( $missed_pages );

	}

	function test_get_page_id(){
		$this->woocommerce_wpml->store->add_filter_to_get_shop_translated_page_id();
		$this->create_missing_store_pages();
		$this->wcml_helper->icl_clear_and_init_cache( 'es' );
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
		$this->create_missing_store_pages();
		$this->wcml_helper->icl_clear_and_init_cache( 'es' );
		$this->sitepress->switch_lang( 'fr' );

		$checkout_url = $this->woocommerce_wpml->store->get_checkout_page_url();

		$this->assertContains( '&lang=fr', $checkout_url );

	}

	function test_get_shop_url(){
		$this->create_missing_store_pages();

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
		$this->wcml_helper->icl_clear_and_init_cache( 'es' );

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

	/**
	 * Store pages are created in the admin context.
	 */
	private function create_missing_store_pages() {
		$isAdmin = is_admin();

		if ( ! $isAdmin ) {
			$this->switch_to_admin();
		}

		$this->woocommerce_wpml->store->create_missing_store_pages();

		if ( ! $isAdmin ) {
			$this->switch_to_front();
		}
	}
}