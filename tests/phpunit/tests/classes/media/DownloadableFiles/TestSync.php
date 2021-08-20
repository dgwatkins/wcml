<?php

namespace WCML\Media\DownloadableFiles;

use tad\FunctionMocker\FunctionMocker;
use WPML\LIB\WP\PostMock;
use WPML\LIB\WP\WPDBMock;

/**
 * @group media
 * @group downloadable-files
 * @group wcml-3504
 */
class TestSync extends \OTGS_TestCase {

	const ORIGINAL_ID = 123;
	const TRANSLATED_ID = 456;
	const LANG_SOURCE = 'en';
	const LANG_TARGET = 'fr';
	const ORIGINAL_URL = 'https://example.com/foo.pdf';
	const TRANSLATED_URL = 'https://example.com/bar.pdf';

	use PostMock;
	use WPDBMock;

	public function setUp() {
		parent::setUp();
		$this->setUpPostMock();
		$this->setUpWPDBMock();;

		\WP_Mock::passthruFunction( 'maybe_unserialize' );
		$this->mockFieldTypeDecode();
	}

	public function tearDown() {

		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpIsMediaAddonActive
	 */
	public function itShouldSyncToTranslation( $isMediaAddonActive ) {
		$fileHash1        = '46627c58-3766-4fe7-b4d9-a2d7d6f409c7';
		$fileHash2        = '598a283a-6680-5977-b7ca-3289df7e8e46';
		$fileHash3        = '24152cc8-6c80-5d7b-9ffa-663a5b67bdba';
		$nameTranslation1 = 'FR One File';
		$urlTranslation3  = 'https://cdn.example.com/translated-in-job-editor.pdf';

		$originalFiles = [
			$fileHash1 => [
				'id'   => $fileHash1,
				'name' => 'One File',
				'file' => self::ORIGINAL_URL,
			],
			$fileHash2 => [
				'id'   => $fileHash2,
				'name' => 'Another File not translated',
				'file' => 'https://example.com/non-translated.pdf',
			],
			$fileHash3 => [
				'id'   => $fileHash3,
				'name' => 'Another File',
				'file' => 'https://example.com/some-original.pdf',
			],
		];

		$expectedFiles = [
			$fileHash1 => [
				'id'   => $fileHash1,
				'name' => $nameTranslation1, // Translated in the job
				'file' => $isMediaAddonActive ? self::TRANSLATED_URL : self::ORIGINAL_URL, // Media translation
			],
			$fileHash2 => [
				'id'   => $fileHash2,
				'name' => 'Another File not translated',
				'file' => 'https://example.com/non-translated.pdf',
			],
			$fileHash3 => [
				'id'   => $fileHash3,
				'name' => 'Another File',
				'file' => $urlTranslation3, // Translated in the job
			],
		];

		$job = $this->getJob( [
			$this->getJobElement( 'body', [], self::ORIGINAL_URL ), // ignored
			$this->getJobElement( '_downloadable_files', [ $fileHash1, 'id' ], $fileHash1 ),
			$this->getJobElement( '_downloadable_files', [ $fileHash1, 'name' ], $nameTranslation1 ),
			$this->getJobElement( '_downloadable_files', [ $fileHash1, 'file' ], self::ORIGINAL_URL ),
			$this->getJobElement( '_downloadable_files', [ $fileHash3, 'id' ], $fileHash3 ),
			$this->getJobElement( '_downloadable_files', [ $fileHash3, 'name' ], 'Another File' ),
			$this->getJobElement( '_downloadable_files', [ $fileHash3, 'file' ], $urlTranslation3 ),
		] );

		$this->mockMediaTranslate( $isMediaAddonActive );

		update_post_meta( self::ORIGINAL_ID, '_downloadable_files', $originalFiles );

		Sync::toTranslation( self::ORIGINAL_ID, self::TRANSLATED_ID, $job );

		$this->assertEquals(
			$expectedFiles,
			get_post_meta( self::TRANSLATED_ID, '_downloadable_files', true )
		);
	}

	public function dpIsMediaAddonActive() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpIsMediaAddonActive
	 *
	 * @param bool $isMediaAddonActive
	 */
	public function itShouldNotSyncToTranslationIfJobIsFalse( $isMediaAddonActive ) {
		$fileHash1 = '46627c58-3766-4fe7-b4d9-a2d7d6f409c7';

		$originalFiles = [
			$fileHash1 => [
				'id'   => $fileHash1,
				'name' => 'One File',
				'file' => self::ORIGINAL_URL,
			],
		];

		$job = false;

		$this->mockMediaTranslate( $isMediaAddonActive );

		update_post_meta( self::ORIGINAL_ID, '_downloadable_files', $originalFiles );

		Sync::toTranslation( self::ORIGINAL_ID, self::TRANSLATED_ID, $job );

		$this->assertEquals(
			$originalFiles,
			get_post_meta( self::TRANSLATED_ID, '_downloadable_files', true )
		);
	}

	/**
	 * @test
	 * @dataProvider dpIsMediaAddonActive
	 *
	 * @param bool $isMediaAddonActive
	 */
	public function itShouldNotSyncToTranslationIfDownloadableFilesIsNotArray( $isMediaAddonActive ) {
		$originalFiles = 'corrupted data';
		$job           = false;

		$this->mockMediaTranslate( $isMediaAddonActive );

		update_post_meta( self::ORIGINAL_ID, '_downloadable_files', $originalFiles );

		Sync::toTranslation( self::ORIGINAL_ID, self::TRANSLATED_ID, $job );

		$this->assertEquals(
			false,
			get_post_meta( self::TRANSLATED_ID, '_downloadable_files', true )
		);
	}

	/**
	 * @param string $fieldName
	 * @param array  $path
	 * @param string $translation
	 *
	 * @return object
	 */
	private function getJobElement( $fieldName, $path, $translation ) {
		return (object) [
			// The encoding is more complex in the code base.
			'field_type'            => json_encode( [ $fieldName, $path ] ),
			'field_data_translated' => base64_encode( $translation ),
		];
	}

	/**
	 * @param array $elements
	 *
	 * @return object
	 */
	private function getJob( array $elements ) {
		return (object) [
			'language_code'        => self::LANG_TARGET,
			'source_language_code' => self::LANG_SOURCE,
			'elements'             => $elements,
		];
	}

	/**
	 * @param bool $isMediaAddonActive
	 */
	private function mockMediaTranslate( $isMediaAddonActive ) {
		$mock = null;

		if ( $isMediaAddonActive ) {
			$mock = $this->getMockBuilder( \WPML_Media_Image_Translate::class )
				->setMethods( [ 'get_translated_image_by_url' ] )
				->getMock();
			$mock->method( 'get_translated_image_by_url' )
				->willReturnMap( [
					[ self::ORIGINAL_URL, self::LANG_SOURCE, self::LANG_TARGET, self::TRANSLATED_URL ],
				] );
		}

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'args'   => [ 'WPML_Media_Image_Translate' ],
			'return' => $mock,
		] );
	}

	private function mockFieldTypeDecode() {
		FunctionMocker::replace( 'WPML_TM_Field_Type_Encoding::decode', function( $fieldType ) {
			// The encoding is more complex in the code base.
			return json_decode( $fieldType, true );
		} );
	}
}
