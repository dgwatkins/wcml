<?php

namespace WPML\Media\Wrapper;

use WCML\Media\Wrapper\NonTranslatable;

/**
 * @group media
 * @group media-wrapper
 */
class TestNonTranslatable extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldReturnAnEmptyArrayForImageIds() {
		$subject = new NonTranslatable();

		$this->assertEquals( [], $subject->product_images_ids( 123 ) );
	}

	/**
	 * @test
	 */
	public function itShouldReturnZeroForCreateBaseMediaTranslation() {
		$subject = new NonTranslatable();

		$this->assertSame( 0, $subject->create_base_media_translation( 123, 456, 'fr' ) );
	}

	/**
	 * @test
	 */
	public function itShouldDoNothing() {
		$subject = new NonTranslatable();

		$subject->add_hooks();
		$subject->sync_product_gallery( 123 );
		$subject->sync_product_gallery_duplicate_attachment( 123, 456 );
		$subject->sync_thumbnail_id( 123, 456, 'fr' );
		$subject->sync_variation_thumbnail_id( 123, 456, 'fr' );
	}
}
