<?php

namespace WPML\Media\Wrapper;

use SitePress;
use WCML\Media\Wrapper\Translatable;
use woocommerce_wpml;
use WP_Mock;
use wpdb;
use WPML_WP_API;

class TestTranslatable extends \OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	function setUp() {
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();
	}

	/**
	 * @return woocommerce_wpml
	 */
	public function get_woocommerce_wpml() {

		return $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress( $wp_api = null ) {
		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_wp_api' ] )
		                  ->getMock();

		if ( null === $wp_api ) {
			$wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'constant' ] )
		            ->getMock();
	}

	/**
	 * @return Translatable
	 */
	private function get_subject( $woocommerce_wpml = null, $sitepress = null ) {

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new Translatable( $woocommerce_wpml, $sitepress, $this->wpdb );
	}

	/**
	 * @test
	 */
	function it_should_sync_variation_thumbnail_id() {

		$variation_id            = mt_rand( 1, 10 );
		$translated_variation_id = mt_rand( 11, 20 );
		$lang                    = rand_str();
		$thumbnail_id            = mt_rand( 21, 30 );
		$translated_thumbnail    = mt_rand( 31, 40 );

		\WP_Mock::userFunction( 'get_post_meta',
		                        [
			                        'args'   => [ $variation_id, '_thumbnail_id', true ],
			                        'return' => $thumbnail_id,
		                        ] );

		WP_Mock::onFilter( 'translate_object_id' )
		       ->with( $thumbnail_id, 'attachment', false, $lang )
		       ->reply( $translated_thumbnail );

		WP_Mock::userFunction( 'update_post_meta',
		                       [
			                       'args'   => [ $translated_variation_id, '_thumbnail_id', $translated_thumbnail ],
			                       'return' => true,
		                       ] );

		WP_Mock::userFunction( 'update_post_meta',
		                       [
			                       'args'   => [ $variation_id, '_wpml_media_duplicate', 1 ],
			                       'return' => true,
		                       ] );

		WP_Mock::userFunction( 'update_post_meta',
		                       [
			                       'args'   => [ $variation_id, '_wpml_media_featured', 1 ],
			                       'return' => true,
		                       ] );

		$subject = $this->get_subject();
		$subject->sync_variation_thumbnail_id( $variation_id, $translated_variation_id, $lang );
	}

	/**
	 * @test
	 */
	function it_should_duplicate_and_sync_variation_thumbnail_id() {

		$variation_id            = mt_rand( 1, 10 );
		$translated_variation_id = mt_rand( 11, 20 );
		$lang                    = rand_str();
		$thumbnail_id            = mt_rand( 21, 30 );
		$translated_thumbnail    = mt_rand( 31, 40 );

		$media_duplication_mock = $this->getMockBuilder( 'WPML_Media_Attachments_Duplication' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( [ 'create_duplicate_attachment' ] )
		                               ->getMock();
		$media_duplication_mock->method( 'create_duplicate_attachment' )->willReturn( $translated_thumbnail );

		$media_duplication_factory_mock = \Mockery::mock( 'overload:WPML_Media_Attachments_Duplication_Factory' );
		$media_duplication_factory_mock->shouldReceive( 'create' )->andReturn( $media_duplication_mock );

		\WP_Mock::userFunction( 'get_post_meta',
		                        [
			                        'args'   => [ $variation_id, '_thumbnail_id', true ],
			                        'return' => $thumbnail_id,
		                        ] );

		WP_Mock::onFilter( 'translate_object_id' )->with( $thumbnail_id, 'attachment', false, $lang )->reply( null );

		WP_Mock::userFunction( 'wp_get_post_parent_id',
		                       [
			                       'args'   => [ $thumbnail_id ],
			                       'return' => mt_rand( 41, 50 ),
		                       ] );

		WP_Mock::userFunction( 'update_post_meta',
		                       [
			                       'args'   => [ $translated_variation_id, '_thumbnail_id', $translated_thumbnail ],
			                       'return' => true,
		                       ] );

		WP_Mock::userFunction( 'update_post_meta',
		                       [
			                       'args'   => [ $variation_id, '_wpml_media_duplicate', 1 ],
			                       'return' => true,
		                       ] );

		WP_Mock::userFunction( 'update_post_meta',
		                       [
			                       'args'   => [ $variation_id, '_wpml_media_featured', 1 ],
			                       'return' => true,
		                       ] );

		$subject = $this->get_subject();
		$subject->sync_variation_thumbnail_id( $variation_id, $translated_variation_id, $lang );
	}

	/**
	 * @test
	 */
	function it_should_check_is_media_duplication_enabled_for_product_only() {

		$product_id  = 10;
		$setting_key = '_wpml_media_duplicate';

		WP_Mock::userFunction( 'get_post_meta',
		                       [
			                       'args'   => [ $product_id, $setting_key, true ],
			                       'return' => 1,
		                       ] );

		$wp_api = $this->get_wpml_wp_api_mock();

		$wp_api->expects( $this->once() )
		       ->method( 'constant' )
		       ->with( 'WPML_Admin_Post_Actions::DUPLICATE_MEDIA_META_KEY' )
		       ->willReturn( $setting_key );

		$subject = $this->get_subject( null, $this->get_sitepress( $wp_api ) );
		$this->assertTrue( $subject->is_media_duplication_enabled( $product_id ) );
	}

	/**
	 * @test
	 */
	function it_should_check_is_media_duplication_enabled_globally_if_disabled_for_product() {

		$product_id               = 10;
		$this->setting_key        = '_wpml_media_duplicate';
		$this->global_setting_key = 'duplicate_media';
		$media_settings           = [
			'new_content_settings' => [
				$this->global_setting_key => 1,
			],
		];

		WP_Mock::userFunction( 'get_post_meta',
		                       [
			                       'args'   => [ $product_id, $this->setting_key, true ],
			                       'return' => '',
		                       ] );

		WP_Mock::userFunction( 'get_option',
		                       [
			                       'args'   => [ '_wpml_media', [] ],
			                       'return' => $media_settings,
		                       ] );

		$wp_api = $this->get_wpml_wp_api_mock();

		$that = $this;
		$wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WPML_Admin_Post_Actions::DUPLICATE_MEDIA_META_KEY' == $const ) {
				return $that->setting_key;
			} elseif ( 'WPML_Admin_Post_Actions::DUPLICATE_MEDIA_GLOBAL_KEY' == $const ) {
				return $that->global_setting_key;
			}
		} );

		$subject = $this->get_subject( null, $this->get_sitepress( $wp_api ) );
		$this->assertTrue( $subject->is_media_duplication_enabled( $product_id ) );
	}

}