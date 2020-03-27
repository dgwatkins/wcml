<?php

class Test_WCML_Composite_Products extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Element_Translation_Package */
	private $tp;

	private function get_subject( $sitepress = null, $woocommerce_wpml = null, $tp = null ) {

		if ( null === $sitepress ) {
			$sitepress = $this->getMockBuilder( 'Sitepress' )
			                  ->disableOriginalConstructor()
			                  ->getMock();
		}

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			                         ->disableOriginalConstructor()
			                         ->getMock();
		}

		if ( null === $tp ) {
			$tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			           ->disableOriginalConstructor()
			           ->getMock();
		}

		return new WCML_Composite_Products( $sitepress, $woocommerce_wpml, $tp );
	}

	/**
	 * @test
	 */
	public function add_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array( $subject, 'replace_tm_editor_custom_fields_with_own_sections' ) );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function add_price_rounding_filters(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$subject = $this->get_subject();
		$filters = array(
			'woocommerce_product_get_price',
			'woocommerce_product_get_sale_price',
			'woocommerce_product_get_regular_price',
			'woocommerce_product_variation_get_price',
			'woocommerce_product_variation_get_sale_price',
			'woocommerce_product_variation_get_regular_price'
		);

		foreach( $filters as $filter ){
			\WP_Mock::expectFilterAdded( $filter, array( $subject, 'apply_rounding_rules' ), $subject::PRICE_FILTERS_PRIORITY_AFTER_COMPOSITE );
		}

		$subject->add_price_rounding_filters();
	}
	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections(){

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_bto_data', '_bto_scenario_data' ), $fields_to_hide );

	}

	/**
	 * @test
	 *
	 * @group wcml-2663
	 */
	public function it_should_apply_rounding_rules() {
		$price = mt_rand( 1, 100 );
		$converted_price = mt_rand( 101, 200 );
		$default_currency = 'USD';
		$client_currency = 'EUR';

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_client_currency' ) )
		                                         ->getMock();
		$woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$woocommerce_wpml->multi_currency->prices = $this->getMockBuilder( 'WCML_Prices' )
		                                                 ->disableOriginalConstructor()
		                                                 ->setMethods( array( 'apply_rounding_rules' ) )
		                                                 ->getMock();
		$woocommerce_wpml->multi_currency->prices->method( 'apply_rounding_rules' )->with( $price )->willReturn( $converted_price );

		\WP_Mock::userFunction(
			'wcml_get_woocommerce_currency_option',
			array(
				'return' => $default_currency
			)
		);

		\WP_Mock::userFunction(
			'wcml_is_multi_currency_on',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'is_composite_product',
			array(
				'return' => true
			)
		);


		$subject        = $this->get_subject( null, $woocommerce_wpml );
		$filtered_price = $subject->apply_rounding_rules( $price );

		$this->assertSame( $converted_price, $filtered_price );
	}

	/**
	 * @test
	 * @dataProvider it_should_not_apply_rounding_rules_data_provider
	 *
	 * @group wcml-2663
	 */
	public function it_should_not_apply_rounding_rules( $price, $is_multi_currency_on, $is_composite_product ) {

		\WP_Mock::userFunction(
			'wcml_is_multi_currency_on',
			array(
				'return' => $is_multi_currency_on,
			)
		);

		\WP_Mock::userFunction(
			'is_composite_product',
			array(
				'return' => $is_composite_product
			)
		);

		$subject        = $this->get_subject( );
		$filtered_price = $subject->apply_rounding_rules( $price );

		$this->assertSame( $price, $filtered_price );
	}

	/**
	 * Data provider for it_should_apply_rounding_rules.
	 *
	 * @return array
	 */
	public function it_should_not_apply_rounding_rules_data_provider() {
		return [
			[ 10, false, false ],
			[ 12, false, true ],
			[ 12, true, false ],
			[ '', true, true ],
		];
	}

	/**
	 * @test
	 */
	public function it_should_get_composite_data(){

		$product_id = 21;
		$bto_data = array( 11 => array( 'title' => 'test' ) );

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'args' => array( $product_id, '_bto_data', true ),
				'return' => $bto_data
			)
		);

		$subject = $this->get_subject();
		$composite_data = $subject->get_composite_data( $product_id );
		$this->assertEquals( $bto_data, $composite_data );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_if_composite_data_not_exists(){

		$product_id = 21;

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'args' => array( $product_id, '_bto_data', true ),
				'return' => false
			)
		);

		$subject = $this->get_subject();
		$composite_data = $subject->get_composite_data( $product_id );
		$this->assertEquals( array(), $composite_data );
	}

	/**
	 * @test
	 */
	public function it_should_sync_composite_data_across_translations(){

		\WP_Mock::passthruFunction( 'sanitize_title' );

		$original_product_id = 21;
		$current_product_id = 22;

		$product_type = new stdClass();
		$product_type->name = 'composite';
		$terms = [ $product_type ];

		\WP_Mock::userFunction(
			'wp_get_object_terms',
			[
				'args' => [ $original_product_id, 'product_type' ],
				'return' => $terms
			]
		);

		$assigned_original_product_id = 12;
		$assigned_original_category_id = 14;

		$original_bto_data[] = [
			'title'        => 'test title',
			'description'  => 'test description',
			'query_type'   => 'product_ids',
			'default_id'   => $assigned_original_product_id,
			'assigned_ids' => [ $assigned_original_product_id ],
		];

		$original_bto_data[] = [
			'title'                 => 'test title',
			'description'           => 'test description',
			'query_type'            => 'category_ids',
			'default_id'            => $assigned_original_category_id,
			'assigned_category_ids' => [ $assigned_original_category_id ],
		];

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args' => [ $original_product_id, '_bto_data', true ],
				'return' => $original_bto_data
			]
		);

		$translated_bto_data[] = [
			'title'        => 'test title FR',
			'description'  => 'test description FR',
			'query_type'   => 'product_ids',
			'default_id'   => $assigned_original_product_id,
			'assigned_ids' => [ $assigned_original_product_id ],
		];

		$translated_bto_data[] = [
			'title'                 => 'test title FR',
			'description'           => 'test description FR',
			'query_type'            => 'category_ids',
			'default_id'            => $assigned_original_category_id,
			'assigned_category_ids' => [ $assigned_original_category_id ],
		];

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args' => [ $current_product_id, '_bto_data', true ],
				'return' => $translated_bto_data
			]
		);

		$original_bto_scenario_data = [
			[
				'component_data' => [
					[ $assigned_original_product_id ],
					[ $assigned_original_category_id ],
				]
			]
		];

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args' => [ $original_product_id, '_bto_scenario_data', true ],
				'return' => $original_bto_scenario_data
			]
		);

		\WP_Mock::userFunction(
			'get_post_type',
			[
				'args' => [ $assigned_original_product_id ],
				'return' => 'product'
			]
		);

		\WP_Mock::userFunction(
			'get_post_type',
			[
				'args' => [ $assigned_original_category_id ],
				'return' => 'product_cat'
			]
		);



		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_element_trid', 'get_element_translations' ] )
		                  ->getMock();

		$en_translation                = new stdClass();
		$en_translation->language_code = 'en';
		$en_translation->original      = true;
		$en_translation->element_id    = $original_product_id;
		$translations['en']            = $en_translation;

		$fr_translation                = new stdClass();
		$fr_translation->language_code = 'fr';
		$fr_translation->element_id    = $current_product_id;
		$translations['fr']            = $fr_translation;

		$sitepress->method( 'get_element_translations' )->willReturn( $translations );

		$translated_assigned_product_id = 15;

		WP_Mock::onFilter( 'translate_object_id' )->with( $assigned_original_product_id, 'product', true, $fr_translation->language_code )->reply( $translated_assigned_product_id );
		WP_Mock::onFilter( 'translate_object_id' )->with( $assigned_original_product_id, 'product', false, $fr_translation->language_code )->reply( $translated_assigned_product_id );

		$expected_bto_data = $translated_bto_data;
		$expected_bto_data[0]['default_id'] = $translated_assigned_product_id;
		$expected_bto_data[0]['assigned_ids'] = [ $translated_assigned_product_id ];

		$translated_assigned_category_id  = 16;

		WP_Mock::onFilter( 'translate_object_id' )->with( $assigned_original_category_id, 'product_cat', false, $fr_translation->language_code )->reply( $translated_assigned_category_id );

		$expected_bto_data[1]['default_id'] = $translated_assigned_category_id;
		$expected_bto_data[1]['assigned_category_ids'] = [ $translated_assigned_category_id ];

		\WP_Mock::userFunction(
			'update_post_meta',
			[
				'args' => [ $current_product_id, '_bto_data', $expected_bto_data ],
				'times' => 1,
				'return' => true
			]
		);

		$expected_scenario_data = [
			[
				'component_data' => [
					[ $translated_assigned_product_id ],
					[ $translated_assigned_category_id ],
				]
			]
		];

		\WP_Mock::userFunction(
			'update_post_meta',
			[
				'args' => [ $current_product_id, '_bto_scenario_data', $expected_scenario_data ],
				'times' => 1,
				'return' => true
			]
		);

		$subject = $this->get_subject( $sitepress );
		$subject->sync_composite_data_across_translations( $original_product_id, $current_product_id );
	}

	/**
	 * @test
	 */
	public function it_should_not_sync_composite_data_across_translations_for_simple_product(){

		\WP_Mock::passthruFunction( 'sanitize_title' );

		$original_product_id = 10;
		$current_product_id = 11;

		$product_type = new stdClass();
		$product_type->name = 'simple';
		$terms = [ $product_type ];

		\WP_Mock::userFunction(
			'wp_get_object_terms',
			[
				'args' => [ $original_product_id, 'product_type' ],
				'return' => $terms
			]
		);

		$subject = $this->get_subject();
		$subject->sync_composite_data_across_translations( $original_product_id, $current_product_id );
	}
}
