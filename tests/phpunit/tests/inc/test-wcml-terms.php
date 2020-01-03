<?php

class Test_WCML_Terms extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	function setUp(){
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();
	}

	/**
	 * @return woocommerce_wpml
	 */
	public function get_woocommerce_wpml(){

		return $this->getMockBuilder('woocommerce_wpml')
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return SitePress
	 */
	public function get_sitepress() {

		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return WCML_Terms
	 */
	private function get_subject( $woocommerce_wpml = null, $sitepress = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}



		return new WCML_Terms( $woocommerce_wpml, $sitepress , $this->wpdb );
	}

	/**
	 * @test
	 */
	function it_should_add_hooks(){

		WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'update_term_meta', [ $subject, 'update_category_count_meta'], 10 ,4 );
		\WP_Mock::expectFilterAdded( 'woocommerce_get_product_subcategories_cache_key', [ $subject, 'add_lang_parameter_to_cache_key'] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	function is_translatable_wc_taxonomy(){

		$subject = $this->get_subject();
		$taxonomy = rand_str();

		$this->assertTrue( $subject->is_translatable_wc_taxonomy( $taxonomy ) );

		$taxonomy = 'product_type';
		$this->assertFalse( $subject->is_translatable_wc_taxonomy( $taxonomy ) );

	}

	/**
	 * @test
	 */
	function it_should_return_default_product_cat_id_in_current_language() {

		$product_language           = 'es';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_default_language' ) )
		                  ->getMock();

		$sitepress->method( 'get_current_language' )->willReturn( $product_language );
		$sitepress->method( 'get_default_language' )->willReturn( 'en' );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_settings' ) )
		                         ->getMock();

		$category_id                                              = mt_rand( 1, 10 );
		$wcml_settings['default_categories'][ $product_language ] = $category_id;

		$woocommerce_wpml->method( 'get_settings' )->willReturn( $wcml_settings );

		$this->wpdb->method( 'get_var' )->willReturn( $category_id );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$filtered_cat_id = $subject->pre_option_default_product_cat();

		$this->assertEquals( $category_id, $filtered_cat_id );
	}

	/**
	 * @test
	 */
	function it_should_update_default_product_cat_ids_for_all_languages() {

		$oldvalue                          = mt_rand( 1, 10 );
		$new_value                         = mt_rand( 11, 20 );
		$trid                              = mt_rand( 21, 30 );
		$translations                      = array();
		$translations['en']                = new StdClass();
		$translations['es']                = new StdClass();
		$translations['en']->language_code = 'en';
		$translations['en']->element_id    = mt_rand( 31, 40 );
		$translations['es']->language_code = 'es';
		$translations['es']->element_id    = mt_rand( 41, 50 );

		$this->wpdb->method( 'get_var' )->willReturn( $new_value );

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_trid', 'get_element_translations' ) )
		                  ->getMock();

		$sitepress->method( 'get_element_trid' )->with( $new_value, 'tax_product_cat' )->willReturn( $trid );
		$sitepress->method( 'get_element_translations' )->with( $trid )->willReturn( $translations );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_settings', 'update_settings' ) )
		                         ->getMock();

		$category_id                         = mt_rand( 1, 10 );
		$wcml_settings['default_categories'] = array(
			'en' => $translations['en']->element_id,
			'es' => $translations['es']->element_id
		);

		$updated_wcml_settings['default_categories'] = array();
		$woocommerce_wpml->method( 'get_settings' )->willReturn( $wcml_settings );
		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' )->with()->willReturn( $wcml_settings );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$filtered_settings = $subject->update_option_default_product_cat( $oldvalue, $new_value );


	}

	/**
	 * @test
	 */
	function it_should_update_category_count_meta() {
		$meta_id = 1;
		$object_id = 10;
		$trid = 11;
		$meta_key = 'product_count_product_cat';
		$meta_value = 2;

		$translations                      = array();
		$translations['en']                = new StdClass();
		$translations['es']                = new StdClass();
		$translations['en']->language_code = 'en';
		$translations['en']->element_id    = $object_id;
		$translations['es']->language_code = 'es';
		$translations['es']->element_id    = 20;

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_trid', 'get_element_translations' ) )
		                  ->getMock();

		$sitepress->method( 'get_element_trid' )->with( $object_id, 'tax_product_cat' )->willReturn( $trid );
		$sitepress->method( 'get_element_translations' )->with( $trid )->willReturn( $translations );

		$subject = $this->get_subject( null, $sitepress );

		WP_Mock::passthruFunction( 'remove_action' );

		\WP_Mock::userFunction( 'update_term_meta', array(
			'args' => array( $translations['es']->element_id, $meta_key, $meta_value ),
			'times' => 1,
		));

		$subject->update_category_count_meta( $meta_id, $object_id, $meta_key, $meta_value );

	}

	/**
	 * @test
	 */
	public function it_should_filter_shipping_classes_terms_in_default_language_on_shipping_settings_page() {

		WP_Mock::passthruFunction( 'remove_filter' );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'shipping';

		$taxonomies = [ 'product_shipping_class' ];
		$args       = [ 'taxonomy' => $taxonomies ];

		$original_term_object          = new stdClass();
		$original_term_object->term_id = 10;

		$expected_terms = [ $original_term_object ];

		WP_Mock::userFunction( 'is_admin', [
			'return' => true
		] );

		WP_Mock::userFunction( 'get_terms', [
			'args'   => [ $args ],
			'return' => $expected_terms
		] );

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'switch_lang', 'get_default_language' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_default_language' )->willReturn( 'en' );
		$sitepress->expects( $this->exactly( 2 ) )->method( 'switch_lang' )->willReturn( true );

		$subject = $this->get_subject( null, $sitepress );

		$filtered_terms = $subject->filter_shipping_classes_terms( [], $taxonomies, $args );

		$this->assertEquals( $expected_terms, $filtered_terms );

		unset( $_GET['page'], $_GET['tab'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_shipping_classes_terms_for_not_shipping_settings_page() {

		WP_Mock::passthruFunction( 'remove_filter' );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		WP_Mock::userFunction( 'is_admin', [
			'return' => true
		] );

		$this->not_filtered_shipping_classes_terms_mock();

		unset( $_GET['page'], $_GET['tab'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_shipping_classes_terms_for_front_page() {

		WP_Mock::passthruFunction( 'remove_filter' );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'shipping';

		WP_Mock::userFunction( 'is_admin', [
			'return' => false
		] );

		$this->not_filtered_shipping_classes_terms_mock();

		unset( $_GET['page'], $_GET['tab'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_shipping_classes_terms_for_not_settings_page() {

		WP_Mock::passthruFunction( 'remove_filter' );

		$_GET['page'] = 'products';

		WP_Mock::userFunction( 'is_admin', [
			'return' => true
		] );

		$this->not_filtered_shipping_classes_terms_mock();

		unset( $_GET['page'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_shipping_classes_terms_if_taxonomies_is_null() {

		$subject = $this->get_subject();

		$filtered_terms = $subject->filter_shipping_classes_terms( [], null, [] );

		$this->assertEquals( [], $filtered_terms );
	}


	private function not_filtered_shipping_classes_terms_mock(){
		$taxonomies = [ 'product_shipping_class' ];
		$args       = [ 'taxonomy' => $taxonomies ];

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'switch_lang', 'get_default_language' ) )
		                  ->getMock();

		$sitepress->expects( $this->exactly( 0 ) )->method( 'get_default_language' )->willReturn( 'en' );
		$sitepress->expects( $this->exactly( 0 ) )->method( 'switch_lang' )->willReturn( true );

		$subject = $this->get_subject( null, $sitepress );

		$filtered_terms = $subject->filter_shipping_classes_terms( [], $taxonomies, $args );

		$this->assertEquals( [], $filtered_terms );
	}

	/**
	 * @test
	 */
	public function it_should_add_current_language_parameter_to_cache_key() {

		$cache_key = rand_str();
		$current_language = 'es';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( ['get_current_language'] )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		$this->assertEquals( $cache_key.'-'.$current_language, $subject->add_lang_parameter_to_cache_key( $cache_key ) );
	}

}