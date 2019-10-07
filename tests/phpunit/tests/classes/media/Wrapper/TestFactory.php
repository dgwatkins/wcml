<?php

namespace WPML\Media\Wrapper;

use WCML\Media\Wrapper\Factory;
use WCML\Media\Wrapper\NonTranslatable;
use WCML_Media;
use woocommerce_wpml;

/**
 * @group media
 * @group media-wrapper
 */
class TestFactory extends \OTGS_TestCase {

	public function setUp() {
		global $sitepress, $wpdb;

		parent::setUp();

		$sitepress = $this->getMockBuilder( '\SitePress' )->getMock();
		$wpdb      = $this->getMockBuilder( '\wpdb' )->getMock();
	}

	public function tearDown() {
		global $sitepress, $wpdb;

		unset( $sitepress, $wpdb );

		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itShouldCreateNonTranslatableWrapper() {
		$this->mockSettings( false );

		$woocommerce_wpml = $this->getMockBuilder( woocommerce_wpml::class )->getMock();

		$this->assertInstanceOf( NonTranslatable::class, Factory::create( $woocommerce_wpml ) );
	}

	/**
	 * @test
	 */
	public function itShouldCreateTranslatableWrapper() {
		$this->mockSettings( true );

		$woocommerce_wpml = $this->getMockBuilder( woocommerce_wpml::class )->getMock();

		$this->assertInstanceOf( WCML_Media::class, Factory::create( $woocommerce_wpml ) );
	}

	/**
	 * @param bool $isAttachmentTranslatable
	 */
	private function mockSettings( $isAttachmentTranslatable ) {
		$settings = $this->getMockBuilder( '\WPML_Element_Sync_Settings' )
			->setMethods( [ 'is_sync' ] )
			->disableOriginalConstructor()->getMock();
		$settings->method( 'is_sync' )->with( 'attachment' )->willReturn( $isAttachmentTranslatable );

		\Mockery::mock( 'overload:\WPML_Element_Sync_Settings_Factory' )
			->shouldReceive( 'create' )
			->with( 'post' )
			->andReturn( $settings );
	}
}
