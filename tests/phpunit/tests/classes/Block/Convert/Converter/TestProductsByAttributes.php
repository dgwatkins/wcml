<?php

namespace WCML\Block\Convert\Converter;

/**
 * @group block
 * @group block-convert
 */
class TestProductsByAttributes extends \OTGS_TestCase {

	public function tearDown() {
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpShouldNotConvertIfBlockDoesNotHaveTheRequiredAttributes
	 *
	 * @param array $block
	 */
	public function itShouldNotConvertIfBlockDoesNotHaveTheRequiredAttributes( array $block ) {
		$subject = new ProductsByAttributes();

		$this->assertSame( $block, $subject->convert( $block ) );
	}

	public function dpShouldNotConvertIfBlockDoesNotHaveTheRequiredAttributes() {
		return [
			'missing "attributes"' => [
				[
					'attrs' => [],
				],
			],
			'missing "attribute id"' => [
				[
					'attrs' => [
						[ 'attr_slug' => 'pa_color' ],
					],
				],
			],
			'missing "attribute attr_slug"' => [
				[
					'attrs' => [
						[ 'id' => 123 ],
					],
				],
			],
		];
	}

	/**
	 * @test
	 */
	public function itShouldConvert() {
		$originalId  = 123;
		$convertedId = 456;
		$attrSlug    = 'pa_color';

		$getBlock = function( $id ) use( $attrSlug ) {
			return [
				'attrs' => [
					'attributes' =>[
						[ 'id' => $id, 'attr_slug' => $attrSlug ],
					],
				],
			];
		};

		$originalBlock  = $getBlock( $originalId );
		$convertedBlock = $getBlock( $convertedId );

		$this->mockConvertIds( $originalId, $convertedId, $attrSlug );

		$subject = new ProductsByAttributes();

		$this->assertEquals( $convertedBlock, $subject->convert( $originalBlock ) );
	}

	private function mockConvertIds( $id, $convertedId, $slug ) {
		global $sitepress;

		$sitepress = $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'is_display_as_translated_taxonomy' ] )
			->disableOriginalConstructor()->getMock();

		$sitepress->method( 'is_display_as_translated_taxonomy' )
		          ->with( $slug )
		          ->willReturn( false );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $id, $slug ],
			'return' => $convertedId,
		] );
	}
}
