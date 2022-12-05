<?php

class Test_WCML_Cart_Switch_Lang_Functions extends WCML_UnitTestCase {

    private $default_language;
    private $second_language;
    private $wcml_cart_switch_lang_functions;

	function setUp(){
		parent::setUp();

        $this->wcml_cart_switch_lang_functions = new WCML_Cart_Switch_Lang_Functions();
		$this->wcml_cart_switch_lang_functions->add_hooks();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
    }

	function test_wcml_language_force_switch(){
        $_GET[ 'force_switch' ] = '1';
        $this->wcml_cart_switch_lang_functions->wcml_language_force_switch();
        $this->assertEquals( 'lang_switch',  $this->woocommerce->session->get( 'wcml_switched_type' ) );
    }

    function test_wcml_language_switch_dialog(){
		global $post;

        $this->woocommerce_wpml->settings[ 'cart_sync' ][ 'lang_switch' ] = WCML_CART_CLEAR;
        update_option( '_wcml_settings', $this->woocommerce_wpml->settings );
        $this->wcml_cart_switch_lang_functions->language_has_switched( $this->default_language, $this->second_language );

        $orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
	    $post = get_post( $orig_product->id );

        WC()->cart->add_to_cart( $orig_product->id, 1 );
        ob_start();
        $this->wcml_cart_switch_lang_functions->wcml_language_switch_dialog();
        $html = ob_get_contents();
        ob_end_clean();

	    $expected_from_link = esc_url( add_query_arg( 'force_switch', 0, get_permalink( $orig_product->id ) ), null, 'redirect' );

        $this->assertNotEmpty( $html );
        $this->assertContains( 'wcml-cart-dialog-confirm', $html );
        $this->assertContains( $expected_from_link, $html );
    }

	function test_wcml_language_switch_dialog_empty_from_post(){

		$this->woocommerce_wpml->settings[ 'cart_sync' ][ 'lang_switch' ] = WCML_CART_CLEAR;
		update_option( '_wcml_settings', $this->woocommerce_wpml->settings );
		$this->wcml_cart_switch_lang_functions->language_has_switched( $this->default_language, $this->second_language );

		ob_start();
		$this->wcml_cart_switch_lang_functions->wcml_language_switch_dialog();
		$html = ob_get_contents();
		ob_end_clean();

		$this->assertContains( home_url(), $html );
	}

}