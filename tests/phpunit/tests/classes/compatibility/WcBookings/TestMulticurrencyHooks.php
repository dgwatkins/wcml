<?php

namespace WCML\Compatibility\WcBookings;

use PHPUnit\Framework\MockObject\MockObject;
use tad\FunctionMocker\FunctionMocker;
use WCML_Multi_Currency;
use WCML_Products;

/**
 * @group compatibility
 * @group wc-bookings
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	public function tearDown() {
		$_COOKIE = [];
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_does_NOT_set_booking_currency() {
		$cookieName      = '_wcml_booking_currency';
		$setCookieCalled = false;

		$multiCurrency = $this->getMultiCurrency();
		$multiCurrency->expects( $this->never() )->method( 'get_currency_code' );

		$_COOKIE[ $cookieName ] = 'something';
		FunctionMocker::replace( 'headers_sent', false );

		FunctionMocker::replace(
			'setcookie',
			function () use ( &$setCookieCalled ) {
				$setCookieCalled = true;
			}
		);
		FunctionMocker::replace( 'time', 0 );

		$subject = $this->getSubject( null, $multiCurrency );
		$subject->set_booking_currency();

		$this->assertFalse( $setCookieCalled );
	}

	/**
	 * @test
	 */
	public function it_sets_booking_currency_when_cookie_are_set() {
		$cookieName  = '_wcml_booking_currency';
		$setCookieOk = false;

		$multiCurrency = $this->getMultiCurrency();
		$multiCurrency->expects( $this->never() )->method( 'get_currency_code' );

		$_COOKIE[ $cookieName ] = 'something';
		FunctionMocker::replace( 'headers_sent', false );

		$currencyOption = 'EUR';

		FunctionMocker::replace(
			'setcookie',
			function ( $name, $value, $expires, $path, $domain ) use ( $cookieName, $currencyOption, &$setCookieOk ) {
				$setCookieOk = $cookieName === $name
				                && $currencyOption === $value
				                && 86400 === $expires
				                && COOKIEPATH === $path
				                && COOKIE_DOMAIN === $domain;
			}
		);
		FunctionMocker::replace( 'time', 0 );

		$subject = $this->getSubject( null, $multiCurrency );
		$subject->set_booking_currency( 'EUR' );

		$this->assertTrue( $setCookieOk );
	}

	/**
	 * @test
	 */
	public function it_sets_booking_currency_when_currencies_are_independent() {
		$cookieName     = '_wcml_booking_currency';
		$currencyOption = 'EUR';
		$setCookieOk    = false;

		$multiCurrency = $this->getMultiCurrency();
		$multiCurrency->expects( $this->once() )->method( 'get_currency_code' )->willReturn( $currencyOption );

		unset( $_COOKIE[ $cookieName ] );
		FunctionMocker::replace( 'headers_sent', false );

		FunctionMocker::replace(
			'setcookie',
			function ( $name, $value, $expires, $path, $domain ) use ( $cookieName, $currencyOption, &$setCookieOk ) {
				$setCookieOk = $cookieName === $name
				                && $currencyOption === $value
				                && 86400 === $expires
				                && COOKIEPATH === $path
				                && COOKIE_DOMAIN === $domain;
			}
		);
		FunctionMocker::replace( 'time', 0 );

		$subject = $this->getSubject( null, $multiCurrency );
		$subject->set_booking_currency();

		$this->assertTrue( $setCookieOk );
	}

	/**
	 * @test
	 */
	public function it_sets_booking_currency_and_uses_default_currency() {
		$cookieName      = '_wcml_booking_currency';
		$defaultCurrency = 'USD';
		$currencyCode    = $defaultCurrency;
		$setCookieOk     = false;

		$multiCurrency = $this->getMultiCurrency();
		$multiCurrency->expects( $this->once() )->method( 'get_currency_code' )->willReturn( $currencyCode );

		unset( $_COOKIE[ $cookieName ] );
		FunctionMocker::replace( 'headers_sent', false );

		FunctionMocker::replace(
			'setcookie',
			function ( $name, $value, $expires, $path, $domain ) use ( $cookieName, $currencyCode, &$setCookieOk ) {
				$setCookieOk = $cookieName === $name
				                && $currencyCode === $value
				                && 86400 === $expires
				                && COOKIEPATH === $path
				                && COOKIE_DOMAIN === $domain;
			}
		);
		FunctionMocker::replace( 'time', 0 );

		$subject = $this->getSubject( null, $multiCurrency );
		$subject->set_booking_currency();

		$this->assertTrue( $setCookieOk );
	}

	/**
	 * @param WCML_Products|MockObject       $products
	 * @param WCML_Multi_Currency|MockObject $multicurrency
	 *
	 * @return MulticurrencyHooks
	 */
	private function getSubject( $products = null, $multicurrency = null ) {
		$woocommerce_wpml = $this->getMockBuilder( \woocommerce_wpml::class )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->products       = $products ?: $this->getProducts();
		$woocommerce_wpml->multi_currency = $multicurrency ?: $this->getMultiCurrency();

		return new MulticurrencyHooks( $woocommerce_wpml );
	}

	private function getProducts() {
		return $this->getMockBuilder( WCML_Products::class )
		            ->disableOriginalConstructor()
		            ->setMethods( [] )
		            ->getMock();
	}

	private function getMultiCurrency() {
		return $this->getMockBuilder( WCML_Multi_Currency::class )
		            ->disableOriginalConstructor()
		            ->setMethods( [] )
		            ->getMock();
	}
}
