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
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'render_block_data', [ $subject, 'filterAttributeIds' ] );
		\WP_Mock::expectActionAdded( 'parse_query', [ $subject, 'addCurrentLangToQueryVars' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldFilterAttributeIdsWithSingleId() {
		$blockName     = 'woocommerce/my-block';
		$attributeName = 'productId';
		$type          = 'product';
		$originalId    = 123;
		$translatedId  = 456;

		$getBlock = function( $id ) use ( $blockName, $attributeName ) {
			return [
				'blockName' => $blockName,
				'attrs'     => [
					$attributeName => $id,
					'foo'          => 'bar',
				],
			];
		};

		$originalBlock = $getBlock( $originalId );
		$expectedBlock = $getBlock( $translatedId );

		$attributesToConvert = [
			[ 'name' => $attributeName, 'type' => $type ],
		];

		FunctionMocker::replace( Config::class . '::get', function( $name ) use ( $blockName, $attributesToConvert ) {
			return $name === $blockName ? $attributesToConvert : [];
		} );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_object_id' )
		          ->with( $originalId, $type )
		          ->willReturn( $translatedId );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals( $expectedBlock, $subject->filterAttributeIds( $originalBlock ) );
	}

	/**
	 * @test
	 */
	public function itShouldFilterAttributeIdsWithArrayOfIds() {
		$blockName     = 'woocommerce/my-block';
		$attributeName = 'productIds';
		$type          = 'product';
		$originalIds   = [ 123, 456 ];
		$translatedIds = [ 2001, 2002 ];

		$getBlock = function( $ids ) use ( $blockName, $attributeName ) {
			return [
				'blockName' => $blockName,
				'attrs'     => [
					$attributeName => $ids,
					'foo'          => 'bar',
				],
			];
		};

		$originalBlock = $getBlock( $originalIds );
		$expectedBlock = $getBlock( $translatedIds );

		$attributesToConvert = [
			[ 'name' => $attributeName, 'type' => $type ],
		];

		FunctionMocker::replace( Config::class . '::get', function( $name ) use ( $blockName, $attributesToConvert ) {
			return $name === $blockName ? $attributesToConvert : [];
		} );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_object_id' )
		          ->withConsecutive( [ $originalIds[0], $type ], [ $originalIds[1], $type ] )
		          ->willReturnOnConsecutiveCalls( ...$translatedIds );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals( $expectedBlock, $subject->filterAttributeIds( $originalBlock ) );
	}

	/**
	 * @test
	 */
	public function itShouldNotFilterAttributeIfMissing() {
		$blockName     = 'woocommerce/my-block';
		$attributeName = 'productIds';
		$type          = 'product';

		$originalBlock = [
			'blockName' => $blockName,
			'attrs'     => [
				'foo' => 'bar',
			],
		];

		$attributesToConvert = [
			[ 'name' => $attributeName, 'type' => $type ],
		];

		FunctionMocker::replace( Config::class . '::get', function( $name ) use ( $blockName, $attributesToConvert ) {
			return $name === $blockName ? $attributesToConvert : [];
		} );

		$subject = $this->getSubject();

		$this->assertEquals( $originalBlock, $subject->filterAttributeIds( $originalBlock ) );
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

	private function getSubject( $sitepress = null ) {
		$sitepress = $sitepress ?: $this->getSitepress();

		return new Hooks( $sitepress );
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'get_object_id', 'get_current_language' ] )
			->disableOriginalConstructor()->getMock();
	}
}
