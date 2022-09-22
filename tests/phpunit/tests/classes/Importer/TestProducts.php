<?php

namespace WCML\Importer;

use WPML\LIB\WP\OnActionMock;

class Test_Products extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itSynchronizesProducts() {
		$product_id = '123';

		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_id' ] )
			->getMock();
		$product->method( 'get_id' )->willReturn( $product_id );

		\WP_Mock::expectAction( 'wpml_sync_all_custom_fields', $product_id );

		$wooCommerceWpml = $this->getWooCommerceWpml();
		\WP_Mock::userFunction( 'WCML\functions\getWooCommerceWpml', [
			'return' => $wooCommerceWpml,
		] );

		\WP_Mock::userFunction( 'get_post', [
			'times' => 1,
			'with'  => $product_id,
		] );

		( new Products() )->add_hooks();

		$this->runAction( 'woocommerce_product_import_inserted_product_object', $product );
	}

	private function getWooCommerceWpml() {
		$wooCommerceWpml = $this->getMockBuilder( \woocommerce_wpml::class )
			->disableOriginalConstructor()
			->getMock();

		$wooCommerceWpml->sync_product_data = $this->getMockBuilder( \WCML_Synchronize_Product_Data::class )
			->disableOriginalConstructor()
			->getMock();

		return $wooCommerceWpml;
	}

}
