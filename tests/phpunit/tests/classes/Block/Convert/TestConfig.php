<?php

namespace WCML\Block\Convert;

/**
 * @group block
 * @group block-convert
 */
class TestConfig extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldRetrieveBlockConfig() {
		$result = Config::get( 'woocommerce/product-category' );

		$this->assertInternalType( 'array', $result );
		$this->assertInternalType( 'array', $result[0] );
		$this->assertEquals( 'categories', $result[0]['name'] );
		$this->assertEquals( 'product_cat', $result[0]['type'] );
	}

	/**
	 * @test
	 */
	public function itShouldAnEmptyArrayIfConfigDoesNotExist() {
		$result = Config::get( 'foo/bar' );

		$this->assertEquals( [], $result );
	}
}
