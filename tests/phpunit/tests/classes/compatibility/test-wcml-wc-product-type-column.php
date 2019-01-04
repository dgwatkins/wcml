<?php

class Test_WCML_WC_Product_Type_Column extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function add_hooks() {

		$subject = new WCML_WC_Product_Type_Column();

		\WP_Mock::expectFilterAdded( 'wcml_show_type_column', array(
			$subject,
			'show_type_column'
		) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_show_type_column() {

		$subject = new WCML_WC_Product_Type_Column();

		\WP_Mock::userFunction( 'wp_enqueue_style', array(
			'args'   => array( 'wc-product-type-column-admin-styles' ),
			'return' => true
		) );


		$this->assertTrue( $subject->show_type_column( false ) );


	}
}
