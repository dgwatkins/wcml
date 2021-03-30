<?php

namespace WCML\Rest;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group rest
 * @group wcml-3581
 */
class TestUrlHooks extends \OTGS_TestCase {

	const LANGUAGE_NEGOTIATION_TYPE_DIRECTORY = 1;

	public function setUp() {
		parent::setUp();

		FunctionMocker::replace( 'constant', function( $name ) {
			return $name === 'WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY' ? self::LANGUAGE_NEGOTIATION_TYPE_DIRECTORY : null;
		} );
	}

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();
		\WP_Mock::expectFilterAdded( 'rest_url', [ $subject, 'handleLanguageInDirectories' ], UrlHooks::AFTER_URL_CONVERTER_REST_HOOKS, 2 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterUrlIfNotWcEndpoint() {
		$path = '/wp/v1/posts/';
		$url  = 'https://example.com/wp-json' . $path;

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_setting' )
		          ->willReturn( [ 'language_negotiation_type' => self::LANGUAGE_NEGOTIATION_TYPE_DIRECTORY ] );

		$subject = $this->getSubject( null, $sitepress );

		$this->assertSame(
			$url,
			$subject->handleLanguageInDirectories( $url, $path )
		);
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterUrlIfNotLangsInDirectories() {
		$path = '/wc/v3/products/';
		$url  = 'https://example.com/wp-json' . $path;

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_setting' )
			->willReturn( [ 'language_negotiation_type' => 'not-in-directories' ] );

		$subject = $this->getSubject( null, $sitepress );

		$this->assertSame(
			$url,
			$subject->handleLanguageInDirectories( $url, $path )
		);
	}

	/**
	 * @test
	 */
	public function itShouldAlterUrlForDefaultLanguage() {
		$path        = '/wc/v3/products/';
		$absHome     = 'https://example.com';
		$urlLang     = 'en';
		$url         = $absHome . '/' . $urlLang . '/wp-json' . $path;
		$filteredUrl = $absHome . '/wp-json' . $path;

		$urlConverter = $this->getUrlConverter();
		$urlConverter->method( 'get_language_from_url' )
			->with( $url )
			->willReturn( $urlLang );

		$urlConverter->method( 'get_abs_home' )
			->willReturn( $absHome );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_setting' )
			->willReturn( [ 'language_negotiation_type' => self::LANGUAGE_NEGOTIATION_TYPE_DIRECTORY ] );
		$sitepress->method( 'get_default_language' )->willReturn( $urlLang );

		$subject = $this->getSubject( $urlConverter, $sitepress );

		$this->assertSame(
			$filteredUrl,
			$subject->handleLanguageInDirectories( $url, $path )
		);
	}

	/**
	 * @test
	 */
	public function itShouldAlterUrlForSecondaryLanguage() {
		$path        = '/wc/v3/products/';
		$absHome     = 'https://example.com';
		$urlLang     = 'en';
		$defaultLang = 'fr';
		$url         = $absHome . '/' . $urlLang . '/wp-json' . $path;
		$filteredUrl = $absHome . '/wp-json' . $path . '?lang=' . $urlLang;

		\WP_Mock::userFunction( 'add_query_arg' )
			->with( [ 'lang' => $urlLang ], $absHome . '/wp-json' . $path )
			->andReturn( $filteredUrl );

		$urlConverter = $this->getUrlConverter();
		$urlConverter->method( 'get_language_from_url' )
			->with( $url )
			->willReturn( $urlLang );

		$urlConverter->method( 'get_abs_home' )
			->willReturn( $absHome );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_setting' )
			->willReturn( [ 'language_negotiation_type' => self::LANGUAGE_NEGOTIATION_TYPE_DIRECTORY ] );
		$sitepress->method( 'get_default_language' )->willReturn( $defaultLang );

		$subject = $this->getSubject( $urlConverter, $sitepress );

		$this->assertSame(
			$filteredUrl,
			$subject->handleLanguageInDirectories( $url, $path )
		);
	}

	private function getSubject( $urlConverter = null, $sitepress = null ) {
		$urlConverter = $urlConverter ?: $this->getUrlConverter();
		$sitepress    = $sitepress ?: $this->getSitepress();

		return new UrlHooks( $urlConverter, $sitepress );
	}

	private function getUrlConverter() {
		return $this->getMockBuilder( \WPML_URL_Converter::class )
			->setMethods( [ 'get_language_from_url', 'get_abs_home' ] )
			->getMock();
	}

	private function getSitepress() {
		return $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'get_default_language', 'get_setting' ] )
			->getMock();
	}
}
