<?php

namespace WCML\Block\Convert\Converter;

/**
 * @group block
 * @group block-convert
 */
class TestProductsByAttributes extends \OTGS_TestCase {

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

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $attrSlug ],
			'return' => $convertedId,
		] );

		$subject = new ProductsByAttributes();

		$this->assertEquals( $convertedBlock, $subject->convert( $originalBlock ) );
	}
}
