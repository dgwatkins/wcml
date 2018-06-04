<?php

/**
 * Class Test_WCML_Status_Media_UI
 */
class Test_WCML_Status_Media_UI extends OTGS_TestCase {

	private function get_sitepress() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_wp_api' ] )
		            ->getMock();
	}

	private function get_wpml_wp_api() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'constant' ] )
		            ->getMock();
	}

	private function get_subject( $sitepress = null ) {
		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Status_Media_UI( $sitepress );
	}

	/**
	 * @test
	 */
	public function it_gets_model_with_media_inactive() {
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$wpml_wp_api = $this->get_wpml_wp_api();
		$sitepress->expects( $this->once() )->method( 'get_wp_api' )->willReturn( $wpml_wp_api );

		$wpml_wp_api->expects( $this->once() )->method( 'constant' )->with('WPML_MEDIA_VERSION')->willReturn( null );

		$model = $subject->get_model();
		$this->assertSame( 'Media', $model['strings']['heading'] );
		$this->assertFalse( $model['media_translation_active'] );
	}

	/**
	 * @test
	 */
	public function it_gets_model_with_media_active() {
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$wpml_wp_api = $this->get_wpml_wp_api();
		$sitepress->expects( $this->once() )->method( 'get_wp_api' )->willReturn( $wpml_wp_api );

		$wpml_wp_api->expects( $this->once() )->method( 'constant' )->with('WPML_MEDIA_VERSION')->willReturn( '2.3.0' );

		$model = $subject->get_model();
		$this->assertSame( 'Media', $model['strings']['heading'] );
		$this->assertTrue( $model['media_translation_active'] );
	}

	/**
	 * @test
	 */
	public function it_gets_template() {
		$subject = $this->get_subject();
		$this->assertSame( 'media.twig', $subject->get_template() );
	}

}
