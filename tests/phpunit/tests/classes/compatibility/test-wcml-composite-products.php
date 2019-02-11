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
		\WP_Mock::expectFilterAdded( 'raw_woocommerce_price', array( $subject, 'apply_rounding_rules' ) );
		$subject->add_hooks();

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
	 * @dataProvider it_should_apply_rounding_rules_data_provider
	 *
	 * @group wcml-2663
	 */
	public function it_should_apply_rounding_rules( $is_multi_currency_on ) {
		$price           = mt_rand( 1, 100 );
		$converted_price = mt_rand( 101, 200 );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->getMock();

		$woocommerce_wpml->multi_currency->prices = $this->getMockBuilder( 'WCML_Prices' )
		                                                 ->disableOriginalConstructor()
		                                                 ->setMethods( array( 'apply_rounding_rules' ) )
		                                                 ->getMock();
		$woocommerce_wpml->multi_currency->prices->method( 'apply_rounding_rules' )->with( $price )->willReturn( $converted_price );

		\WP_Mock::userFunction(
			'wcml_is_multi_currency_on',
			array(
				'return' => $is_multi_currency_on,
			)
		);


		$subject        = $this->get_subject( null, $woocommerce_wpml );
		$filtered_price = $subject->apply_rounding_rules( $price );

		if ( $is_multi_currency_on ) {
			$this->assertSame( $converted_price, $filtered_price );
		} else {
			$this->assertSame( $price, $filtered_price );
		}
	}

	/**
	 * Data provider for it_should_apply_rounding_rules.
	 *
	 * @return array
	 */
	public function it_should_apply_rounding_rules_data_provider() {
		return array(
			array( false ),
			array( true ),
		);
	}
}
