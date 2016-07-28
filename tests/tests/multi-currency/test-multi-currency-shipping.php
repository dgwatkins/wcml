<?php

class Test_WCML_Multi_Currency_Shipping extends WCML_UnitTestCase {

    private $multi_currency;
    private $multi_currency_helper;

    private $currencies = array();
    private $flat_rate_cost = 0;
    private $products = array();

    private $expected_costs = array();

    public function setUp() {

        add_filter('wcml_load_multi_currency', '__return_true');
        set_current_screen( 'front' );

        parent::setUp();

        $this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
        $this->multi_currency_helper->enable_multi_currency();

        //
        // THE CONTEXT
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

        $this->expected_costs['GBP'] = array( 'contents' => 50 , 'total' => '60' );
        foreach( $this->currencies as $code => $currency ){
            $this->multi_currency_helper->add_currency( $code, $currency['rate'], $currency['options'] );

            $this->expected_costs[ $code ] = array(
                'contents' => $this->currencies[$code]['rate'] * $this->products[0]['price'],
                'total'    => $this->currencies[$code]['rate'] *
                                ($this->products[0]['price'] + $this->flat_rate_cost )
            );
        }


        // Multi currency objects
        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency =& $this->woocommerce_wpml->multi_currency;

        $this->multi_currency->prices->prices_init();

        // Create Zones
        WCML_Helper_Shipping::create_mock_zones();
        // Create a flat rate method (cost will be 10)
        WCML_Helper_Shipping::create_simple_flat_rate( array( 'cost' => $this->flat_rate_cost ) );


    }

    public function test_convert_shipping_cost(){

        // We need this to have the calculate_totals() method calculate totals
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
            define( 'WOOCOMMERCE_CHECKOUT', true );
        }

        WC()->cart->empty_cart();
        $this->multi_currency->set_client_currency( 'GBP' );

        // Add the product to the cart
        $product = $this->wcml_helper->add_product( $this->sitepress->get_default_language() , false, $this->products[0]['title'], 0,
            array( '_price' => $this->products[0]['price'], '_regular_price' => $this->products[0]['price'] ) );

        // DEFAULT CURRENCY
        WC()->cart->add_to_cart( $product->id, 1 );
        WC()->cart->calculate_totals();
        // Cost without shipping
        $this->assertEquals( $this->expected_costs['GBP']['contents'],  WC()->cart->cart_contents_total );
        //The cost of the cart in the DEFAULT currency: 50 product + 10 shipping
        $this->assertEquals( $this->expected_costs['GBP']['total'],  WC()->cart->total );



        add_filter('option_woocommerce_flat_rate_settings', array($this->multi_currency->shipping, 'convert_shipping_cost'));
        foreach( $this->currencies as $code => $currency ){

            // Clean up the cart
            WC()->cart->empty_cart();
            $this->multi_currency->set_client_currency( $code );
            WC()->cart->add_to_cart( $product->id, 1 );

            WC()->cart->calculate_totals();

            //The cost of the cart in the SECONDARY currency (without shipping)
            $this->assertEquals( $this->expected_costs[$code]['contents'], WC()->cart->cart_contents_total );
            //The cost of the cart in the SECONDARY currency (with shipping)
            $this->assertEquals( $this->expected_costs[$code]['total'], WC()->cart->total );

        }

        // Delete the product
        wp_delete_post( $product->id, true );

    }

}