<?php

use \WCML\Multicurrency\Shipping\ShippingHooksFactory;

class Test_WCML_Multi_Currency_Shipping_Admin_Hooks_Factory extends OTGS_TestCase {
	private function get_subject() {
		return new ShippingHooksFactory();
	}

	/**
	 * @test
	 */
	public function itCreatesAdminHooks() {
		global $woocommerce_wpml;
		global $_GET;

		$_GET = [
			'page' => 'wc-settings',
			'tab' => 'shipping'
		];

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		$subject = $this->get_subject();
		$this->assertInstanceOf( 'IWPML_Action', $subject->create()[0] );

		unset( $woocommerce_wpml, $_GET );
	}


	/**
	 * @test
	 */
	public function itShouldReturnNullIfNotShippingPageRequest() {
		global $woocommerce_wpml;

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		\WP_Mock::userFunction( 'is_ajax', [
			'return' => false
		] );

		$subject = $this->get_subject();
		$this->assertInstanceOf( 'WCML\Multicurrency\Shipping\FrontEndHooks', $subject->create()[0] );

		unset( $woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function itShouldReturnNullIfMultiCurrencyIsNotEnabled() {
		global $woocommerce_wpml;
		global $_GET;

		$_GET = [
			'page' => 'wc-settings',
			'tab' => 'shipping'
		];

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => false
		] );

		$subject = $this->get_subject();
		$this->assertEmpty( $subject->create() );

		unset( $woocommerce_wpml, $_GET );
	}

	/**
	 * @test
	 */
	public function itShouldReturnNullIfNoSecondaryCurrency() {
		global $woocommerce_wpml;
		global $_GET;

		$_GET = [
			'page' => 'wc-settings',
			'tab' => 'shipping'
		];

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		$subject = $this->get_subject();
		$this->assertEmpty( $subject->create() );

		unset( $woocommerce_wpml, $_GET );
	}

	/**
	 * @dataProvider ajaxActions
	 *
	 * @test
	 */
	public function itCreatesAdminHooksForAjaxRequest( $action ) {
		global $woocommerce_wpml;
		global $_GET;

		$_GET = [
			'action' => $action
		];

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		\WP_Mock::userFunction( 'is_ajax', [
			'return' => true
		] );

		$subject = $this->get_subject();
		$this->assertInstanceOf( 'WCML\Multicurrency\Shipping\AdminHooks', $subject->create()[0] );

		unset( $woocommerce_wpml, $_GET );
	}

	public function ajaxActions() {
		return [
			[ 'woocommerce_shipping_zone_add_method' ],
			[ 'woocommerce_shipping_zone_methods_save_changes' ],
		];
	}

	/**
	 * @test
	 */
	public function itCreatesFrontEndHooksWhenAjaxRequestHasUnlistedAction() {
		global $woocommerce_wpml;
		global $_GET;

		$_GET = [
			'action' => 'foo'
		];

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		\WP_Mock::userFunction( 'is_ajax', [
			'return' => true
		] );

		$subject = $this->get_subject();
		$this->assertInstanceOf( 'WCML\Multicurrency\Shipping\FrontEndHooks', $subject->create()[0] );

		unset( $woocommerce_wpml, $_GET );
	}

	/**
	 * @test
	 */
	public function itCreatesFrontEndHooksWhenItIsShippingCostsUpdateOnCartPage() {
		global $woocommerce_wpml;
		global $_POST;

		$_POST = [
			'calc_shipping' => 'x'
		];

		$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_currency_codes' ] )
		                      ->getMock();
		$multicurrency->expects( $this->any() )->method( 'get_currency_codes' )->willReturn( [ 'PLN', 'EUR' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_multi_currency' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->any() )->method( 'get_multi_currency' )->willReturn( $multicurrency );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		\WP_Mock::userFunction( 'is_ajax', [
			'return' => true
		] );

		$subject = $this->get_subject();
		$this->assertInstanceOf( 'WCML\Multicurrency\Shipping\FrontEndHooks', $subject->create()[0] );

		unset( $woocommerce_wpml, $_POST );
	}

}
