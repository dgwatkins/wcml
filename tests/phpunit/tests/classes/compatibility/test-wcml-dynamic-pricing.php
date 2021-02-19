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

		\WP_Mock::expectFilterAdded( 'woocommerce_dynamic_pricing_is_object_in_terms', [ $subject, 'is_object_in_translated_terms' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'wc_dynamic_pricing_load_modules', [ $subject, 'filter_price' ] );
		\WP_Mock::expectFilterAdded( 'wc_dynamic_pricing_load_modules', [ $subject, 'translate_collector_args' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_dynamic_pricing_is_applied_to', [ $subject, 'woocommerce_dynamic_pricing_is_applied_to' ], 10, 5 );
		\WP_Mock::expectFilterAdded( 'woocommerce_dynamic_pricing_get_rule_amount', [ $subject, 'woocommerce_dynamic_pricing_get_rule_amount' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'dynamic_pricing_product_rules', [ $subject, 'dynamic_pricing_product_rules' ] );

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
	 * @param string                          $post_type
	 */
	public function it_does_process_discounts( WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance, $properties = [], $post_type = 'product' ) {
		$taxonomy = 'product_cat';

		foreach ( $properties as $property => $value ) {
			$dynamic_pricing_instance->$property = $value;
			if ( 'taxonomy' === $property ) {
				$taxonomy = $value;
			}
		}

		$product = $this->get_product_mock();

		$product_id = 123;
		$parent_id  = 99;

		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'get_parent_id' )->willReturn( $parent_id );
		$product->post_type = $post_type;

		$cat_ids = [ 1, 2, 3 ];

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'is_object_in_term', [
			'times'  => 1,
			'args'   => [
			    'product_variation' === $post_type ? $parent_id : $product_id,
			    $taxonomy,
			    $cat_ids
            ],
			'return' => true,
		] );

		$this->assertTrue( $subject->woocommerce_dynamic_pricing_is_applied_to( false, $product, 1, $dynamic_pricing_instance, $cat_ids ) );
	}

	/**
	 * @test
	 * @dataProvider dp_included_dynamic_pricing_instances
	 *
	 * @param \WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance
	 * @param array                           $properties
	 * @param string                          $post_type
	 */
	public function it_does_process_discounts_for_translations( WC_Dynamic_Pricing_Simple_Base $dynamic_pricing_instance, $properties = [], $post_type = 'product' ) {
		$taxonomy = 'product_cat';

		foreach ( $properties as $property => $value ) {
			$dynamic_pricing_instance->$property = $value;
			if ( 'taxonomy' === $property ) {
				$taxonomy = $value;
			}
		}

		$product = $this->get_product_mock();
		$product->post_type = $post_type;

		$product_id = 123;
		$parent_id  = 99;

		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'get_parent_id' )->willReturn( $parent_id );

		$tr_product_id = 246;
		$tr_parent_id  = 198;

		WP_Mock::onFilter( 'wpml_object_id' )
			->with( 'product_variation' === $post_type ? $parent_id : $product_id, 'product', true )
			->reply( 'product_variation' === $post_type ? $tr_parent_id : $tr_product_id );

		$cat_ids    = [ 1, 2, 3 ];
		$tr_cat_ids = [ 4, 5, 6 ];
		for ( $i = 0; $i < 3; $i ++ ) {
			WP_Mock::onFilter( 'translate_object_id' )
				->with( $cat_ids[ $i ], $taxonomy, true )
				->reply( $tr_cat_ids[ $i ] );
		}

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'is_object_in_term', [
			'times'  => 1,
			'args'   => [
			    'product_variation' === $post_type ? $tr_parent_id : $tr_product_id,
			    $taxonomy,
			    $tr_cat_ids
            ],
			'return' => true,
		] );

		$this->assertTrue( $subject->woocommerce_dynamic_pricing_is_applied_to( false, $product, 1, $dynamic_pricing_instance, $cat_ids ) );
	}

	public function dp_ignored_dynamic_pricing_instances() {
		return [
			'WC_Dynamic_Pricing_Simple_Category'   => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Category' ),
				[
					'available_rulesets' => [],
				],
			],
			'WC_Dynamic_Pricing_Simple_Membership' => [
                $this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Membership' ),
				[
					'available_rulesets' => [],
				],
			],
			'WC_Dynamic_Pricing_Simple_Product'    => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Product' ),
				[
					'available_rulesets' => [],
				],
			],
		];
	}

	public function dp_included_dynamic_pricing_instances() {
		return [
			'WC_Dynamic_Pricing_Advanced_Category' => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Advanced_Category' ),
				[
					'adjustment_sets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Advanced_Taxonomy' => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Advanced_Taxonomy' ),
				[
					'adjustment_sets' => [ 1, 2, 3 ],
					'taxonomy'        => 'a-taxonomy'
				],
			],
			'WC_Dynamic_Pricing_Advanced_Totals'   => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Advanced_Totals' ),
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
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Category' ),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Membership' => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Membership' ),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Product'    => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Product' ),
				[
					'available_rulesets' => [ 1, 2, 3 ],
				],
			],
			'WC_Dynamic_Pricing_Simple_Taxonomy'   => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Taxonomy' ),
				[
					'available_rulesets' => [ 1, 2, 3 ],
					'taxonomy'           => 'a-taxonomy',
				],
			],
			'With a product variation'   => [
				$this->get_dynamic_pricing_mock( 'WC_Dynamic_Pricing_Simple_Taxonomy' ),
				[
					'available_rulesets' => [ 1, 2, 3 ],
					'taxonomy'           => 'a-taxonomy',
				],
                'product_variation',
			],
		];
	}

	/**
	 * @param $classname
	 * @return \PHPUnit\Framework\MockObject\MockObject|PHPUnit_Framework_MockObject_MockObject|WC_Dynamic_Pricing_Simple_Base
	 */
	private function get_dynamic_pricing_mock( $classname ) {
	    return $this->getMockBuilder( 'WC_Dynamic_Pricing_Simple_Base' )
            ->setMockClassName( $classname )
            ->disableOriginalConstructor()
            ->getMock();
    }

	/**
	 * @test
	 */
	public function it_translates_collector_args() {
		$catid   = 1;
		$trcatid = 2;
		$modules = [
			'advanced-category' => (object) [ 'available_advanced_rulesets' => [
				'rule1' => [
					'targets' => [ $catid ],
					'collector' => [ 'args' => [ 'cats' => [ $catid ] ] ],
				]
			] ],
		];
		$expected = [
			'advanced-category' => (object) [ 'available_advanced_rulesets' => [
				'rule1' => [
					'targets' => [ $trcatid ],
					'collector' => [ 'args' => [ 'cats' => [ $trcatid ] ] ],
				]
			] ],
		];

		$subject = $this->get_subject();

		\WP_Mock::onFilter( 'translate_object_id' )
			->with( $catid, 'product_cat', true )
			->reply( $trcatid );

		$this->assertEquals( $expected, $subject->translate_collector_args( $modules ) );
	}

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|PHPUnit_Framework_MockObject_MockObject|WC_Product
	 */
	private function get_product_mock() {
        return $this->getMockBuilder( 'WC_Product' )
            ->disableOriginalConstructor()
            ->setMethods( [ 'get_id', 'get_parent_id' ] )
            ->getMock();
    }
}
