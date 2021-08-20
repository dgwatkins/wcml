<?php

namespace WCML\Media\DownloadableFiles;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML_Media_Image_Translate;
use function WPML\Container\make;

class Sync {

	/**
	 * @param int          $originalId
	 * @param int          $translationId
	 * @param object|false $job
	 */
	public static function toTranslation( $originalId, $translationId, $job ) {
		// $getJobDetail :: string -> mixed
		$getJobDetail = Obj::prop( Fns::__, (object) $job );

		// $saveInTranslation :: array -> void
		$saveInTranslation = function( $convertedFiles ) use ( $translationId ) {
			update_post_meta( $translationId, '_downloadable_files', $convertedFiles );
		};

		Maybe::of( maybe_unserialize( get_post_meta( $originalId, '_downloadable_files', true ) ) )
			->filter( Logic::isArray() )
			->map( self::applyTranslationFields( $job ) )
			->map( self::convertFileUrls( $getJobDetail( 'source_language_code' ), $getJobDetail( 'language_code' ) ) )
			->map( $saveInTranslation );
	}

	/**
	 * @param object|false $job
	 *
	 * @return \Closure :: array -> array
	 */
	private static function applyTranslationFields( $job ) {
		return function( $downloadableFiles ) use ( $job ) {
			return Fns::reduce( function( $downloadableFiles, $jobElement ) {
				list( $fieldName, $path ) = \WPML_TM_Field_Type_Encoding::decode( $jobElement->field_type );

				if ( '_downloadable_files' === $fieldName ) {
					$fieldValue = base64_decode( $jobElement->field_data_translated ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
					return Obj::set( Obj::lensPath( $path ), $fieldValue, $downloadableFiles );
				}

				return $downloadableFiles;
			}, $downloadableFiles, (array) Obj::prop( 'elements', (array) $job ) );
		};
	}

	/**
	 * @param string $sourceLang
	 * @param string $targetLang
	 *
	 * @return \Closure :: array -> array
	 */
	private static function convertFileUrls( $sourceLang, $targetLang ) {
		$mediaTranslate = self::getMediaTranslate();

		if ( $mediaTranslate ) {
			// $convertMediaUrl :: string -> string
			$convertMediaUrl = function( $url ) use ( $sourceLang, $targetLang, $mediaTranslate ) {
				return $mediaTranslate->get_translated_image_by_url( $url, $sourceLang, $targetLang ) ?: $url;
			};

			return function( $downloadableFiles ) use ( $convertMediaUrl ) {
				// $convertFile :: array -> array
				$convertFile = Obj::over( Obj::lensProp( 'file' ), $convertMediaUrl );

				return Fns::map( $convertFile, $downloadableFiles );
			};
		}

		return Fns::identity();
	}

	/**
	 * It will return an instance of \WPML_Media_Image_Translate
	 * if WPML Media addon is active or NoopMediaImageTranslate otherwise.
	 *
	 * @return WPML_Media_Image_Translate
	 */
	private static function getMediaTranslate() {
		return make( WPML_Media_Image_Translate::class );
	}
}
