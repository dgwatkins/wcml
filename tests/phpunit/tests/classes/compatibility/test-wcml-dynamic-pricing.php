<?php

class Test_WCML_Dynamic_Pricing extends OTGS_TestCase {

	public function setUp()
	{
		parent::setUp();

	}

	private function get_subject(){

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		return new WCML_Dynamic_Pricing( $sitepress );
	}

	/**
	 * @test
	 */
	public function is_admin_add_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_product_get__pricing_rules', array( $subject, 'translate_variations_in_rules' ) );
		$subject->add_hooks();

	}
	/**
	 * @test
	 */
	public function translate_variations_in_rules(){

		$variation_id = mt_rand( 1, 100 );
		$translated_variation_id = mt_rand ( 101, 200 );

		$rule = array(
			'variation_rules' => array(
				'args' => array(
					'variations' => array( $variation_id )
				)
			)
		);

		$rules = array( $rule );

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $variation_id, 'product_variation', true )
		        ->reply( $translated_variation_id );

		$subject        = $this->get_subject();
		$filtered_rules = $subject->translate_variations_in_rules( $rules );
		$this->assertEquals( array( $translated_variation_id ), $filtered_rules[0]['variation_rules']['args']['variations'] );

	}
}
