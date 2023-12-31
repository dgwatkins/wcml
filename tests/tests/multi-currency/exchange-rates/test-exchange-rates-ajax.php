<?php

/**
 * Class Test_WCML_Exchange_Rates_Ajax
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class Test_WCML_Exchange_Rates_Ajax extends WP_Ajax_UnitTestCase {

    private $multi_currency;
    private $exchange_rate_services;
    private $woocommerce_wpml;

    function setUp(){
        global $woocommerce_wpml, $sitepress, $wpdb;

	    if ( ! defined( 'DOING_AJAX' ) ) {
		    define( 'DOING_AJAX', true );
	    }

	    $this->woocommerce_wpml = $woocommerce_wpml;
	    WCML_Helper::init( $this->woocommerce_wpml, $sitepress, $wpdb );
	    WCML_Helper::create_icl_string_packages_table();

        parent::setUp();
        set_current_screen( 'dashboard' );

        $this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
        $this->multi_currency_helper->enable_multi_currency();
        $this->multi_currency_helper->setup_3_currencies();

        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency = $this->woocommerce_wpml->multi_currency;

        $this->exchange_rate_services = $this->multi_currency->exchange_rate_services;

    }

    /**
     * @test
     */
    public function test_update_exchange_rates_ajax(){

	    $exchange_rate_service_fixerio = $this->getMockBuilder( 'Fixerio' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'getRates' ) )
            ->getMock();

        // Set random rates for the mocked getRates method
        $currencies = $this->woocommerce_wpml->multi_currency->get_currency_codes();
        $default_currency = wcml_get_woocommerce_currency_option();
        $secondary_currencies = array_diff( $currencies, array( $default_currency ) );
        foreach( $secondary_currencies as $currency ){
            $rates[ $currency ] = round( rand( 1, 1000 ) / 100 , 2);
        }
	    $exchange_rate_service_fixerio->method( 'getRates' )->willReturn( $rates );

	    $this->exchange_rate_services->add_service( 'fixerio', $exchange_rate_service_fixerio );

	    $this->exchange_rate_services->save_setting( 'service', 'fixerio' );

        $_POST['wcml_nonce'] = wp_create_nonce( 'update-exchange-rates' );
        try {
            $this->_handleAjax( 'wcml_update_exchange_rates' );
        } catch ( WPAjaxDieContinueException $e ) {
            $last_response = substr( $this->_last_response, strpos( $this->_last_response, "\n" ) );
            $response = json_decode( $last_response );

            $this->assertEquals( 1, $response->success );

            $this->_last_response = ''; //need this
        }

        $_POST['wcml_nonce'] = 'invalid_nonce';
        try {
            $this->_handleAjax( 'wcml_update_exchange_rates' );
        } catch ( WPAjaxDieContinueException $e ) {

            $last_response = substr( $this->_last_response, strpos( $this->_last_response, "\n" ) );
            $response = json_decode( $last_response );

            $this->assertEquals( 0, $response->success );
            $this->assertEquals( 'Invalid nonce', $response->error );

            $this->_last_response = ''; //need this
        }



    }





}