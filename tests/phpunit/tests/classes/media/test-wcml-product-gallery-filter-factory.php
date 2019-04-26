<?php

/**
 * Class Test_WCML_Product_Gallery_Filter_Factory
 * @group product-media
 */
class Test_WCML_Product_Gallery_Filter_Factory extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_create_the_object() {
		\Mockery::mock( 'WPML_Translation_Element_Factory' );

		$factory = new WCML_Product_Gallery_Filter_Factory();
		$this->assertInstanceOf( 'WCML_Product_Gallery_Filter', $factory->create() );
	}

	/**
	 * @test
	 */
	public function it_should_create_the_object_that_implements_iwpml_action() {

		\Mockery::mock( 'WPML_Translation_Element_Factory' );

		$factory = new WCML_Product_Gallery_Filter_Factory();

		$implements = class_implements( $factory->create() );
		$this->assertContains( 'IWPML_Action', $implements );

	}

}

if ( ! class_exists( 'WPML_WP_Cache' ) ) {
	/**
	 * Class WPML_Templates_Factory
	 * Stub for Test_WCML_Products_UI
	 */
	class WPML_WP_Cache {

		public function __construct() { /*silence is golden*/
		}

	}
}