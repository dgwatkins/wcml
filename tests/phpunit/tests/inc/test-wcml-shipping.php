<?php

/**
 * Class Test_WCML_Shipping
 */
class Test_WCML_Shipping extends OTGS_TestCase {

	private function get_subject( $sitepress = null, $wcml_strings = null ){

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress_mock();
		}

		if( null === $wcml_strings ){
			$wcml_strings = $this->get_wcml_strings_mock();
		}

		return new WCML_WC_Shipping( $sitepress, $wcml_strings );
	}

	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( array(
			            'get_current_language',
			            'get_element_trid',
			            'get_element_translations'
		            ) )
		            ->getMock();

	}

	private function get_wp_term_mock() {
		return $this->getMockBuilder( 'WP_Term' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	private function get_wcml_strings_mock(){
		return $this->getMockBuilder( WCML_WC_Strings::class )
		                          ->disableOriginalConstructor()
		                          ->setMethods( [ 'get_translated_string_by_name_and_context' ] )
		                          ->getMock();
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		$subject = $this->get_subject();

		WP_Mock::expectActionAdded( 'woocommerce_tax_rate_added', array( $subject, 'register_tax_rate_label_string'), 10, 2 );
		WP_Mock::expectActionAdded( 'wp_ajax_woocommerce_shipping_zone_methods_save_settings', array( $subject, 'save_shipping_zone_method_from_ajax'), 9 );
		WP_Mock::expectActionAdded( 'icl_save_term_translation', array( $subject, 'sync_class_costs_for_new_shipping_classes'), 100, 2 );
		WP_Mock::expectActionAdded( 'wp_ajax_woocommerce_shipping_zone_methods_save_settings', array( $subject, 'update_woocommerce_shipping_settings_for_class_costs_from_ajax'), 9 );

		WP_Mock::expectFilterAdded( 'woocommerce_package_rates', array( $subject, 'translate_shipping_methods_in_package' ) );
		WP_Mock::expectFilterAdded( 'woocommerce_rate_label', array( $subject, 'translate_woocommerce_rate_label' ) );
		WP_Mock::expectFilterAdded( 'pre_update_option_woocommerce_flat_rate_settings', array( $subject, 'update_woocommerce_shipping_settings_for_class_costs' ) );
		WP_Mock::expectFilterAdded( 'pre_update_option_woocommerce_international_delivery_settings', array( $subject, 'update_woocommerce_shipping_settings_for_class_costs' ) );
		WP_Mock::expectFilterAdded( 'woocommerce_shipping_flat_rate_instance_option', array( $subject, 'get_original_shipping_class_rate' ), 10, 3 );

		$wc = $this->getMockBuilder( 'WooCommerce' )->disableOriginalConstructor()->getMock();

		$wc->shipping = $this->getMockBuilder( 'WC_Shipping' )->setMethods( array( 'get_shipping_methods' ) )->disableOriginalConstructor()->getMock();

		$shipping_method = new stdClass();
		$shipping_method->id = 1;
		$wc->shipping->expects( $this->once() )
		                     ->method( 'get_shipping_methods' )
		                     ->willReturn( array( $shipping_method ) );

		WP_Mock::userFunction( 'WC', array( 'return' => $wc ) );

		WP_Mock::expectFilterAdded( 'woocommerce_shipping_' . $shipping_method->id . '_instance_settings_values', array( $subject, 'register_zone_shipping_strings' ), 9, 2 );
		WP_Mock::expectFilterAdded( 'option_woocommerce_' . $shipping_method->id . '_settings', array( $subject, 'translate_shipping_strings' ), 9, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider get_shipping_method_title
	 */
	public function translate_shipping_method_title( $title, $translated_title, $language ){

		$shipping_id = rand_str();

		$sitepress = $this->getMockBuilder( 'SitePress' )
			->disableOriginalConstructor()
			->setMethods( array(
				'get_current_language'
			) )
			->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $language );

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$wcml_strings = $this->get_wcml_strings_mock();

		$wcml_strings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_shipping', $shipping_id .'_shipping_method_title', $language )->willReturn( $translated_title );

		$subject = $this->get_subject( $sitepress, $wcml_strings );

		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $translated_title, $filtered_title );
	}

	public function get_shipping_method_title(){
		return [
			[ 'title origin', 'title origin', 'en' ],
			[ 'title origin', 'title DE', 'de' ],
		];
	}

	/**
	 * @test
	 * @dataProvider get_shipping_method_title
	 */
	public function translate_shipping_method_title_on_admin( $title, $translated_title, $language ){

		$shipping_id      = rand_str();

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'get_current_language'
		                  ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $language );

		$wcml_strings = $this->get_wcml_strings_mock();

		$wcml_strings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_shipping', $shipping_id .'_shipping_method_title', $language )->willReturn( $translated_title );

		$subject = $this->get_subject( $sitepress, $wcml_strings );

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		\WP_Mock::wpFunction( 'did_action', array(
			'args' => array( 'admin_init' ),
			'return' => true
		) );

		\WP_Mock::wpFunction( 'did_action', array(
			'args' => array( 'current_screen' ),
			'return' => true
		) );

		$screen = $this->getMockBuilder( 'WP_Screen' )->disableOriginalConstructor()->getMock();
		\WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $screen ) );

		$screen->id = 'NOT_shop_order';
		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $title, $filtered_title );

		$screen->id = 'shop_order';
		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $translated_title, $filtered_title );

	}

	/**
	 * @test
	 * @group wcml-2061
	 */
	public function translate_shipping_method_title_on_admin_before_admin_init(){

		$title            = rand_str();
		$translated_title = rand_str();
		$shipping_id      = rand_str();
		$language         = 'en';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'get_current_language'
		                  ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $language );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $title, 'woocommerce', $shipping_id .'_shipping_method_title', $language )
		       ->reply( $translated_title );

		$subject = $this->get_subject( $sitepress );


		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'did_action', array(
			'times' => 1,
			'args' => array( 'admin_init' ),
			'return' => false
		) );

		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $title, $filtered_title );
	}

	/**
	 * @test
	 */
	public function it_should_get_original_shipping_class_rate_if_current_not_set(){

		$rate = '';
		$language = 'es';
		$current_class_id = 22;
		$original_class_id = 21;
		$class_name = 'class_cost_'.$current_class_id;
		$shipping_method = $this->getMockBuilder( 'WC_Shipping_Method' )->disableOriginalConstructor()->getMock();
		$shipping_method->instance_settings = array();
		$shipping_method->instance_settings['class_cost_'.$original_class_id] = 100;

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'term_translations',
			                  'get_current_language'
		                  ) )
		                  ->getMock();
		$term_translations = $this->getMockBuilder( 'WPML_Term_Translations' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'get_original_element'
		                  ) )
		                  ->getMock();

		$sitepress->method( 'term_translations' )->willReturn( $term_translations );
		$sitepress->method( 'get_current_language' )->willReturn( $language );
		$term_translations->method( 'get_original_element' )->with( $current_class_id )->willReturn( $original_class_id );

		$subject = $this->get_subject( $sitepress );

		$filtered_rate = $subject->get_original_shipping_class_rate( $rate, $class_name, $shipping_method );

		$this->assertEquals( $shipping_method->instance_settings['class_cost_'.$original_class_id], $filtered_rate );

	}

	/**
	 * @test
	 */
	public function it_should_get_current_shipping_class_rate_if_set(){

		$rate = '110';
		$shipping_method = $this->getMockBuilder( 'WC_Shipping_Method' )->disableOriginalConstructor()->getMock();

		$subject = $this->get_subject();

		$filtered_rate = $subject->get_original_shipping_class_rate( $rate, 'class_cost_23', $shipping_method );

		$this->assertEquals( $rate, $filtered_rate );
	}

	/**
	 * @test
	 */
	public function it_should_get_current_rate_if_not_class_name_passed(){

		$rate = '150';
		$shipping_method = $this->getMockBuilder( 'WC_Shipping_Method' )->disableOriginalConstructor()->getMock();

		$subject = $this->get_subject();

		$filtered_rate = $subject->get_original_shipping_class_rate( $rate, 'cost', $shipping_method );

		$this->assertEquals( $rate, $filtered_rate );
	}
}
