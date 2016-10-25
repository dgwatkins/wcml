<?php

class WCML_Test_Exchange_Rate_Service extends WCML_UnitTestCase {

    private $_mock_http_response = false;

    function setUp(){
        parent::setUp();
    }

    /**
     * @test
     */
    function test_currencylayer(){

        $currencylayer = new WCML_Exchange_Rates_Currencylayer();

        $this->assertEquals( 'currencylayer',  $currencylayer->get_name() );
        $this->assertEquals( 'https://currencylayer.com/',  $currencylayer->get_url() );

        $random_key   = random_string();
        $random_value = random_string();
        $currencylayer->save_setting( $random_key, $random_value );

        $this->assertEquals( $random_value,  $currencylayer->get_setting( $random_key ) );

        $settings = $currencylayer->get_settings();
        $this->assertEquals( $random_value,  $settings[$random_key] );

        add_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );


        // 1) WP error
        $this->_mock_http_response = new WP_Error( 500, 'some_wp_error' );
        try{
            $rates = $currencylayer->get_rates( 'EUR', array( 'USD', 'RON' ) );
        } catch ( Exception $e ){
            $this->assertEquals( 'some_wp_error', $e->getMessage() );
        }

        // 2) Missing access key
        $this->_mock_http_response = array(
            'body' => json_encode(
                array(
                    'success' => false,
                    'error'   => array(
                        'code' => 101,
                        'type' => 'missing_access_key',
                        'info' => 'You have not supplied an API Access Key. [Required format: access_key=YOUR_ACCESS_KEY]'
                    )
                )
            )
        );
        try{
            $rates = $currencylayer->get_rates( 'EUR', array( 'USD', 'RON' ) );
        } catch ( Exception $e ){
            $this->assertEquals( 'You have entered an incorrect API Access Key', $e->getMessage() );
        }

        $currencylayer->save_setting( 'api-key', 'INCORRECT_a1e7e888e1ce81ac7c6f4a9b4374a748' );
        $currencylayer = new WCML_Exchange_Rates_Currencylayer(); // New Instance to take key

        // 3) Incorrect API key
        $this->_mock_http_response = array(
            'body' => json_encode(
                array(
                    'success' => false,
                    'error'   => array(
                        'code' => 101,
                        'type' => 'invalid_access_key',
                        'info' => 'You have not supplied a valid API Access Key. [Technical Support: support@apilayer.com]'
                    )
                )
            )
        );
        try{
            $rates = $currencylayer->get_rates( 'EUR', array( 'USD', 'RON' ) );
        } catch ( Exception $e ){
            $this->assertEquals( 0, strpos( $e->getMessage(), 'You have not supplied a valid API Access Key' ) );
        }


        // 4) Correct API key
        $currencylayer->save_setting( 'api-key', 'a1e7e888e1ce81ac7c6f4a9b4374a748' );
        $currencylayer = new WCML_Exchange_Rates_Currencylayer(); // New Instance to take key

        $source = 'USD';
        $tos    = array( 'EUR', 'RON' );
        foreach ($tos as $to ){
            $quotes[ $source . $to ] = round( rand(1, 10000) / 100, 2 );
        }

        $this->_mock_http_response = array(
            'body' => json_encode(
                array(
                    'success' => 1,
                    'source'  => $source,
                    'quotes'  => $quotes
                )
            )
        );
        try{
            $rates = $currencylayer->get_rates( $source, $tos );

            foreach ($tos as $to ){
                $this->assertEquals( $quotes[ $source . $to ], $rates[ $to ] );
            }

        } catch ( Exception $e ){
            $this->assertFalse( $e->getMessage() ); // Reveal this! Should not happen.
        }


        remove_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );
        $this->_mock_http_response = false;


    }

    /**
     * @test
     *
     */
    function test_last_error(){

        $random = random_string();

        $currencylayer = new WCML_Exchange_Rates_Currencylayer();
        $currencylayer->save_last_error( $random );

        // save & get
        $currencylayer = new WCML_Exchange_Rates_Currencylayer();
        $last_error    = $currencylayer->get_last_error();
        $this->assertEquals( $random,  $last_error['text'] );

        //clear
        $currencylayer = new WCML_Exchange_Rates_Currencylayer();
        $currencylayer->clear_last_error();
        $this->assertFalse( $currencylayer->get_last_error() );

    }

    /**
     * @test
     */
    public function test_yahoo(){

        add_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );

        $yahoo = new WCML_Exchange_Rates_YahooFinance();


        // 1) WP error
        $this->_mock_http_response = new WP_Error( 500, 'some_wp_error' );
        try{
            $rates = $yahoo->get_rates( 'USD', array( 'RON', 'BGN' ) );
        } catch ( Exception $e ){
            $this->assertEquals( 'some_wp_error', $e->getMessage() );
        }

        // 2) Valid request
        $source = 'USD';
        $quotes = array(
            'RON' => round( rand(1, 10000) / 100, 2 ),
            'BGN' => round( rand(1, 10000) / 100, 2 )
        );

        $lines = array();
        foreach( $quotes as $to => $quote ){
            $lines[] = '"' . $to . '",' . $quotes[$to];
        }

        $this->_mock_http_response = array(
            'body' => join("\n" , $lines )
        );

        try{
            $rates = $yahoo->get_rates( $source, array_keys( $quotes ) );

            $this->assertEquals( $quotes, $rates );
        } catch ( Exception $e ){
            $this->assertFalse( $e->getMessage() ); // Reveal this! Should not happen.
        }

        remove_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );
        $this->_mock_http_response = false;

    }

    /**
     * @test
     */
    public function test_fixierio(){

        add_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );

        $fixier = new WCML_Exchange_Rates_Fixierio();


        // 1) WP error
        $this->_mock_http_response = new WP_Error( 500, 'some_wp_error' );
        try{
            $rates = $fixier->get_rates( 'USD', array( 'RON', 'BGN' ) );
        } catch ( Exception $e ){
            $this->assertEquals( 'some_wp_error', $e->getMessage() );
        }

        // 2) Valid request
        $from   = 'EUR';
        $tos    = array( 'USD', 'RON' );
        $quotes = array();
        foreach( $tos as $to ){
            $quotes[$to] = round( rand(1, 10000) / 100, 2 );
        }

        $this->_mock_http_response = array(
            'body' => json_encode(
                array(
                    'base' => $from,
                    'rates'=> $quotes
                )
            )
        );

        try{
            $rates = $fixier->get_rates( $from, $tos );
            $this->assertEquals( $quotes, $rates );
        } catch ( Exception $e ){
            $this->assertFalse( $e->getMessage() ); // Reveal this! Should not happen.
        }

        remove_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );
        $this->_mock_http_response = false;


    }

    public function mock_api_response( $reponse ){

        return $this->_mock_http_response;

    }

}