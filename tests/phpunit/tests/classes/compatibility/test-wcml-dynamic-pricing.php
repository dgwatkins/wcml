<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WCML_Dynamic_Pricing
 *
 * @group dynamic-pricing
 */
class Test_WCML_Dynamic_Pricing extends OTGS_TestCase {

	private function get_subject( $sitepress = null ) {

		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress_mock();
		}

		return new WCML_Dynamic_Pricing( $sitepress );
	}

	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'Sitepress' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function is_admin_add_hooks(){
		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_product_get__pricing_rules', [ $subject, 'translate_variations_in_rules' ] );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function it_should_hide_language_switcher_for_settings_page() {

		$sitepress = $this->get_sitepress_mock();

		FunctionMocker::replace( 'filter_input', 'wc_dynamic_pricing' );

		WP_Mock::userFunction( 'remove_action', [
			'times' => 1,
			'args'  => [ 'wp_before_admin_bar_render', [ $sitepress, 'admin_language_switcher' ] ],
		] );

		$subject = $this->get_subject( $sitepress );
		$subject->hide_language_switcher_for_settings_page();
	}

	/**
	 * @test
	 */
	public function is_frontend_add_hooks(){
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_dynamic_pricing_is_object_in_terms', [ $subject, 'is_object_in_translated_terms' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'wc_dynamic_pricing_load_modules', [ $subject, 'filter_price' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_dynamic_pricing_is_applied_to', [ $subject, 'woocommerce_dynamic_pricing_is_applied_to' ], 10, 5 );
		\WP_Mock::expectFilterAdded( 'woocommerce_dynamic_pricing_get_rule_amount', [ $subject, 'woocommerce_dynamic_pricing_get_rule_amount' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'dynamic_pricing_product_rules', [ $subject, 'dynamic_pricing_product_rules' ] );
		\WP_Mock::expectFilterAdded( 'wcml_calculate_totals_exception', [ $subject, 'calculate_totals_exception' ] );

		\WP_Mock::expectFilterAdded( 'woocommerce_product_get__pricing_rules', [ $subject, 'translate_variations_in_rules' ] );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function it_translates_variations_in_rules(){

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

	/**
	 * @test
	 */
	public function it_does_not_translate_variations_in_rules(){

		$variation_id = mt_rand( 1, 100 );
		$translated_variation_id = mt_rand ( 101, 200 );

		$rule = array(
			'variation_rules' => array(
				'args' => array()
			)
		);

		$rules = array( $rule );

		$subject        = $this->get_subject();
		$filtered_rules = $subject->translate_variations_in_rules( $rules );
		$this->assertEquals( $rules, $filtered_rules );

	}

	/**
	 * @test
	 * @dataProvider dp_ignored_dynamic_pricing_instances
	 *
	 * @param \WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance
	 * @param array                           $properties
	 */
	public function it_does_not_process_discounts( WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance, $properties = [] ) {
		foreach ( $properties as $property => $value ) {
			$dynamic_pricing_instance->$property = $value;
		}

		/** @var WC_Product|PHPUnit_Framework_MockObject_MockBuilder $product */
		$product = $this->getMockBuilder( 'WC_Product' )->disableOriginalConstructor()->setMethods( [ 'get_id' ] )->getMock();

		$product_id = 1;
		$product->method( 'get_id' )->willReturn( $product_id );

		$cat_ids = [ 1, 2, 3 ];

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'is_object_in_term', [ 'times' => 0 ] ); //1, 'args' => [$product_id, $taxonomy]]);

		$this->assertFalse( $subject->woocommerce_dynamic_pricing_is_applied_to( false, $product, 1, $dynamic_pricing_instance, $cat_ids ) );
	}

	/**
	 * @test
	 * @dataProvider dp_included_dynamic_pricing_instances
	 *
	 * @param \WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance
	 * @param array                           $properties
	 */
	public function it_does_process_discounts( WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance, $properties = [] ) {
		$taxonomy = 'product_cat';

		foreach ( $properties as $property => $value ) {
			$dynamic_pricing_instance->$property = $value;
			if ( 'taxonomy' === $property ) {
				$taxonomy = $value;
			}
		}


		/** @var WC_Product|PHPUnit_Framework_MockObject_MockBuilder $product */
		$product = $this->getMockBuilder( 'WC_Product' )->disableOriginalConstructor()->setMethods( [ 'get_id' ] )->getMock();

		$product_id = 1;
		$product->method( 'get_id' )->willReturn( $product_id );

		$cat_ids = [ 1, 2, 3 ];

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'is_object_in_term', [
			'times'  => 1,
			'args'   => [ $product_id, $taxonomy, $cat_ids ],
			'return' => true,
		] );

		$this->assertTrue( $subject->woocommerce_dynamic_pricing_is_applied_to( false, $product, 1, $dynamic_pricing_instance, $cat_ids ) );
	}

	public function dp_ignored_dynamic_pricing_instances() {
		return [
			'WC_Dynamic_Pricing_Simple_Category'   => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Category' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [],
				],
			],
			'WC_Dynamic_Pricing_Simple_Membership' => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Membership' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [],
				],
			],
			'WC_Dynamic_Pricing_Simple_Product'    => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Product' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [],
				],
			],
		];
	}

	public function dp_included_dynamic_pricing_instances() {
		return [
			'WC_Dynamic_Pricing_Advanced_Category' => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Advanced_Category' )->disableOriginalConstructor()->getMock(),
				[
					'adjustment_sets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Advanced_Taxonomy' => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Advanced_Taxonomy' )->disableOriginalConstructor()->getMock(),
				[
					'adjustment_sets' => [ 1, 2, 3 ],
					'taxonomy'        => 'a-taxonomy'
				],
			],
			'WC_Dynamic_Pricing_Advanced_Totals'   => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Advanced_Totals' )->disableOriginalConstructor()->getMock(),
				[
					'adjustment_sets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Base'       => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Category'   => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Category' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Membership' => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Membership' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Product'    => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Product' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Taxonomy'   => [
				$this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )->setMockClassName( 'WC_Dynamic_Pricing_Simple_Taxonomy' )->disableOriginalConstructor()->getMock(),
				[
					'available_rulesets' => [ 1, 2, 3 ],
					'taxonomy'           => 'a-taxonomy',
				],
			],
		];
	}
}
