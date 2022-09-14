<?php

namespace WCML\User\Store;

/**
 * @group utilities
 * @group wcml-4104
 */
class TestStore extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void
	 */
	public function TestGetFromCookie() {
		$key     = 'test';
		$realKey = 'wcml_' . $key;
		$value   = 'value';
		$subject = $this->getSubject();

		$cookieHandler = $this->getMockBuilder( 'WPML_Cookie' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_cookie' ] )
			->getMock();

		$cookieHandler->method( 'get_cookie' )
			->with( $realKey )
			->willReturn( $value );

		\WP_Mock::onFilter( 'wcml_user_store_strategy' )
			->with( 'wc-session', $realKey )
			->reply( 'cookie' );

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'args'   => Cookie::class,
			'return' => new Cookie( $cookieHandler ),
		] );
	
		$this->assertEquals( $subject->get( $key ), $value );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function TestGetFromSession() {
		global $woocommerce;

		$key     = 'test';
		$realKey = 'wcml_' . $key;
		$value   = 'value';

		$woocommerce = $this->getMockBuilder( 'WooCommerce' )
			->disableOriginalConstructor()
			->getMock();

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
			->disableOriginalConstructor()
			->setMethods( [ 'get' ] )
			->getMock();

		$woocommerce->session->method( 'get' )
			->with( $realKey )
			->willReturn( $value );

		$subject = $this->getSubject();

		$this->assertEquals( $subject->get( $key ), $value );
	}

	public function tearDown() {
		global $woocommerce;

		unset( $woocommerce );
	}

	/**
	 * @return Store
	 */
	private function getSubject() {
		return new Store();
	}

}
