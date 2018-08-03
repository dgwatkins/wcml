<?php

class Test_Woo_Var_Table extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function add_hooks() {

		$current_language = rand_str();

		$subject = new WCML_Woo_Var_Table( $current_language );

		\WP_Mock::expectFilterAdded( 'vartable_add_to_cart_product_id', array(
			$subject,
			'filter_add_to_cart_product_id'
		) );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function it_should_filter_add_to_cart_product_id() {

		$current_language   = rand_str();
		$product_id         = mt_rand( 1, 10 );
		$current_product_id = mt_rand( 11, 20 );

		$subject = new WCML_Woo_Var_Table( $current_language );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		) );

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $product_id, 'product', true, $current_language )
		        ->reply( $current_product_id );

		$filtered_product_id = $subject->filter_add_to_cart_product_id( $product_id );

		$this->assertEquals( $current_product_id, $filtered_product_id );


	}
}
