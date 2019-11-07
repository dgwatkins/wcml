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

}
