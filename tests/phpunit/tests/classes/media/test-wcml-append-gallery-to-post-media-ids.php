<?php

/**
 * Class Test_WCML_Append_Gallery_To_Post_Media_Ids
 * @group product-media
 */
class Test_WCML_Append_Gallery_To_Post_Media_Ids extends OTGS_TestCase {

	private function get_subject() {
		return new WCML_Append_Gallery_To_Post_Media_Ids();
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		$this->expectFilterAdded( 'wpml_ids_of_media_used_in_post', [ $subject, 'add_product_gallery_images' ], 10, 2 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 * @dataProvider gallery_strings_provider
	 *
	 * @param string $gallery_string
	 * @param array $existing_ids
	 * @param array $expected
	 */
	public function it_should_add_ids( $gallery_string, $existing_ids, $expected ) {
		$subject = $this->get_subject();

		$post_id = mt_rand( 1, 199 );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $post_id, '_product_image_gallery', true ],
			'return' => $gallery_string
		] );

		$this->assertSame( $expected, $subject->add_product_gallery_images( $existing_ids, $post_id ) );
	}

	public function gallery_strings_provider() {
		return [
			'nothing happens'    => [ '', [], [] ],
			'nothing added'      => [ '', [ 3, 4 ], [ 3, 4 ] ],
			'add ids'            => [ '1,2, 3 ,4 , 5', [], [ 1, 2, 3, 4, 5 ] ],
			'doesnt add doubles' => [ '1,2,3', [ 1, 2 ], [ 1, 2, 3 ] ],
		];
	}


}