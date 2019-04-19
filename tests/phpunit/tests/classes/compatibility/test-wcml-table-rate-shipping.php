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

	private function get_subject( $sitepress = null, $woocommerce_wpml = null ){

		if( !$sitepress ){
			$sitepress = $this->get_sitepress();
		}

		if( !$woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		return new WCML_Table_Rate_Shipping( $sitepress, $woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function front_adds_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'get_the_terms', array( $subject, 'shipping_class_id_in_default_language' ), 10, 3 );
		\WP_Mock::expectFilterAdded(  'woocommerce_shipping_table_rate_is_available', array( $subject, 'shipping_table_rate_is_available' ), 10, 3 );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function mc_adds_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api' ) )
		                  ->getMock();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant', 'version_compare' ) )
		               ->getMock();

		$wp_api->method( 'version_compare' )->willReturn( true );

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$subject = $this->get_subject( $sitepress );
		\WP_Mock::expectFilterAdded( 'woocommerce_table_rate_query_rates_args', array( $subject, 'filter_query_rates_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_table_rate_package_row_base_price', array( $subject, 'filter_product_base_price' ), 10, 3 );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function it_should_filter_table_rate_priorities() {

		$class_instance_id = 2;
		$values            = array(
			'class_slug' => $class_instance_id
		);

		$translated_class       = new stdClass();
		$translated_class->slug = 'slug-de';

		WP_Mock::userFunction( 'get_term_by', array(
			'args'   => array( 'slug', 'class_slug', 'product_shipping_class' ),
			'return' => $translated_class
		) );

		$subject         = $this->get_subject();
		$filtered_values = $subject->filter_table_rate_priorities( $values );

		$this->assertEquals( array( $translated_class->slug => $class_instance_id ), $filtered_values );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_table_rate_priorities_with_same_slug() {

		$class_instance_id = 2;
		$values            = array(
			'class_slug' => $class_instance_id
		);

		$translated_class       = new stdClass();
		$translated_class->slug = 'class_slug';

		WP_Mock::userFunction( 'get_term_by', array(
			'args'   => array( 'slug', 'class_slug', 'product_shipping_class' ),
			'return' => $translated_class
		) );

		$subject         = $this->get_subject();
		$filtered_values = $subject->filter_table_rate_priorities( $values );

		$this->assertEquals( array( $translated_class->slug => $class_instance_id ), $filtered_values );
	}

	/**
	 * @test
	 */
	public function it_should_re_check_shipping_table_rate_is_available() {

		\WP_Mock::wpPassthruFunction( 'remove_filter' );
		$available = false;

		$object = $this->getMockBuilder( 'WC_Shipping_Method' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_available' ) )
		                     ->getMock();
		$object->method( 'is_available' )->willReturn( true );

		$object->instance_id = mt_rand( 1, 10 );

		$subject         = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'option_woocommerce_table_rate_priorities_'.$object->instance_id, array( $subject, 'filter_table_rate_priorities' ) );

		$filtered_values = $subject->shipping_table_rate_is_available( $available, array(), $object );
	}

	/**
	 * @test
	 */
	public function it_should_filter_product_base_price() {

		$default_currency = 'USD';
		$client_currency = 'EUR';

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( 'woocommerce_currency' ),
			'times' => 1,
			'return' => $default_currency
		));

		$product_id = 1;
		$product_price = 10;
		$qty = 3;

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_id' ) )
		                ->getMock();
		$product->method( 'get_id' )->willReturn( $product_id );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method('get_client_currency')->willReturn( $client_currency );


		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_product_price_from_db' ) )
		                                         ->getMock();
		$this->woocommerce_wpml->products->method('get_product_price_from_db')->with( $product_id )->willReturn( $product_price );

		$subject         = $this->get_subject( null, $this->woocommerce_wpml );

		$this->assertEquals( $product_price * $qty, $subject->filter_product_base_price( 10, $product, $qty ) );
	}

}
