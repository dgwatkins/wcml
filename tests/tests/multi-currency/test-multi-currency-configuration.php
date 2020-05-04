<?php

class Test_WCML_Multi_Currency_Configuration extends WCML_UnitTestCase {

    private $multi_currency_helper;
    private $multi_currency;
    private $currencies;

    public function setUp() {

        parent::setUp();

        $this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
        $this->multi_currency_helper->enable_multi_currency();

        $this->multi_currency_helper->add_currency( 'USD', '1', array() );
        $this->currencies[ 'first' ] = 'USD';
        $this->multi_currency_helper->add_currency( 'BTC', '121', array() );
        $this->currencies[ 'second' ] = 'BTC';

        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency = $this->woocommerce_wpml->multi_currency;

    }

	/**
	 * @group pierre
	 */
    public function test_update_default_currency(){

        $settings['default_currencies'] = $this->woocommerce_wpml->settings['default_currencies'];
        $settings['default_currencies'][ $this->sitepress->get_default_language() ] = $this->currencies[ 'first' ];
        $this->woocommerce_wpml->update_settings( $settings );
        $this->woocommerce->session->set( 'client_currency_language', $this->sitepress->get_default_language() );

        $_POST['data'] = json_encode( [
	        'lang' => $this->sitepress->get_default_language(),
	        'code' => $this->currencies[ 'second' ],
        ] );

        WCML_Multi_Currency_Configuration::update_default_currency();

        $this->assertEquals( $this->woocommerce->session->get( 'client_currency' ), $this->currencies[ 'second' ] );
        $this->assertEquals( $this->woocommerce_wpml->settings[ 'default_currencies' ][ $this->sitepress->get_default_language() ], $this->currencies[ 'second' ] );
    }

}