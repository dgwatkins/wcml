<?php

use \WCML\Multicurrency\Shipping\AdminHooksFactory;

class Test_WCML_Multi_Currency_Shipping_Admin_Hooks_Factory extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();

		if ( ! defined('WCML_MULTI_CURRENCIES_INDEPENDENT' ) ) {
			define( 'WCML_MULTI_CURRENCIES_INDEPENDENT', 2 );
		}
	}

	private function get_subject() {
		return new AdminHooksFactory();
	}

	/**
	 * @test
	 */
	public function it_creates_admin_hooks() {
		global $woocommerce_wpml;

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->atLeastOnce() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->settings['enable_multi_currency'] = 2;
		$woocommerce_wpml->expects( $this->atLeastOnce() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		$subject = $this->get_subject();
		$this->assertInstanceOf( 'IWPML_Action', $subject->create() );

		unset( $woocommerce_wpml );
	}
}
