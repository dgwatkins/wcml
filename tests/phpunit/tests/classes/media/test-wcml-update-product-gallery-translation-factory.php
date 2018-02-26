<?php

/**
 * Class Test_WCML_Update_Product_Gallery_Translation_Factory
 * @group product-media
 */
class Test_WCML_Update_Product_Gallery_Translation_Factory extends OTGS_TestCase {

	public function tearDown(){
		global $sitepress;
		unset( $sitepress );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_should_create_the_object() {
		global $sitepress;
		\Mockery::mock( 'WPML_Translation_Element_Factory' );
		$sitepress = \Mockery::mock( 'Sitepress' );

		$factory = new WCML_Update_Product_Gallery_Translation_Factory();
		$this->assertInstanceOf( 'WCML_Update_Product_Gallery_Translation', $factory->create() );
	}

	/**
	 * @test
	 */
	public function it_should_create_the_object_that_implements_iwpml_action() {
		global $sitepress;

		\Mockery::mock( 'WPML_Translation_Element_Factory' );
		$sitepress = \Mockery::mock( 'Sitepress' );

		$factory = new WCML_Update_Product_Gallery_Translation_Factory();

		$implements = class_implements( $factory->create() );
		$this->assertContains( 'IWPML_Action', $implements );

	}

}