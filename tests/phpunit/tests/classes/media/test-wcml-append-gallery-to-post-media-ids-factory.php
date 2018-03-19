<?php

/**
 * Class Test_WCML_Append_Gallery_To_Post_Media_Ids_Factory
 * @group product-media
 */
class Test_WCML_Append_Gallery_To_Post_Media_Ids_Factory extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_create_the_object() {

		$factory = new WCML_Append_Gallery_To_Post_Media_Ids_Factory();
		$this->assertInstanceOf( 'WCML_Append_Gallery_To_Post_Media_Ids', $factory->create() );
	}

	/**
	 * @test
	 */
	public function it_should_create_the_object_that_implements_iwpml_action() {

		$factory = new WCML_Append_Gallery_To_Post_Media_Ids_Factory();

		$implements = class_implements( $factory->create() );
		$this->assertContains( 'IWPML_Action', $implements );

	}

}