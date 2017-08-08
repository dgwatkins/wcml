<?php

class Test_WCML_Table_Rate_Shipping extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();
	}

	private function get_woocommerce_wpml(){

		return $this->getMockBuilder('woocommerce_wpml')
		                               ->disableOriginalConstructor()
		                               ->getMock();
	}

	private function get_sitepress(){
		return $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->getMock();
	}

	private function get_subject(){

		return new WCML_Table_Rate_Shipping( $this->get_sitepress(), $this->get_woocommerce_wpml() );
	}

	/**
	 * @test
	 */
	public function front_adds_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'get_the_terms', array( $subject, 'shipping_class_id_in_default_language' ), 10, 3 );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function mc_adds_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_table_rate_query_rates_args', array( $subject, 'filter_query_rates_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_table_rate_package_row_base_price', array( $subject, 'filter_product_base_price' ), 10, 3 );
		$subject->add_hooks();

	}

}
