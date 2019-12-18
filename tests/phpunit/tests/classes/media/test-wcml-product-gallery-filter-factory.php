<?php

/**
 * Class Test_WCML_Product_Gallery_Filter_Factory
 * @group product-media
 */
class Test_WCML_Product_Gallery_Filter_Factory extends OTGS_TestCase {

	/**
	 * @test
	 *
	 * @runInSeparateProcess
	 *
	 */
	public function it_should_create_the_object() {
		$this->get_woocommerce_wpml_mock();

		\Mockery::mock( 'WPML_Translation_Element_Factory' );

		$factory = new WCML_Product_Gallery_Filter_Factory();
		$this->assertInstanceOf( 'WCML_Product_Gallery_Filter', $factory->create() );
	}

	/**
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_create_the_object_that_implements_iwpml_action() {
		$this->get_woocommerce_wpml_mock();

		\Mockery::mock( 'WPML_Translation_Element_Factory' );

		$factory = new WCML_Product_Gallery_Filter_Factory();

		$implements = class_implements( $factory->create() );
		$this->assertContains( 'IWPML_Action', $implements );

	}

	private function get_woocommerce_wpml_mock(){
		global $woocommerce_wpml;

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_setting' ) )->getMock();
		$woocommerce_wpml->expects( $this->once() )->method( 'get_setting' )->with( 'sync_media', true )->willReturn( true );
	}

}

if ( ! class_exists( 'WPML_WP_Cache' ) ) {
	/**
	 * Class WCML_Templates_Factory
	 * Stub for Test_WCML_Products_UI
	 */
	class WPML_WP_Cache {

		public function __construct() { /*silence is golden*/
		}

	}
}