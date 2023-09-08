<?php

namespace WCML\Rest\Wrapper\Attributes;

use WCML\Rest\Wrapper\ProductAttributes;

/**
 * @group rest
 * @group rest-product-attributes
 */
class TestProductAttributes extends \OTGS_TestCase {

	private $product_save_actions;
	/** @var \WCML_WC_Strings */
	private $strings;

	public function setUp() {
		parent::setUp();

		$this->strings = $this->getMockBuilder( 'WCML_WC_Strings' )
							  ->disableOriginalConstructor()
							  ->setMethods( array( 'get_translated_string_by_name_and_context' ) )
							  ->getMock();
	}


	function get_subject() {
		return new ProductAttributes( $this->strings );
	}

	/**
	 * @test
	 */
	public function translate_product_attributes() {
		$lang = 'de';
		$attribute_english = 'Size';
		$attribute_deutsch = 'Große';

		$response = $this->getMockBuilder( 'WP_REST_Response' )
			->disableOriginalConstructor()
			->getMock();

		$object = $this->getMockBuilder( 'WP_Term' )
			->disableOriginalConstructor()
			->getMock();
		$object->attribute_label = $attribute_english;

		$request = $this->getMockBuilder( 'WP_REST_Request' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_params' ] )
			->getMock();
		$request->method( 'get_params' )->willReturn( [
			'lang' => $lang,
		] );

		$this->strings->method( 'get_translated_string_by_name_and_context' )
			->with( 'WordPress', 'taxonomy singular name: ' . $attribute_english, $lang, $attribute_english)
			->willReturn( $attribute_deutsch );

		$product_attributes = $this->get_subject()->prepare( $response, $object, $request );

		$this->assertEquals( $product_attributes->data['name'], $attribute_deutsch );
	}
}
