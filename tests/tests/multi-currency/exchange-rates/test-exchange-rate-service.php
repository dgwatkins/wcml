<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

/**
 * @group multicurrency
 * @group exchange-rate-services
 */
class Test_Service extends \WCML_UnitTestCase {

    private $_mock_http_response = false;

    function setUp(){
        parent::setUp();
    }

    /**
     * @test
     */
    function test_currencylayer(){

        $currencylayer = new CurrencyLayer();

        $this->assertEquals( 'currencylayer',  $currencylayer->getName() );
        $this->assertEquals( 'https://currencylayer.com/',  $currencylayer->getUrl() );

        $random_key   = random_string();
        $random_value = random_string();
        $currencylayer->saveSetting( $random_key, $random_value );

        $this->assertEquals( $random_value,  $currencylayer->getSetting( $random_key ) );

        $settings = $currencylayer->getSettings();
        $this->assertEquals( $random_value,  $settings[$random_key] );

        add_filter( 'pre_http_request', [ $this, 'mock_api_response' ] );


        // 1) WP error
        $this->_mock_http_response = new \WP_Error( 500, 'some_wp_error' );
        try {
            $rates = $currencylayer->getRates( 'EUR', [ 'USD', 'RON' ] );
        } catch ( \Exception $e ) {
            $this->assertEquals( 'some_wp_error', $e->getMessage() );
        }

        $currencylayer->saveSetting( 'api-key', 'INCORRECT_a1e7e888e1ce81ac7c6f4a9b4374a748' );
        $currencylayer = new CurrencyLayer(); // New Instance to take key

        // 2) Error in API response
		$error_body = [
			'success' => false,
			'error'   => [
				'code' => 101,
				'type' => 'invalid_access_key',
				'info' => 'You have not supplied a valid API Access Key. [Technical Support: support@apilayer.com]'
			]
		];

        $this->_mock_http_response = [ 'body' => json_encode( $error_body ) ];
        try {
            $rates = $currencylayer->getRates( 'EUR', [ 'USD', 'RON' ] );
        } catch ( \Exception $e ) {
            $this->assertStringContainsString( $error_body['error']['code'], $e->getMessage() );
            $this->assertStringContainsString( $error_body['error']['type'], $e->getMessage() );
            $this->assertStringContainsString( $error_body['error']['info'], $e->getMessage() );
        }


        // 3) Correct API key
        $currencylayer->saveSetting( 'api-key', 'a1e7e888e1ce81ac7c6f4a9b4374a748' );
        $currencylayer = new CurrencyLayer(); // New Instance to take key

        $source = 'USD';
        $tos    = [ 'EUR', 'RON' ];
        foreach ( $tos as $to ){
            $quotes[ $source . $to ] = round( rand(1, 10000) / 100, 2 );
        }

        $this->_mock_http_response = [
            'body' => json_encode(
                [
                    'success' => 1,
                    'source'  => $source,
                    'quotes'  => $quotes
                ]
            )
        ];
        try {
            $rates = $currencylayer->getRates( $source, $tos );

            foreach ($tos as $to ) {
                $this->assertEquals( $quotes[ $source . $to ], $rates[ $to ] );
            }

        } catch ( \Exception $e ) {
            $this->assertFalse( $e->getMessage() ); // Reveal this! Should not happen.
        }


        remove_filter( 'pre_http_request', [ $this, 'mock_api_response' ] );
        $this->_mock_http_response = false;


    }

    /**
     * @test
     *
     */
    function test_last_error(){

        $random = random_string();

        $currencylayer = new CurrencyLayer();
        $currencylayer->saveLastError( $random );

        // save & get
        $currencylayer = new CurrencyLayer();
        $last_error    = $currencylayer->getLastError();
        $this->assertEquals( $random,  $last_error['text'] );

        //clear
        $currencylayer = new CurrencyLayer();
        $currencylayer->clearLastError();
        $this->assertFalse( $currencylayer->getLastError() );

    }

    /**
     * @test
     */
    public function test_fixerio(){

        add_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );

        $fixer = new Fixerio();


        // 1) WP error
        $this->_mock_http_response = new \WP_Error( 500, 'some_wp_error' );
        try {
            $rates = $fixer->getRates( 'USD', array( 'RON', 'BGN' ) );
        } catch ( \Exception $e ) {
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

        try {
            $rates = $fixer->getRates( $from, $tos );
            $this->assertEquals( $quotes, $rates );
        } catch ( \Exception $e ) {
            $this->assertFalse( $e->getMessage() ); // Reveal this! Should not happen.
        }

        remove_filter( 'pre_http_request', array( $this, 'mock_api_response' ) );
        $this->_mock_http_response = false;


    }

    public function mock_api_response( $reponse ){

        return $this->_mock_http_response;

    }

}