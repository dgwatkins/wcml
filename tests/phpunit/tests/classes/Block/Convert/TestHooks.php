<?php

namespace WCML\Block\Convert;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group block
 * @group block-convert
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldLoadOnFrontEndWithDic() {
		$subject = $this->getSubject();
		$this->assertInstanceOf( '\IWPML_Frontend_Action', $subject );
		$this->assertInstanceOf( '\IWPML_DIC_Action', $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooksOnAdmin() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		\WP_Mock::expectFilterAdded( 'render_block_data', [ $subject, 'filterIdsInBlock' ] );
		\WP_Mock::expectActionAdded( 'parse_query', [ $subject, 'addCurrentLangToQueryVars' ] );
		\WP_Mock::expectFilterNotAdded( 'rest_request_before_callbacks', [ $subject, 'useLanguageFromCookie' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldAddHooksOnFrontend() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );

		\WP_Mock::expectFilterAdded( 'render_block_data', [ $subject, 'filterIdsInBlock' ] );
		\WP_Mock::expectActionAdded( 'parse_query', [ $subject, 'addCurrentLangToQueryVars' ] );
		\WP_Mock::expectFilterAdded( 'rest_request_before_callbacks', [ $subject, 'useLanguageFromCookie' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldFilterIdsInBlock() {
		$blockName = 'woocommerce/my-block';

		$getBlock = function( $id ) use ( $blockName ) {
			return [
				'blockName' => $blockName,
				'attrs'     => [
					'id'  => $id,
					'foo' => 'bar',
				],
			];
		};

		$originalBlock = $getBlock( 123 );
		$expectedBlock = $getBlock( 456 );

		$converter = $this->getMockBuilder( \WPML\PB\Gutenberg\ConvertIdsInBlock\Base::class )
			->setMethods( [ 'convert' ] )
			->disableOriginalConstructor()->getMock();
		$converter->method( 'convert' )->with( $originalBlock)->willReturn( $expectedBlock );

		FunctionMocker::replace( ConverterProvider::class . '::get', function( $name ) use ( $blockName, $converter ) {
			if ( $name === $blockName ) {
				return $converter;
			}
		} );

		$subject = $this->getSubject();

		$this->assertEquals( $expectedBlock, $subject->filterIdsInBlock( $originalBlock ) );
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterQueryIfNotBlockWpQuery() {
		$queryVars = [ 'foo' => 'bar' ];

		$query = \Mockery::mock( '\WP_Query' );
		$query->query_vars = $queryVars;

		$sitepress = $this->getSitepress();
		$sitepress->expects( $this->never() )->method( 'get_current_language' );

		$subject = $this->getSubject( $sitepress );

		$subject->addCurrentLangToQueryVars( $query );

		$this->assertEquals( $queryVars, $query->query_vars );
	}

	/**
	 * @test
	 */
	public function itShouldAddCurrentLangToQueryVarsHash() {
		$lang              = 'fr';
		$queryVars         = [ 'foo' => 'bar' ];
		$expectedQueryVars = $queryVars + [ 'wpml_language' => $lang ];

		$query = \Mockery::mock( '\Automattic\WooCommerce\Blocks\Utils\BlocksWpQuery' );
		$query->query_vars = $queryVars;

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $lang );

		$subject = $this->getSubject( $sitepress );

		$subject->addCurrentLangToQueryVars( $query );
		$this->assertEquals( $expectedQueryVars, $query->query_vars );
	}

	/**
	 * @test
	 */
	public function itShouldNotUseLanguageFromCookieIfNotAWcRestRequest() {
		$response = 'some-response';
		$request  = $this->getRestRequest( '/invalid/route/' );

		$sitepress = $this->getSitepress();
		$sitepress->expects( $this->never() )->method( 'switch_lang' );

		$subject = $this->getSubject( $sitepress );

		$this->assertSame( $response, $subject->useLanguageFromCookie( $response, [], $request ) );
	}

	/**
	 * @test
	 */
	public function itShouldNotUseLanguageFromCookieIfEmpty() {
		$response = 'some-response';
		$request  = $this->getRestRequest( '/wc/blocks/' );

		$sitepress = $this->getSitepress();
		$sitepress->expects( $this->never() )->method( 'switch_lang' );

		$cookie = $this->getCookie();
		$cookie->method( 'get_cookie' )->willReturn( '' );

		$subject = $this->getSubject( $sitepress, $cookie );

		$this->assertSame( $response, $subject->useLanguageFromCookie( $response, [], $request ) );
	}

	/**
	 * @test
	 * @dataProvider dpShouldUseLanguageFromCookie
	 *
	 * @param string $route
	 */
	public function itShouldUseLanguageFromCookie( $route ) {
		$response   = 'some-response';
		$request    = $this->getRestRequest( $route );
		$cookieLang = 'fr';

		$sitepress = $this->getSitepress();
		$sitepress->expects( $this->once() )
		    ->method( 'switch_lang' )
			->with( $cookieLang );

		$cookie = $this->getCookie();
		$cookie->method( 'get_cookie' )->willReturn( $cookieLang );

		$subject = $this->getSubject( $sitepress, $cookie );

		$this->assertSame( $response, $subject->useLanguageFromCookie( $response, [], $request ) );
	}

	public function dpShouldUseLanguageFromCookie() {
		return [
			[ '/wc/blocks/' ],
			[ '/wc/blocks/something/' ],
			[ '/wc/store/' ],
			[ '/wc/store/something/' ],
		];
	}

	private function getSubject( $sitepress = null, $cookie = null ) {
		$sitepress = $sitepress ?: $this->getSitepress();
		$cookie    = $cookie ?: $this->getCookie();

		return new Hooks( $sitepress, $cookie );
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'get_object_id', 'get_current_language', 'switch_lang' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getCookie() {
		return $this->getMockBuilder( '\WPML_Cookie' )
            ->setMethods( [ 'get_cookie' ] )
            ->disableOriginalConstructor()->getMock();
	}

	private function getRestRequest( $route ) {
		$request = $this->getMockBuilder( '\WP_REST_Request' )
			->setMethods( [ 'get_route' ] )
			->disableOriginalConstructor()->getMock();
		$request->method( 'get_route' )->willReturn( $route );

		return $request;
	}
}
