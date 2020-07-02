<?php

class Test_WCML_Multi_Currency_Shipping extends WCML_UnitTestCase {

    private $multi_currency;
    private $multi_currency_helper;

    private $currencies = array();
    private $products = array();

    private $method_instances = array();

    private $expected_rates = array();

    public function setUp() {

        add_filter('wcml_load_multi_currency_in_ajax', '__return_true');
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
                ),
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

        // Create Zones
        WCML_Helper_Shipping::create_mock_zones();
        // Create a flat rate method (cost will be 10)
        $legacy_flat_rate_cost = 10;
        WCML_Helper_Shipping::create_simple_flat_rate( array( 'cost' => $legacy_flat_rate_cost ) );

        $free_shipping_cost = 0;
        $this->method_instances['free_shipping'] =
            WCML_Helper_Shipping::add_free_shipping( array( 'min_amount' => 90 ) );  // will add a 50 GBP product first

        $flat_rate_cost = 5;
        $this->method_instances['flat_rate'] =
            WCML_Helper_Shipping::add_flat_rate_shipping( array( 'cost' => $flat_rate_cost ) );

        $local_pickup_cost = 3.5;
        $this->method_instances['local_pickup'] =
            WCML_Helper_Shipping::add_local_pickup_shipping( array( 'cost' => $local_pickup_cost ) );


        $this->expected_rates['legacy_flat_rate']['GBP'] = $legacy_flat_rate_cost;
        $this->expected_rates['flat_rate:' . $this->method_instances['flat_rate']]['GBP'] = $flat_rate_cost;

        foreach( $this->currencies as $code => $currency ){
            $this->multi_currency_helper->add_currency( $code, $currency['rate'], $currency['options'] );

            $this->expected_rates['legacy_flat_rate'][ $code ]
                = $this->currencies[$code]['rate'] * $legacy_flat_rate_cost;
            $this->expected_rates['flat_rate:' . $this->method_instances['flat_rate']][$code]
                = $this->currencies[$code]['rate'] * $flat_rate_cost;
            $this->expected_rates['local_pickup:' . $this->method_instances['flat_rate']][$code]
                = $this->currencies[$code]['rate'] * $local_pickup_cost;
        }

	    // Multi currency objects
	    $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
	    $this->multi_currency = $this->woocommerce_wpml->multi_currency;

	    // Set an address so that shipping can be calculated.
	    add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_country' ) );
	    add_filter( 'woocommerce_customer_get_shipping_state', array( $this, 'force_customer_state' ) );
	    add_filter( 'woocommerce_customer_get_shipping_postcode', array( $this, 'force_customer_postcode' ) );
    }

	public function force_customer_country( $country ) {
		return 'ES';
	}

	public function force_customer_state( $state ) {
		return 'AL';
	}

	public function force_customer_postcode( $postcode ) {
		return '12345';
	}

	/**
	 * @group wcml-3276
	 */
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

        $packages = WC()->shipping->get_packages();

        foreach( $packages as $package ){
            $available_methods = $package['rates'];
            foreach( $available_methods as $method ){
                $this->assertEquals( $this->expected_rates[$method->id]['GBP'],  $method->cost );
            }
        }

        foreach( $this->currencies as $code => $currency ){
	        wp_cache_flush(); // important ( products cache include prices )

            // Clean up the cart
            WC()->cart->empty_cart();
            $this->multi_currency->set_client_currency( $code );

            WC()->cart->add_to_cart( $product->id, 1 );
            WC()->cart->calculate_totals();

            $packages = WC()->shipping->get_packages();
            foreach( $packages as $package ){
                $available_methods = $package['rates'];
                foreach( $available_methods as $method ){

                    if( strpos( $method->id, 'free_shipping') === 0 ) continue;

                    $label = wc_cart_totals_shipping_method_label( $method );
                    $cost = $this->getCostFromPriceMarkup( $label );
                    $cost = str_replace(',', '', $cost);
                    $this->assertEquals( $this->expected_rates[$method->id][$code],  $cost );
                }
            }
        }

        // Delete the product
        wp_delete_post( $product->id, true );
    }

	/**
	 * @param string $markup
	 *
	 * @return string
	 */
	private function getCostFromPriceMarkup( $markup ) {
		$dom = new \DOMDocument();
		$dom->loadHTML( $markup );

		$xpath = new \DOMXPath( $dom );

		$priceNode = $xpath->query( '//span/bdi/text()' );

		$this->assertEquals( 1, $priceNode->length, "Cannot find price in $markup" );

		return $priceNode->item( 0 )->nodeValue;
	}

    public function test_free_shipping_eligibiltiy(){

        // We need this to have the calculate_totals() method calculate totals
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
            define( 'WOOCOMMERCE_CHECKOUT', true );
        }

        $instance_id = $this->method_instances['free_shipping'];

        // the filter for the newly added free shipping
        add_filter('option_woocommerce_free_shipping_' . $instance_id . '_settings',
            array($this->multi_currency->shipping, 'convert_shipping_method_cost_settings'));

        // Add the product to the cart
        $product = $this->wcml_helper->add_product(
            $this->sitepress->get_default_language() ,
            false,
            $this->products[0]['title'], 0,
            array(
                '_price' => $this->products[0]['price'],
                '_regular_price' => $this->products[0]['price']
            )
        );

        // DEFAULT CURRENCY
        WC()->cart->add_to_cart( $product->id, 1 );
        WC()->cart->calculate_totals();
        $packages = WC()->shipping->get_packages();
        $this->assertTrue( isset( $packages[0]['rates'] ) && !isset( $packages[0]['rates']['free_shipping:' .  $instance_id ] ) );

        // Add one more product to match min_amount required
        WC()->cart->add_to_cart( $product->id, 1 );
        WC()->cart->calculate_totals();
        $packages = WC()->shipping->get_packages();
        $this->assertTrue( isset( $packages[0]['rates']['free_shipping:' .  $instance_id ] ) );

        // SECONDARY CURRENCIES
        foreach( $this->currencies as $code => $currency ){
	        wp_cache_flush(); // important ( products cache include prices )

            // Clean up the cart
            WC()->cart->empty_cart();
            $this->multi_currency->set_client_currency( $code );

            WC()->cart->add_to_cart( $product->id, 1 );
            WC()->cart->calculate_totals();
            $packages = WC()->shipping->get_packages();
            $this->assertFalse( isset( $packages[0]['rates']['free_shipping:' .  $instance_id ] ) );

            // Add one more product to match min_amount required
            WC()->cart->add_to_cart( $product->id, 1 );
            WC()->cart->calculate_totals();
            $packages = WC()->shipping->get_packages();
            $this->assertTrue( isset( $packages[0]['rates']['free_shipping:' .  $instance_id ] ) );

        }

        WC()->cart->empty_cart();
        $this->multi_currency->set_client_currency( 'GBP' );

    }


}