<?php

class Test_WCML_Cart extends WCML_UnitTestCase {
    private $multi_currency;
    private $currencies = array();

	function setUp(){
		parent::setUp();

        add_filter('wcml_load_multi_currency', '__return_true');
        set_current_screen( 'front' );

		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;

		//add global attribute for tests
		$attr = 'size';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'medium', $attr, 'en' );
		$es_term = $this->wcml_helper->add_attribute_term( 'medio', $attr, 'es', $term['trid'] );

        $this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
        $this->multi_currency_helper->enable_multi_currency();

        //
        // THE MULTI CURRENCY CONTEXT
        //
        $this->products = array(
            '0' => array(
                'title' => 'Test Shipping Costs',
                'price' => 50
            )
        );

        $this->flat_rate_cost = 10;

        $this->currencies = array(
            'USD' => array(
                'rate'      => 1.34,
                'options'   => array(
                    'position' => 'left',
                    'thousand_sep' => ',',
                    'decimal_sep' => '.',
                    'num_decimals' => 2,
                    'rounding' => 'disabled',
                    'rounding_increment' => 0,
                    'auto_subtract' => 0
                )
            ),
            'JPY' => array(
                'rate'      => 137,
                'options'   => array(
                    'position' => 'left',
                    'thousand_sep' => ',',
                    'decimal_sep' => '.',
                    'num_decimals' => 0,
                    'rounding' => 'disabled',
                    'rounding_increment' => 0,
                    'auto_subtract' => 0
                )
            ),
            'BTC' => array(
                'rate'      => 0.0020,
                'options'   => array(
                    'position' => 'left',
                    'thousand_sep' => ',',
                    'decimal_sep' => '.',
                    'num_decimals' => 4,
                    'rounding' => 'disabled',
                    'rounding_increment' => 0,
                    'auto_subtract' => 0
                )
            )

        );

        foreach( $this->currencies as $code => $currency ){
            $this->multi_currency_helper->add_currency( $code, $currency['rate'], $currency['options'] );
        }


        // Multi currency objects
        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency =& $this->woocommerce_wpml->multi_currency;

        $this->multi_currency->prices->prices_init();



    }

	function test_get_cart_attribute_translation(){

		//test global attribute
		$trnsl_attr = $this->woocommerce_wpml->cart->get_cart_attribute_translation( 'attribute_pa_size', 'medium', false, 'es', false, false );

		$this->assertEquals( 'medio', $trnsl_attr );

		//test variation global attribute
		$variation = $this->wcml_helper->add_product_variation( 'es', false );
		$variation_id = $variation->id;

		add_post_meta( $variation_id, 'attribute_pa_size', 'medio' );
		$trnsl_attr = $this->woocommerce_wpml->cart->get_cart_attribute_translation( 'attribute_pa_size', 'medio', $variation_id, 'es', false, false );

		$this->assertEquals( 'medio', $trnsl_attr );

		//test local attribute with variation set to any

		$this->wcml_helper->add_local_attribute( $this->orig_product_id, 'Size', 'small | medium' );

		$this->wcml_helper->add_local_attribute( $this->es_product_id, 'Size', 'pequena | medio' );

		$variation = $this->wcml_helper->add_product_variation( 'es', false );
		$variation_id = $variation->id;
		add_post_meta( $variation_id, 'attribute_size', '' );

		$trnsl_attr = $this->woocommerce_wpml->cart->get_cart_attribute_translation( 'attribute_size', 'small', $variation_id, 'es', $this->orig_product_id , $this->es_product_id );

		$this->assertEquals( 'pequena', $trnsl_attr );

	}

	function test_filter_paypal_args(){
		global $sitepress_settings, $wpml_post_translations, $WPML_String_Translation;

		$WPML_String_Translation->init_active_languages();
		$this->sitepress->switch_lang( 'de' );

		$default_lang_code	= 'de';
		$wpml_wp_api        = new WPML_WP_API();
		$hidden_langs 		= array();
		$wpml_url_converter = new WPML_Lang_Parameter_Converter( $default_lang_code, $hidden_langs, $wpml_wp_api );
		$canonicals = new WPML_Canonicals( $this->sitepress );

		$canonicals = new WPML_Canonicals( $this->sitepress );

		$_SERVER['SERVER_NAME'] = $this->sitepress->convert_url( get_home_url() );
		$wpml_url_filters = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $canonicals, $this->sitepress );

		$args['notify_url'] =  WC()->api_request_url( 'WC_Gateway_Paypal' );

		$filtered_args = $this->woocommerce_wpml->cart->filter_paypal_args( $args ) ;

		$this->assertEquals( $this->sitepress->convert_url( get_home_url() ).'&wc-api=WC_Gateway_Paypal', $filtered_args['notify_url'] );
	}

    /**
     * woocommerce_calculate_totals is used to filter the cart data when WooCommerce calculates the totals
     */
    function test_woocommerce_calculate_totals(){

        // We need this to have the calculate_totals() method calculate totals
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
            define( 'WOOCOMMERCE_CHECKOUT', true );
        }

        WC()->cart->empty_cart();
        $this->multi_currency->set_client_currency( 'GBP' );

        // Add the product to the cart
        $product = $this->wcml_helper->add_product(
            $this->sitepress->get_default_language(),
            false,
            $this->products[0]['title'],
            0,
            array(
                '_price' => $this->products[0]['price'],
                '_regular_price' => $this->products[0]['price']
            )
        );

        // DEFAULT CURRENCY
        $items = random_int(1, 10);
        WC()->cart->add_to_cart( $product->id, $items );
        WC()->cart->calculate_totals();
        // Cost without shipping
        $this->assertEquals( $items * $this->products[0]['price'],  WC()->cart->cart_contents_total );

        foreach( $this->currencies as $code => $currency ){

            // Clean up the cart
            WC()->cart->empty_cart();
            $this->multi_currency->set_client_currency( $code );
            $items = random_int(1, 10);
            WC()->cart->add_to_cart( $product->id, $items );
            WC()->cart->calculate_totals();

            //The cost of the cart in the SECONDARY currency (without shipping)
            $this->assertEquals(
                $items * $this->currencies[$code]['rate'] * $this->products[0]['price'],
                WC()->cart->cart_contents_total
            );

        }

        // Delete the product
        wp_delete_post( $product->id, true );

        $this->multi_currency->set_client_currency( 'GBP' );
    }


}