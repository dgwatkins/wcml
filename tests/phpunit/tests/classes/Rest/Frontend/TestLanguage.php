<?php

namespace WCML\Rest\Frontend;

use WCML_Switch_Lang_Request;

/**
 * @group rest
 * @group rest-frontend
 */
class TestLanguage extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldGetFromCookie() {
		$lang = 'fr';

		$cookie = $this->getCookie();
		$cookie->method( 'get_cookie' )
		       ->with( WCML_Switch_Lang_Request::COOKIE_NAME )
			->willReturn( $lang );

		$subject = $this->getSubject( $cookie );

		$this->assertEquals( $lang, $subject->get() );
	}

	/**
	 * @test
	 */
	public function itShouldGetAndReturnEmptyIfRefererIsNotSet() {
		unset( $_SERVER['HTTP_REFERER'] );

		$cookie = $this->getCookie();
		$cookie->method( 'get_cookie' )
		       ->with( WCML_Switch_Lang_Request::COOKIE_NAME )
		       ->willReturn( '' );

		$urlConverter = $this->getUrlConverter();
		$urlConverter->expects( $this->never() )->method( 'get_language_from_url' );

		$subject = $this->getSubject( $cookie, $urlConverter );

		$this->assertEquals( '', $subject->get() );
	}

	/**
	 * @test
	 */
	public function itShouldGetFromReferer() {
		$referer = 'http://domain.com/fr/something/';
		$lang    = 'fr';

		$_SERVER['HTTP_REFERER'] = $referer;

		$cookie = $this->getCookie();
		$cookie->method( 'get_cookie' )
			->with( WCML_Switch_Lang_Request::COOKIE_NAME )
			->willReturn( '' );

		$urlConverter = $this->getUrlConverter();
		$urlConverter->method( 'get_language_from_url' )
			->with( $referer )
			->willReturn( $lang );

		$subject = $this->getSubject( $cookie, $urlConverter );

		$this->assertEquals( $lang, $subject->get() );

		unset( $_SERVER['HTTP_REFERER'] );
	}

	private function getSubject( $cookie = null, $urlConverter = null ) {
		$cookie       = $cookie ?: $this->getCookie();
		$urlConverter = $urlConverter ?: $this->getUrlConverter();
		return new Language( $cookie, $urlConverter );
	}

	private function getCookie() {
		return $this->getMockBuilder( '\WPML_Cookie' )
			->setMethods( [ 'get_cookie' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getUrlConverter() {
		return $this->getMockBuilder( '\WPML_URL_Converter' )
			->setMethods( [ 'get_language_from_url' ] )
			->disableOriginalConstructor()->getMock();
	}
}
