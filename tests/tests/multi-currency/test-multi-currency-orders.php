<?php
require WC_PATH. '/tests/framework/helpers/class-wc-helper-order.php';
require WC_PATH. '/tests/framework/helpers/class-wc-helper-product.php';
require WC_PATH. '/tests/framework/helpers/class-wc-helper-shipping.php';

class Test_WCML_Multi_Currency_Orders extends WCML_UnitTestCase {

    private $settings;
    private $multi_currency;

    private $orders;

    function setUp(){

        parent::setUp();

        // settings
        $settings = $this->woocommerce_wpml->settings;
        $settings['enable_multi_currency'] = 2;
        $settings['default_currencies'] = array( 'en' => 'USD'  );
        $settings['currency_options']['USD'] = array(
            'rate' 				=> 1.55,
            'position'			=> 'left',
            'thousand_sep'		=> ',',
            'decimal_sep'		=> '.',
            'num_decimals'		=> 2,
            'rounding'			=> 'down',
            'rounding_increment'=> 0,
            'auto_subtract'		=> 0
        );

        $this->settings =& $settings;

        $this->woocommerce_wpml->update_settings( $settings );

        // Multi currency objects
        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency =& $this->woocommerce_wpml->multi_currency;

        $this->multi_currency->prices->prices_init();

        $this->orders[0] = WC_Helper_Order::create_order();

        $this->orders[1] = WC_Helper_Order::create_order();
        update_post_meta( $this->orders[1]->get_id(), '_order_currency', 'EUR');

        $this->orders[2] = WC_Helper_Order::create_order();
        update_post_meta( $this->orders[2]->get_id(), '_order_currency', 'EUR');




    }

    public function test_get_orders_currencies(){

        $currencies = $this->multi_currency->orders->get_orders_currencies();

        $this->assertEquals( array(  'EUR' => 2, 'USD' => 1 ), $currencies );


    }



}