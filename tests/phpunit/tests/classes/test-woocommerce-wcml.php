<?php

/**
 * Class Test_woocommerce_wcml
 * Doesn't doo much for now before refactoring the woocommerce_wpml class with a proper constructor and dependency injections
 */
class Test_woocommerce_wcml extends OTGS_TestCase {

	private $options = [
		'woocommerce_api_enabled' => 'yes'
	];

	public function setUp() {
		parent::setUp();

		if( !defined( 'WCML_MULTI_CURRENCIES_DISABLED' ) ){
			define( 'WCML_MULTI_CURRENCIES_DISABLED', false );
		}
		if( !defined( 'WCML_CART_SYNC' ) ){
			define( 'WCML_CART_SYNC', true );
		}

		$that = $this;

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) use ( $that ) {
				return isset( $that->options[$option_name] ) ?  $that->options[$option_name] : null ;
			},
		) );

	}

	/**
	 * @test
	 */
	public function creating_instance_in_admin(){
		global $sitepress;

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		// Multi-currency ON
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$woocommerce = $this->getMockBuilder( 'woocommerce' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		$woocommerce->version = '3.0.0';
		\WP_Mock::wpFunction( 'WC', array( 'return' => $woocommerce, ) );


		$sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
								->setMethods( array( 'get_settings' ) )
		                        ->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		$woocommerce_wpml = new woocommerce_wpml();

	}

	/**
	 * @test
	 */
	public function get_multi_currency(){

		global $woocommerce_wpml, $sitepress, $wpdb;

		$wpdb = $this->stubs->wpdb();

		$woocommerce_wpml = new woocommerce_wpml();

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_active_languages', 'get_settings' ) )
		                  ->getMock();
		$sitepress->method( 'get_active_languages' )->willReturn( array() );
		$sitepress->method( 'get_settings' )->willReturn( array() );

		$woocommerce_wpml->cs_templates = $this->getMockBuilder( 'WCML_CS_Templates' )
		                                       ->disableOriginalConstructor()
		                                       ->setMethods( array( 'get_active_templates' ) )
		                                       ->getMock();
		$woocommerce_wpml->cs_templates->method( 'get_active_templates' )->willReturn( array() );

		$multi_currency = $woocommerce_wpml->get_multi_currency();

		$this->assertInstanceOf( 'WCML_Multi_Currency', $multi_currency );



	}

}
