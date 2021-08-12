<?php

namespace WCML\Products\Hooks;

use WCML\Products\Hooks;
use WPML\LIB\WP\OnActionMock;

/**
 * @group products
 */
class TestHooks extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		return parent::tearDown();
	}

	/**
	 * @test
	 * @group wcml-3671
	 */
	public function itForcesProductLanguageInQueryForVariableChildren() {
		$productId   = 123;
		$productLang = 'fr';

		$args = [
			'post_parent' => $productId,
		];

		$expectedArgs              = $args;
		$expectedArgs['wpml_lang'] = $productLang;

		\WP_Mock::onFilter( 'wpml_element_language_code' )
			->with( '', [ 'element_id' => $productId, 'element_type' => 'product_variation' ] )
			->reply( $productLang );

		( new Hooks() )->add_hooks();

		$this->assertEquals(
			$expectedArgs,
			$this->runFilter( 'woocommerce_variable_children_args', $args )
		);
	}
}