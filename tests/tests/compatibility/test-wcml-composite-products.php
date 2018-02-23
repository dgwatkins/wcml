<?php

/**
 * Class Test_WCML_Composite_Products
 */
class Test_WCML_Composite_Products extends WCML_UnitTestCase {

	private $composite;
	private $default_language;
	private $second_language;
	private $tp;
	private $test_data;


	function setUp() {

		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
		$this->tp = new WPML_Element_Translation_Package;

		$this->test_data = new stdClass();
		//add composite product
		$this->test_data->composite_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );
		wp_set_object_terms( $this->test_data->composite_product->id, 'composite', 'product_type', true );
		$this->test_data->translated_composite_product = $this->wcml_helper->add_product( $this->second_language, $this->test_data->composite_product->trid, random_string() );
		wp_set_object_terms( $this->test_data->translated_composite_product->id, 'composite', 'product_type', true );
		$this->settings_helper = wpml_load_settings_helper();
		$this->settings_helper->set_taxonomy_translatable( 'product_cat' );
	}

	/**
	 * @return WCML_Composite_Products
	 */
	private function get_test_subject( ) {
		return new WCML_Composite_Products( $this->sitepress, $this->woocommerce_wpml, $this->tp );
	}

	private function setup_composite_product_data( $product_id ){

		// setup components
		$components_data = array();

		//insert simple product to component
		$this->test_data->components['product']['original'] = $original_simple_product = $this->wcml_helper->add_product( $this->default_language, false, rand_str() );
		$this->test_data->components['product']['translated'] = $this->wcml_helper->add_product( $this->second_language, $original_simple_product->trid, rand_str() );

		$product_component_id = current_time( 'timestamp' ) + rand();
		$components_data[ $product_component_id ] = array(
			'component_id' 	=> $product_component_id,
			'query_type'	=> 'product_ids',
			'assigned_ids'	=> array( $original_simple_product->id ),
			'selection_mode'=> 'dropdowns',
			'default_id' 	=> $original_simple_product->id,
			'title' 		=> rand_str(),
			'description' 	=> rand_str(),
			'quantity_min'	=> 1,
			'quantity_max'	=> 1,
			'discount'		=> 5
		);

		//insert product category to component
		$this->test_data->components['category']['original'] = $original_category = $this->wcml_helper->add_term( rand_str(), 'product_cat', $this->default_language );
		$this->test_data->components['category']['translated'] = $this->wcml_helper->add_term( rand_str(), 'product_cat', $this->second_language, false, $original_category->trid );

		$category_component_id = current_time( 'timestamp' ) + rand();
		$components_data[ $category_component_id ] = array(
			'component_id' 	=> $category_component_id,
			'query_type'	=> 'category_ids',
			'assigned_category_ids'	=> array( $original_category->term_id ),
			'selection_mode'=> 'dropdowns',
			'default_id' 	=>  $original_simple_product->id,
			'title' 		=> rand_str(),
			'description' 	=> rand_str(),
			'quantity_min'	=> 1,
			'quantity_max'	=> 1,
			'discount'		=> 10
		);

		update_post_meta( $product_id, '_bto_data', $components_data );

		// setup scenarios
		$scenarios_data = array();
		$product_scenario_id = current_time( 'timestamp' ) + rand();
		$scenarios_data[ $product_scenario_id ] = array(
			'title'				=> rand_str(),
			'description'		=> rand_str(),
			'component_data'	=> array(
				$product_component_id  => array( $original_simple_product->id ),
				$category_component_id => array( $original_category->term_id )
			),
			'modifier'			=> array(
				$product_component_id   => 'in',
				$category_component_id  => 'in'
			)
		);

		update_post_meta( $product_id, '_bto_scenario_data', $scenarios_data );

		return array(
			'components' => $components_data,
			'scenarios'  => $scenarios_data,
		);
	}


	/**
	* @test
	*/
	public function components_update(){
		$composite_products = $this->get_test_subject();

		$original_composite_data = $this->setup_composite_product_data( $this->test_data->composite_product->id );

		$expected = array();
		$data = array();
		foreach( $original_composite_data['components'] as $component_id => $component ){
			$data[ md5( 'composite_'.$component_id.'_title' ) ] = $expected[ $component_id ][ 'title' ] = rand_str();
			$data[ md5( 'composite_'.$component_id.'_description' ) ] = $expected[ $component_id ][ 'description' ] = rand_str();
		}

		foreach( $original_composite_data['scenarios'] as $scenario_id => $scenario ){
			$data[ md5( 'composite_scenario_'.$scenario_id.'_title' ) ] = $expected[ $scenario_id ][ 'title' ] = rand_str();
			$data[ md5( 'composite_scenario_'.$scenario_id.'_description' ) ] = $expected[ $scenario_id ][ 'description' ] = rand_str();
		}

		$tr_composite_data = $composite_products->components_update( $this->test_data->composite_product->id, $this->test_data->translated_composite_product, $data, $this->second_language );

		foreach( $tr_composite_data['components'] as $component_id => $component ){
			$this->assertEquals( $expected[ $component_id ][ 'title' ], $component[ 'title' ] );
			$this->assertEquals( $expected[ $component_id ][ 'description' ], $component[ 'description' ] );
			//check sync ids
			if( $component[ 'query_type' ] == 'product_ids' ){
				$this->assertEquals( array( $this->test_data->components['product']['translated']->id ), $component[ 'assigned_ids' ] );
			}elseif( $component[ 'query_type' ] == 'category_ids' ){
				$this->assertEquals( array( $this->test_data->components['category']['translated']->term_id ), $component[ 'assigned_category_ids' ] );
			}
			//check sync default
			$this->assertEquals( $this->test_data->components['product']['translated']->id, $component[ 'default_id' ] );
		}

		foreach( $tr_composite_data['scenarios'] as $scenario_id => $scenario ){
			$this->assertEquals( $expected[ $scenario_id ][ 'title' ], $scenario[ 'title' ] );
			$this->assertEquals( $expected[ $scenario_id ][ 'description' ], $scenario[ 'description' ] );
			//check sync ids
			foreach( $scenario[ 'component_data' ] as $compon_id => $component_data ){
				if( $original_composite_data['components'][ $compon_id ][ 'query_type' ] == 'product_ids' ){
					$this->assertEquals( array( $this->test_data->components['product']['translated']->id ), $component_data );
				}elseif( $original_composite_data['components'][ $compon_id ][ 'query_type' ] == 'category_ids' ){
					$this->assertEquals( array( $this->test_data->components['category']['translated']->term_id ), $component_data );
				}
			}
		}

	}

	/**
	 * @test
	 */
	public function custom_box_html_data(){

		$composite_products = $this->get_test_subject();

		$original_composite_data = $this->setup_composite_product_data( $this->test_data->composite_product->id );
		$expected = array();
		foreach( $original_composite_data['components'] as $component_id => $component ){
			$expected[ 'composite_'.$component_id.'_title' ] = array(
				'original'    => $component[ 'title' ],
				'translation' => '',
			);
			$expected[ 'composite_'.$component_id.'_description' ] = array(
				'original'    => $component[ 'description' ],
				'translation' => '',
			);
		}

		foreach( $original_composite_data['scenarios'] as $scenario_id => $scenario ){
			$expected[ 'composite_scenario_'.$scenario_id.'_title' ] = array(
				'original'    => $scenario[ 'title' ],
				'translation' => '',
			);
			$expected[ 'composite_scenario_'.$scenario_id.'_description' ] = array(
				'original'    => $scenario[ 'description' ],
				'translation' => '',
			);
		}


		$this->assertEquals( $expected, $composite_products->custom_box_html_data( array(), $this->test_data->composite_product->id, get_post( $this->test_data->translated_composite_product->id ), $this->second_language ) );

	}

	/**
	 * @test
	 */
	public function custom_box_html(){
		$composite_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );
		wp_set_object_terms( $composite_product->id, 'composite', 'product_type', true );

		$job_details = array(
			'job_type'             => 'product',
			'job_id'               => $composite_product->id,
			'target'               => $this->second_language,
			'translation_complete' => true,
		);

		$obj = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

		$composite_products = $this->get_test_subject();

		$original_composite_data = $this->setup_composite_product_data( $composite_product->id );
		$data = $composite_products->custom_box_html_data( array(), $composite_product->id, false, $this->second_language );
		$composite_products->custom_box_html( $obj, $composite_product->id, $data );

		$product_obj = get_post( $composite_product->id );
		$expected = array(
			array(
				'title'                 => 'Title',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'title',
				'field_data'            => $product_obj->post_title,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Slug',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'slug',
				'field_data'            => $product_obj->post_name,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Content / Description',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'product_content',
				'field_data'            => $product_obj->post_content,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'product_excerpt',
				'field_data'            => $product_obj->post_excerpt,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '1',
				'field_type'            => '_purchase_note',
				'field_data'            => '',
				'field_data_translated' => '',
				'field_finished'        => '0',
			)
		);


		foreach( $original_composite_data['components'] as $component_id => $component ){
			$expected[] = array(
				'title'                 => 'Name',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'composite_'.$component_id.'_title',
				'field_data'            => $component[ 'title' ],
				'field_data_translated' => '',
				'field_finished'        => '0'
			);
			$expected[] = array(
				'title'                 => 'Description',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'composite_'.$component_id.'_description',
				'field_data'            => $component[ 'description' ],
				'field_data_translated' => '',
				'field_finished'        => '0'
			);
		}

		foreach( $original_composite_data['scenarios'] as $scenario_id => $scenario ) {
			$expected[] = array(
				'title' => 'Name',
				'tid' => '0',
				'field_style' => '0',
				'field_type' => 'composite_scenario_' . $scenario_id . '_title',
				'field_data' => $scenario['title'],
				'field_data_translated' => '',
				'field_finished' => '0'
			);
			$expected[] = array(
				'title' => 'Description',
				'tid' => '0',
				'field_style' => '0',
				'field_type' => 'composite_scenario_' . $scenario_id . '_description',
				'field_data' => $scenario['description'],
				'field_data_translated' => '',
				'field_finished' => '0'
			);
		}

		$this->assertEquals( $expected, $obj->get_all_fields() );
	}


	/**
	 * @test
	 */
	public function filter_composite_product_cost() {
		$base_regular_price = random_int( 1, 99999 );
		$base_sale_price = random_int( 1, 99999 );
		$base_price = random_int( 1, 9999 );
		$check = null;
		$composite_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );
		wp_set_object_terms( $composite_product->id, 'composite', 'product_type', true );

		$usd_code  = 'USD';

		$rates = array(
			$usd_code => random_int( 1, 9 ),
		);

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )->disableOriginalConstructor()->getMock();

		$woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->willReturn( $this->default_language );

		$woocommerce_wpml->products
			->method( 'get_original_product_id' )
			->willReturn( $composite_product->id );

		$woocommerce_wpml->multi_currency
			->method( 'get_client_currency' )
			->willReturn( $usd_code);

		$woocommerce_wpml->multi_currency->prices
			->method( 'convert_price_amount' )
			->willReturnMap( array(
				array(
					(string)$base_regular_price,
					$usd_code,
					$base_regular_price,
				),
				array(
					(string)$base_sale_price,
					$usd_code,
					$base_sale_price,
				),
				array(
					(string)$base_price,
					$usd_code,
					$base_price * $rates[ $usd_code ],
				)
			) );

		$wcml_composite_products = new WCML_Composite_Products( $this->sitepress, $woocommerce_wpml, $this->tp );

		update_post_meta( $composite_product->id, '_bto_base_regular_price', $base_regular_price );
		update_post_meta( $composite_product->id, '_bto_base_sale_price', $base_sale_price );
		update_post_meta( $composite_product->id, '_bto_base_price', $base_price );

		$this->assertEquals( $base_regular_price, $wcml_composite_products->filter_composite_product_cost( $check, $composite_product->id, '_bto_base_regular_price', true ) );
		$this->assertEquals( $base_sale_price, $wcml_composite_products->filter_composite_product_cost( $check, $composite_product->id, '_bto_base_sale_price', true ) );
		$this->assertEquals( $base_price * $rates[ $usd_code ], $wcml_composite_products->filter_composite_product_cost( $check, $composite_product->id, '_bto_base_price', true ) );

		$base_price = random_int( 1, 9999 );
		update_post_meta( $composite_product->id, '_bto_base_price_' . $usd_code, $base_price );
		update_post_meta( $composite_product->id, '_wcml_custom_prices_status', true );
		$this->assertEquals( $base_price, $wcml_composite_products->filter_composite_product_cost( $check, $composite_product->id, '_bto_base_price', true ) );
	}

	function tearDown() {
		parent::tearDown();
		$this->settings_helper->set_taxonomy_not_translatable( 'product_cat' );
	}

}
