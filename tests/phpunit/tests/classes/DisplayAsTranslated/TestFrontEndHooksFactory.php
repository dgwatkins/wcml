<?php

namespace WCML\DisplayAsTranslated;

use WPML\API\MakeMock;

/**
 * @group display-as-translated
 */
class TestFrontEndHooksFactory extends \OTGS_TestCase {

	use MakeMock;

	public function setUp() {
		parent::setUp();
		$this->setUpMakeMock();
		$this->mockMake( ProductCatHooks::class );
	}

	public function tearDown() {
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpShouldCreateAndReturnNull
	 *
	 * @param array $displayAsTranslatedTaxonomies
	 * @param bool  $isSecondaryLang
	 *
	 * @return void
	 */
	public function itShouldCreateAndReturnNull( $displayAsTranslatedTaxonomies, $isSecondaryLang ) {
		$this->mockSitePress( $displayAsTranslatedTaxonomies, $isSecondaryLang );

		$this->assertSame( [], ( new FrontendHooksFactory() )->create() );
	}

	public function dpShouldCreateAndReturnNull() {
		return [
			[
				[ 'foo_bar', 'product_cat' ],
				false,
			],
			[
				[ 'foo_bar' ],
				true,
			],
		];
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldCreateWithProductCatHooks() {
		$this->mockSitePress( [ 'foo_bar', 'product_cat' ] );

		$hooks = ( new FrontendHooksFactory() )->create();

		$this->assertCount( 1, $this->filterHooks( $hooks, ProductCatHooks::class ) );
	}

	/**
	 * @param array $displayAsTranslatedTaxonomies
	 * @param bool  $isSecondaryLang
	 *
	 * @return void
	 */
	private function mockSitePress( $displayAsTranslatedTaxonomies, $isSecondaryLang = true ) {
		$sitepress = $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'is_display_as_translated_taxonomy', 'get_default_language', 'get_current_language' ] )
			->getMock();

		$sitepress->method( 'is_display_as_translated_taxonomy' )
			->willReturnCallback( function( $taxonomy ) use ( $displayAsTranslatedTaxonomies ) {
				return in_array( $taxonomy, $displayAsTranslatedTaxonomies, true );
			} );

		$sitepress->method( 'get_default_language' )->willReturn( 'fr' );
		$sitepress->method( 'get_current_language' )->willReturn( $isSecondaryLang ? 'de' : 'fr' );

		$GLOBALS['sitepress'] = $sitepress;
	}

	/**
	 * @param array  $hooks
	 * @param string $class
	 *
	 * @return array
	 */
	private function filterHooks( $hooks, $class ) {
		return array_filter( $hooks, function( $hook ) use ( $class ) {
			return $hook instanceof $class;
		} );
	}
}
