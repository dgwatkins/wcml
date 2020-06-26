<?php
/**
 * Class Test_WCML_Attributes
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group wcml-attributes
 */
class Test_WCML_Attributes extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Post_Translation */
	private $post_translations;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
		    ->getMock();

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_product_display_as_translated_post_type' ) )
		                                         ->getMock();

		$this->post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_source_lang_code', 'get_element_translations', 'element_id_in' ) )
			->getMock();

		$this->wpml_term_translations = $this->getMockBuilder( 'WPML_Term_Translation' )
			->disableOriginalConstructor()
			->setMethods( array( ) )
			->getMock();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language', 'get_setting', 'set_setting', 'verify_taxonomy_translations', 'switch_lang' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->wpdb = $this->stubs->wpdb();
	}


	/**
	 * @return WCML_Attributes
	 */
	private function get_subject(){
		$subject = new WCML_Attributes( $this->woocommerce_wpml, $this->sitepress, $this->post_translations, $this->wpml_term_translations, $this->wpdb );

		return $subject;
	}

	/**
	 * @test
	 */
	public function add_hooks()
	{
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_product_get_attributes', [ $subject, 'filter_adding_to_cart_product_attributes_names' ] );
		\WP_Mock::expectFilterAdded( 'wpml_tm_job_field_is_translatable', [ $subject, 'set_custom_product_attributes_as_translatable_for_tm_job' ], 10, 2 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_on_init_hooks_for_attribute_page()
	{
		$_GET[ 'page' ] = 'product_attributes';
		$_GET[ 'post_type' ] = 'product';

		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'admin_init', array( $subject, 'not_translatable_html' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_attribute_added', array( $subject, 'set_attribute_readonly_config' ), $subject::PRIORITY_AFTER_WC_INIT, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_attribute_updated', array( $subject, 'set_attribute_readonly_config' ), $subject::PRIORITY_AFTER_WC_INIT, 3 );
		$subject->init();
	}

	/**
	 * @test
	 */
	public function hooks_for_product_display_as_translated_post_type()
	{
		$this->woocommerce_wpml->products->method( 'is_product_display_as_translated_post_type' )->willReturn( true );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_available_variation', array( $subject, 'filter_available_variation_attribute_values_in_current_language' ) );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'filter_product_variation_post_meta_attribute_values_in_current_language' ), 10 ,4 );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_get_default_attributes', array( $subject, 'filter_product_variation_default_attributes' ) );
		\WP_Mock::expectActionAdded( 'update_post_meta', array( $subject, 'set_translation_status_as_needs_update' ), 10, 3 );
		\WP_Mock::expectActionAdded( 'wc_ajax_get_variation', array( $subject, 'maybe_filter_get_variation' ), 9 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function is_a_taxonomy(){

		$attribute = $this->getMockBuilder( 'WC_Product_Attribute' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_taxonomy' ) )
			->getMock();

		$attribute->method( 'is_taxonomy' )->willReturn( true );

		$subject = $this->get_subject();
		$attribute_is_taxonomy = $subject->is_a_taxonomy( $attribute );

		$this->assertTrue( $attribute_is_taxonomy );

		$attribute = array();
		$attribute[ 'is_taxonomy' ] = true;

		$subject = $this->get_subject();
		$attribute_is_taxonomy = $subject->is_a_taxonomy( $attribute );

		$this->assertTrue( $attribute_is_taxonomy );


		$attribute = array();
		$attribute[ 'is_taxonomy' ] = false;

		$subject = $this->get_subject();
		$attribute_is_taxonomy = $subject->is_a_taxonomy( $attribute );

		$this->assertFalse( $attribute_is_taxonomy );
	}

	/**
	 * @test
	 */
	public function sync_product_attr_test_empty_translated_attributes_array(){

		$product_id = rand( 1, 100 );
		$translated_product_id = rand( 1, 100 );
		$orig_lang = 'en';

		$original_attributes = array( array(
			'name' => rand_str(),
			'value' => rand_str(),
			'is_taxonomy' => 0
		) );

		$translated_attributes = array();

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $product_id, '_product_attributes', true ),
			'times' => 1,
			'return' => $original_attributes
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, '_product_attributes', true ),
			'times' => 1,
			'return' => $translated_attributes
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, '_icl_lang_duplicate_of', true ),
			'times' => 1,
			'return' => false
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, 'attr_label_translations', true ),
			'times' => 1,
			'return' => false
		) );


		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, 'attr_label_translations', array() ),
			'times'  => 1,
			'return' => true
		));

//		 The $original_attributes should be saved to the translated product if is_taxonomy = 0 and there are no existing $translated_attributes
		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_product_attributes', $original_attributes ),
			'times'  => 1,
			'return' => true
		));

		\WP_Mock::wpPassthruFunction( 'sanitize_title' );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$subject = $this->get_subject();

		$subject->sync_product_attr( $product_id, $translated_product_id );
	}

	/**
	 * @test
	 */
	public function sync_product_attr_for_duplicated_product(){

		$product_id = rand( 1, 100 );
		$translated_product_id = rand( 1, 100 );
		$orig_lang = 'en';

		$original_attributes = array( array(
			'name' => rand_str(),
			'value' => rand_str(),
			'is_taxonomy' => 0
		) );

		$translated_attributes = array(
			array(
				'name' => rand_str(),
				'value' => rand_str(),
				'is_taxonomy' => 0
			)
		);

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $product_id, '_product_attributes', true ),
			'return' => $original_attributes
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, '_product_attributes', true ),
			'return' => $translated_attributes
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, 'attr_label_translations', true ),
			'return' => false
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, '_icl_lang_duplicate_of', true ),
			'return' => $product_id
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, 'attr_label_translations', array() ),
			'times'  => 1,
			'return' => true
		));

		//The $original_attributes should be duplicated to the translated product if is_taxonomy = 0 and translation is a duplication
		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_product_attributes', $original_attributes ),
			'times'  => 1,
			'return' => true
		));

		\WP_Mock::wpPassthruFunction( 'sanitize_title' );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$subject = $this->get_subject();

		$subject->sync_product_attr( $product_id, $translated_product_id );
	}

	/**
	 * @test
	 */
	public function filter_attribute_name() {

		$attribute_name           = rand_str();
		$sanitized_attribute_name = rand_str();
		$orig_lang                = 'de';
		$product_id               = rand( 1, 100 );
		$current_language         = 'fr';

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress->locale_utils = $this->getMockBuilder( 'WPML_Locale' )
		                                      ->disableOriginalConstructor()
		                                      ->setMethods( array( 'filter_sanitize_title' ) )
		                                      ->getMock();

		$this->sitepress->locale_utils
			->method( 'filter_sanitize_title' )
			->with( $attribute_name, $attribute_name )
			->willReturn( $attribute_name );

		$this->sitepress
			->method( 'get_current_language' )
			->willReturn( $current_language );

		\WP_Mock::wpFunction( 'remove_accents', array(
			'args'   => array( $attribute_name ),
			'times'  => 1,
			'return' => $attribute_name
		) );

		\WP_Mock::wpFunction( 'sanitize_title', array(
			'args'   => array( $attribute_name ),
			'times'  => 1,
			'return' => $sanitized_attribute_name
		) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times'  => 1,
			'return' => true
		) );

		$subject = $this->get_subject();

		$filtered_attribute_name = $subject->filter_attribute_name( $attribute_name, $product_id, true );

		$this->assertEquals( $sanitized_attribute_name, $filtered_attribute_name );
	}

	/**
	 * @test
	 */
	public function filter_attribute_name_current_is_original() {

		$attribute_name           = rand_str();
		$orig_lang                = 'de';
		$product_id               = rand( 1, 100 );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress
			->method( 'get_current_language' )
			->willReturn( $orig_lang );

		$subject = $this->get_subject();

		$filtered_attribute_name = $subject->filter_attribute_name( $attribute_name, $product_id, false );

		$this->assertEquals( $attribute_name, $filtered_attribute_name );
	}

	/**
	 * @test
	 */
	public function filter_attribute_name_current_is_DE() {

		\WP_Mock::passthruFunction( 'remove_filter' );

		$attribute_name           = 'öööfff';
		$expected_attribute_name  = 'ooofff';
		$orig_lang                = 'sv';
		$product_id               = 11;

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress->locale_utils = $this->getMockBuilder( 'WPML_Locale' )
		                                      ->disableOriginalConstructor()
		                                      ->getMock();

		$this->sitepress
			->method( 'get_current_language' )
			->willReturn( 'de' );

		$this->sitepress
			->expects( $this->exactly( 2 ) )
			->method( 'switch_lang' )
			->willReturn( true );

		\WP_Mock::userFunction( 'sanitize_title', [
			'args'   => [ $attribute_name ],
			'return' => $expected_attribute_name
		] );

		$subject = $this->get_subject();

		$filtered_attribute_name = $subject->filter_attribute_name( $attribute_name, $product_id, true );

		$this->assertEquals( $expected_attribute_name, $filtered_attribute_name );
	}

	/**
	 * @test
	 */
	public function it_filter_product_attributes_names_when_adding_to_cart() {

		$product_id              = rand( 1, 100 );
		$orig_lang               = 'de';
		$_REQUEST['add-to-cart'] = $product_id;

		$attributes = array(
			array(
				'name' => 'test1'
			)
		);

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->expects( $this->once() )
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress
			->expects( $this->once() )
			->method( 'get_current_language' )
			->willReturn( $orig_lang );

		$subject = $this->get_subject();

		$filtered_attributes = $subject->filter_adding_to_cart_product_attributes_names( $attributes, $product_id, false );

		$this->assertEquals( $attributes, $filtered_attributes );

		unset( $_REQUEST['add-to-cart'] );
	}


	/**
	 * @test
	 */
	public function it_filter_product_attributes_names_when_selecting_attribute() {

		$product_id             = rand( 1, 100 );
		$orig_lang              = 'de';
		$_REQUEST['wc-ajax']    = 'get_variation';
		$_REQUEST['product_id'] = $product_id;

		$attributes = array(
			array(
				'name' => 'test1'
			)
		);

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->expects( $this->once() )
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress
			->expects( $this->once() )
			->method( 'get_current_language' )
			->willReturn( $orig_lang );

		$subject = $this->get_subject();

		$filtered_attributes = $subject->filter_adding_to_cart_product_attributes_names( $attributes, $product_id, false );

		$this->assertEquals( $attributes, $filtered_attributes );

		unset( $_REQUEST['wc-ajax'] );
		unset( $_REQUEST['product_id'] );
	}

	/**
	 * @test
	 */
	public function sync_default_product_attr_for_custom_attributes_should_sync_by_insert() {
		$subject = $this->get_subject();

		$original_product_id   = 27222;
		$translated_product_id = 1919191;
		$lang                  = 'ru';

		$original_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small | medium | big'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white|black'
			]
		];

		$original_default_attributes = [
			'size'  => 'medium',
			'color' => 'black'
		];

		$translated_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small-translated | medium-translated | big-translated'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white-translated|black-translated'
			]
		];
		// default attribute not set
		$translated_product_meta = [
			'_product_attributes' => $translated_product_attributes
		];

		$expected_translated_default_attributes = [
			'size'  => 'medium-translated',
			'color' => 'black-translated'
		];


		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_default_attributes', true ],
			'return' => $original_default_attributes
		] );

		\WP_Mock::passthruFunction( 'maybe_unserialize' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_product_attributes', true ],
			'return' => $original_product_attributes
		] );

		\WP_Mock::passthruFunction( 'sanitize_title' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id, '_product_attributes', true ],
			'return' => $translated_product_attributes
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id ],
			'return' => $translated_product_meta
		] );

		\WP_Mock::passthruFunction( 'maybe_serialize' );

		$insert_data = [
			'post_id'    => $translated_product_id,
			'meta_key'   => '_default_attributes',
			'meta_value' => $expected_translated_default_attributes
		];

		$this->wpdb->expects( $this->once() )
		           ->method( 'insert' )
		           ->with( $this->wpdb->postmeta, $insert_data );

		$subject->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );

	}

	/**
	 * @test
	 */
	public function sync_default_product_attr_for_custom_attributes_should_sync_by_update() {
		$subject = $this->get_subject();

		$original_product_id   = 27222;
		$translated_product_id = 1919191;
		$lang                  = 'ru';

		$original_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small | medium | big'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white|black'
			]
		];

		$original_default_attributes = [
			'size'  => 'medium',
			'color' => 'black'
		];

		$translated_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small-translated | medium-translated | big-translated'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white-translated|black-translated'
			]
		];
		// default attribute ARE set
		$translated_product_meta = [
			'_product_attributes' => $translated_product_attributes,
			'_default_attributes' => [ 'size' => 'small-translated', 'color' => 'white-translated' ]
		];

		$expected_translated_default_attributes = [
			'size'  => 'medium-translated',
			'color' => 'black-translated'
		];


		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_default_attributes', true ],
			'return' => $original_default_attributes
		] );

		\WP_Mock::passthruFunction( 'maybe_unserialize' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_product_attributes', true ],
			'return' => $original_product_attributes
		] );

		\WP_Mock::passthruFunction( 'sanitize_title' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id, '_product_attributes', true ],
			'return' => $translated_product_attributes
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id ],
			'return' => $translated_product_meta
		] );

		\WP_Mock::passthruFunction( 'maybe_serialize' );

		$update_data = [
			'meta_value' => $expected_translated_default_attributes
		];
		$update_where = [
			'post_id'    => $translated_product_id,
			'meta_key'   => '_default_attributes'
		];

		$this->wpdb->expects( $this->once() )
		           ->method( 'update' )
		           ->with( $this->wpdb->postmeta, $update_data, $update_where );

		$subject->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );

	}

	/**
	 * @test
	 */
	public function sync_default_product_attr_taxonomy_with_no_latin_letters() {

		\WP_Mock::passthruFunction( 'maybe_unserialize' );
		\WP_Mock::passthruFunction( 'maybe_serialize' );
		\WP_Mock::passthruFunction( 'sanitize_title' );

		$original_product_id   = 2;
		$translated_product_id = 3;
		$lang = 'en';
		$attribute_name = 'pa_%d1%80%d0%be%d0%b7%d0%bc%d1%96%d1%80';
		$sanitized_attribute_name = 'pa_колір';
		$default_term_slug = '%d0%b2%d0%b5%d0%bb%d0%b8%d0%ba%d0%b8%d0%b9';
		$default_term_id = 10;
		$translated_term = new stdClass();
		$translated_term->term_id = 20;
		$translated_term->slug = 'big';

		$original_default_attributes = array(
			$attribute_name => $default_term_slug
		);

		$expected_translated_default_attributes = array(
			$attribute_name => $translated_term->slug
		);

		$this->woocommerce_wpml->terms = $this->getMockBuilder( 'WCML_Terms' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'update_terms_translated_status', 'wcml_get_term_id_by_slug', 'wcml_get_term_by_id' ) )
		                                         ->getMock();
		$this->woocommerce_wpml->terms->method( 'wcml_get_term_id_by_slug' )->with( $sanitized_attribute_name, $default_term_slug )->willReturn( $default_term_id );
		$this->woocommerce_wpml->terms->method( 'wcml_get_term_by_id' )->with( $translated_term->term_id, $sanitized_attribute_name )->willReturn( $translated_term );

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $default_term_id, $sanitized_attribute_name, false, $lang )
		        ->reply( $translated_term->term_id );

		\WP_Mock::userFunction( 'wc_sanitize_taxonomy_name', array(
			'args'   => array( $attribute_name ),
			'return' => $sanitized_attribute_name
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $original_product_id, '_default_attributes', true ),
			'return' => $original_default_attributes
		) );

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $translated_product_id ),
			'return' => array()
		) );

		$insert_data = array(
			'post_id'    => $translated_product_id,
			'meta_key'   => '_default_attributes',
			'meta_value' => $expected_translated_default_attributes
		);

		$this->wpdb->expects( $this->once() )
		           ->method( 'insert' )
		           ->with( $this->wpdb->postmeta, $insert_data );

		$subject->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );
	}

	/**
	 * @test
	 */
	public function it_does_filter_available_variation_attribute_values_in_current_language() {

		$attribute_taxonomy = rand_str( 10 );
		$attribute_key = 'attribute_'.$attribute_taxonomy;
		$attribute_value = rand_str( 12 );
		$translated_attribute_value = rand_str( 15 );

		$args['attributes'] = array( $attribute_key => $attribute_value );

		$term = new stdClass();
		$term->slug = $translated_attribute_value;

		\WP_Mock::wpFunction( 'get_term_by', array(
			'args'  => array( 'slug', $attribute_value, $attribute_taxonomy ),
			'return' => $term
		) );

		\WP_Mock::wpFunction( 'taxonomy_exists', array(
			'args'  => array( $attribute_taxonomy ),
			'return' => true
		) );

		$subject = $this->get_subject();

		$filter_attribute = $subject->filter_available_variation_attribute_values_in_current_language( $args );

		$this->assertEquals( $filter_attribute[ 'attributes' ][ $attribute_key ], $translated_attribute_value );
	}



	/**
	 * @test
	 * @group wcml-2517
	 */
	public function it_does_filter_product_variation_post_meta_attribute_values_in_current_language_when_post_type_is_product_variation() {
		$current_lang = 'fr';

		$this->sitepress->method( 'get_current_language' )->willReturn( $current_lang );
		$subject = $this->get_subject();

		$object_id = mt_rand( 1, 10 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $object_id ),
			'return' => 'product_variation'
		) );

		$attribute_taxonomy = rand_str( 10 );
		$attribute_key = 'attribute_'.$attribute_taxonomy;
		$attribute_value = rand_str( 12 );
		$translated_attribute_value = rand_str( 15 );

		$all_meta[ $attribute_key ] = array( $attribute_value );
		$translated_all_meta = $all_meta;
		$translated_all_meta[ $attribute_key ] = array( $translated_attribute_value );

		\WP_Mock::userFunction( 'wp_cache_get', array(
			'return' => false,
		));

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $object_id ),
			'return' => $all_meta
		) );

		\WP_Mock::userFunction( 'remove_filter', array(
			'times' => 1,
			'args'  => array(
				'get_post_metadata',
				array( $subject, 'filter_product_variation_post_meta_attribute_values_in_current_language' ),
				10,
			),
		));

		\WP_Mock::expectFilterAdded(
			'get_post_metadata',
			array( $subject, 'filter_product_variation_post_meta_attribute_values_in_current_language' ),
			10,
			4
		);

		$term = new stdClass();
		$term->slug = $translated_attribute_value;

		\WP_Mock::userFunction( 'get_term_by', array(
			'args'  => array( 'slug', $attribute_value, $attribute_taxonomy ),
			'return' => $term
		) );

		\WP_Mock::userFunction( 'taxonomy_exists', array(
			'args'  => array( $attribute_taxonomy ),
			'return' => true
		) );

		\WP_Mock::userFunction( 'wp_cache_add', array(
			'args' => array( $current_lang . $object_id, $translated_all_meta, 'wpml-all-meta-product-variation' ),
		));

		$filter_attribute = $subject->filter_product_variation_post_meta_attribute_values_in_current_language( null, $object_id, '', false );

		$this->assertEquals( $filter_attribute[ $attribute_key ][ 0 ], $translated_attribute_value );
	}

	/**
	 * @test
	 * @group wcml-2517
	 */
	public function it_does_filter_product_variation_post_meta_attribute_values_in_current_language_from_cache() {
		$current_lang = 'fr';

		$this->sitepress->method( 'get_current_language' )->willReturn( $current_lang );
		$subject = $this->get_subject();

		$object_id = mt_rand( 1, 10 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $object_id ),
			'return' => 'product_variation'
		) );

		$attribute_taxonomy = rand_str( 10 );
		$attribute_key = 'attribute_'.$attribute_taxonomy;
		$attribute_value = rand_str( 12 );
		$translated_attribute_value = rand_str( 15 );

		$all_meta[ $attribute_key ] = array( $attribute_value );
		$translated_all_meta = $all_meta;
		$translated_all_meta[ $attribute_key ] = array( $translated_attribute_value );

		\WP_Mock::userFunction( 'wp_cache_get', array(
			'args'   => array( $current_lang . $object_id, 'wpml-all-meta-product-variation' ),
			'return' => $translated_all_meta,
		));

		\WP_Mock::userFunction( 'get_post_meta', array(
			'time' => 0
		) );

		\WP_Mock::userFunction( 'wp_cache_add', array(
			'times' => 0,
		));

		$filter_attribute = $subject->filter_product_variation_post_meta_attribute_values_in_current_language( null, $object_id, '', false );

		$this->assertEquals( $filter_attribute[ $attribute_key ][ 0 ], $translated_attribute_value );
	}

	/**
	 * @test
	 */
	public function does_not_filter_product_variation_post_meta_attribute_values_in_current_language_when_post_type_is_not_product_variation() {

		$object_id = mt_rand( 1, 10 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $object_id ),
			'return' => rand_str()
		) );

		$subject = $this->get_subject();

		$filter_attribute = $subject->filter_product_variation_post_meta_attribute_values_in_current_language( null, $object_id, '', false );

		$this->assertNull( $filter_attribute );
	}


	/**
	 * @test
	 */
	public function does_not_filter_product_variation_post_meta_attribute_values_in_current_language_when_attribute_is_not_taxonomy() {

		$attribute_taxonomy = rand_str( 10 );
		$attribute_key = 'attribute_'.$attribute_taxonomy;
		$attribute_value = rand_str( 12 );

		$args['attributes'] = array( $attribute_key => $attribute_value );

		\WP_Mock::wpFunction( 'taxonomy_exists', array(
			'args'  => array( $attribute_taxonomy ),
			'return' => false
		) );

		$subject = $this->get_subject();

		$filter_attribute = $subject->filter_available_variation_attribute_values_in_current_language( $args );

		$this->assertEquals( $filter_attribute[ 'attributes' ][ $attribute_key ], $attribute_value );
	}


	/**
	 * @test
	 */
	public function it_does_filter_product_variation_default_attributes() {

		$object_id = mt_rand( 1, 10 );

		$attribute_taxonomy = rand_str( 10 );
		$attribute_value = rand_str( 12 );

		$default_attributes = array(
			$attribute_taxonomy => $attribute_value
		);

		$translated_attribute_value = rand_str( 15 );

		$term = new stdClass();
		$term->slug = $translated_attribute_value;

		\WP_Mock::wpFunction( 'get_term_by', array(
			'args'  => array( 'slug', $attribute_value, $attribute_taxonomy ),
			'return' => $term
		) );

		\WP_Mock::wpFunction( 'taxonomy_exists', array(
			'args'  => array( $attribute_taxonomy ),
			'return' => true
		) );

		$subject = $this->get_subject();

		$expected_default_attributes = array(
			$attribute_taxonomy => $translated_attribute_value
		);

		$filtered_default_attributes = $subject->filter_product_variation_default_attributes( $default_attributes );

		$this->assertEquals( $expected_default_attributes, $filtered_default_attributes );
	}

	/**
	 * @test
	 * @group wcml-2551
	 */
	public function it_sets_translation_status_as_needs_update() {
		$default_language = 'en';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->setMethods( array( 'get_default_language' ) )
		                  ->disableOriginalConstructor()
		                  ->getMock();

		$sitepress->method( 'get_default_language' )
		          ->willReturn( $default_language );

		$this->product_id     = 2;
		$translation_id = 3;
		$meta_key       = '_product_attributes';
		$this->lang_code      = 'pt';

		$translations = array(
			$default_language => $this->product_id,
			$this->lang_code => $translation_id
		);

		$that = $this;
		$this->post_translations->method( 'get_source_lang_code' )->willReturnCallback( function ( $id ) use ( $that ) {
			if ( $this->product_id == $id ) {
				return null;
			} else {
				return $this->lang_code;
			}
		} );

		$this->post_translations->method( 'get_element_translations' )->with( $this->product_id )->willReturn( $translations );

		$subject        = new WCML_Attributes( $this->woocommerce_wpml, $sitepress, $this->post_translations, $this->wpml_term_translations, $this->wpdb );

		$status_helper = $this->getMockBuilder( 'WPML_Post_Status' )
		                      ->setMethods( array( 'set_update_status' ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$status_helper->expects( $this->once() )
		              ->method( 'set_update_status' )
		              ->with( $translation_id, 1 );

		\WP_Mock::userFunction( 'wpml_get_post_status_helper', array(
			'return' => $status_helper
		) );

		$subject->set_translation_status_as_needs_update( 1, $this->product_id, $meta_key );
	}

	/**
	 * @test
	 * @group wcml-2551
	 */
	public function it_should_not_set_translation_status_as_needs_update_when_meta_field_is_not_product_attributes() {
		$subject    = new WCML_Attributes( $this->woocommerce_wpml, $this->sitepress, $this->post_translations, $this->wpml_term_translations, $this->wpdb );
		$product_id = 2;
		$meta_key   = 'any_other_meta_field';

		$status_helper = $this->getMockBuilder( 'WPML_Post_Status' )
		                      ->setMethods( array( 'set_update_status' ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$status_helper->expects( $this->never() )
		              ->method( 'set_update_status' );

		\WP_Mock::userFunction( 'wpml_get_post_status_helper', array(
			'return' => $status_helper
		) );

		$subject->set_translation_status_as_needs_update( 1, $product_id, $meta_key );
	}


	/**
	 * @test
	 */
	public function it_should_filter_terms_on_get_variation() {
		WP_Mock::passthruFunction( 'wp_unslash' );

		$attribute_taxonomy                          = 'pa_color';
		$translated_term_slug                        = 'blue-de';
		$translated_term_id                          = 15;
		$original_language                           = 'en';
		$original_term                               = new stdClass();
		$original_term->slug                         = 'blue';
		$_POST['product_id']                         = 11;
		$_POST[ 'attribute_' . $attribute_taxonomy ] = $translated_term_slug;
		$current_language                            = 'es';

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array(
			                                         'get_original_product_language', 'is_product_display_as_translated_post_type'
		                                         ) )
		                                         ->getMock();
		$this->woocommerce_wpml->products->method( 'is_product_display_as_translated_post_type' )->willReturn( true );
		$this->woocommerce_wpml->products->method( 'get_original_product_language' )->with( $_POST['product_id'] )->willReturn( $original_language );

		$this->woocommerce_wpml->terms = $this->getMockBuilder( 'WCML_Terms' )
		                                      ->disableOriginalConstructor()
		                                      ->setMethods( array(
			                                      'wcml_get_term_id_by_slug',
			                                      'wcml_get_translated_term'
		                                      ) )
		                                      ->getMock();
		$this->woocommerce_wpml->terms->method( 'wcml_get_term_id_by_slug' )->with( $attribute_taxonomy, $translated_term_slug )->willReturn( $translated_term_id );
		$this->woocommerce_wpml->terms->method( 'wcml_get_translated_term' )->with( $translated_term_id, $attribute_taxonomy, $original_language )->willReturn( $original_term );

		$this->sitepress->method( 'get_current_language' )->willReturn( $current_language );
		$this->post_translations->method( 'element_id_in' )->with( $_POST['product_id'], $current_language )->willReturn( null );

		$subject = $this->get_subject();
		$subject->maybe_filter_get_variation();

		$this->assertEquals( $original_term->slug, $_POST[ 'attribute_' . $attribute_taxonomy ] );
		unset( $_POST['product_id'], $_POST[ 'attribute_' . $attribute_taxonomy ] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_terms_on_get_variation_for_custom_attributes() {
		WP_Mock::passthruFunction( 'wp_unslash' );

		$term_slug                 = 'custom-slug';
		$_POST['product_id']       = 12;
		$_POST['attribute_custom'] = $term_slug;
		$current_language          = 'es';

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array(
			                                         'is_product_display_as_translated_post_type'
		                                         ) )
		                                         ->getMock();
		$this->woocommerce_wpml->products->method( 'is_product_display_as_translated_post_type' )->willReturn( true );

		$this->sitepress->method( 'get_current_language' )->willReturn( $current_language );
		$this->post_translations->method( 'element_id_in' )->with( $_POST['product_id'], $current_language )->willReturn( null );

		$subject = $this->get_subject();
		$subject->maybe_filter_get_variation();

		$this->assertEquals( $term_slug, $_POST['attribute_custom'] );
		unset( $_POST['product_id'], $_POST['attribute_custom'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_terms_on_get_variation_for_disabled_display_as_translated_mode() {
		WP_Mock::passthruFunction( 'wp_unslash' );

		$attribute_taxonomy                          = 'pa_color';
		$translated_term_slug                        = 'blue-de';
		$_POST['product_id']                         = 11;
		$_POST[ 'attribute_' . $attribute_taxonomy ] = $translated_term_slug;

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array(
			                                         'is_product_display_as_translated_post_type'
		                                         ) )
		                                         ->getMock();
		$this->woocommerce_wpml->products->method( 'is_product_display_as_translated_post_type' )->willReturn( false );

		$subject = $this->get_subject();
		$subject->maybe_filter_get_variation();

		$this->assertEquals( $translated_term_slug, $_POST[ 'attribute_' . $attribute_taxonomy ] );
		unset( $_POST['product_id'], $_POST[ 'attribute_' . $attribute_taxonomy ] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_terms_on_get_variation_for_translated_products() {
		WP_Mock::passthruFunction( 'wp_unslash' );

		$attribute_taxonomy                          = 'pa_color';
		$translated_term_slug                        = 'blue-de';
		$_POST['product_id']                         = 11;
		$translated_product_id                       = 12;
		$_POST[ 'attribute_' . $attribute_taxonomy ] = $translated_term_slug;
		$current_language                            = 'es';

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array(
			                                         'is_product_display_as_translated_post_type'
		                                         ) )
		                                         ->getMock();
		$this->woocommerce_wpml->products->method( 'is_product_display_as_translated_post_type' )->willReturn( true );

		$this->sitepress->method( 'get_current_language' )->willReturn( $current_language );
		$this->post_translations->method( 'element_id_in' )->with( $_POST['product_id'], $current_language )->willReturn( $translated_product_id );

		$subject = $this->get_subject();
		$subject->maybe_filter_get_variation();

		$this->assertEquals( $translated_term_slug, $_POST[ 'attribute_' . $attribute_taxonomy ] );
		unset( $_POST['product_id'], $_POST[ 'attribute_' . $attribute_taxonomy ] );
	}

	/**
	 * @test
	 */
	public function it_should_set_custom_product_attributes_as_translatable_for_tm_job() {

		$job_translate['field_type'] = 'wc_attribute_name:color';

		$subject = $this->get_subject();

		$this->assertTrue( $subject->set_custom_product_attributes_as_translatable_for_tm_job( false, $job_translate ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_set_other_fileds_as_translatable_for_tm_job() {

		$job_translate['field_type'] = 'title';

		$subject = $this->get_subject();

		$this->assertFalse( $subject->set_custom_product_attributes_as_translatable_for_tm_job( false, $job_translate ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_attributes_label_translations() {

		$product_id = 10;
		$lang       = 'de';

		$label_translations = [ $lang => [ 'color' => 'colore' ] ];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product_id, 'attr_label_translations', true ],
			'return' => $label_translations
		] );

		$subject = $this->get_subject();

		$this->assertEquals( $label_translations[ $lang ], $subject->get_attr_label_translations( $product_id, $lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_remove_empty_strings_from_attributes_label_translations() {

		$product_id = 10;
		$lang       = 'de';

		$label_translations = [ $lang => [ 'color' => 'colore', 'size' => '' ] ];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product_id, 'attr_label_translations', true ],
			'return' => $label_translations
		] );

		$subject = $this->get_subject();

		$this->assertEquals( [ 'color' => 'colore' ], $subject->get_attr_label_translations( $product_id, $lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_remove_empty_strings_from_attributes_label_translations_in_all_languages() {

		$product_id = 10;

		$label_translations = [ 'de' => [ 'color' => 'colore', 'size' => '' ], 'fr' => [ 'color' => 'color', 'size' => '' ] ];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product_id, 'attr_label_translations', true ],
			'return' => $label_translations
		] );

		$subject = $this->get_subject();

		$this->assertEquals( [ 'de' => [ 'color' => 'colore' ], 'fr' => [ 'color' => 'color' ] ], $subject->get_attr_label_translations( $product_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_if_postmeta_corrupted_for_attributes_label_translations() {

		$product_id = 10;
		$lang       = 'de';

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product_id, 'attr_label_translations', true ],
			'return' => 'data'
		] );

		$subject = $this->get_subject();

		$this->assertEquals( [], $subject->get_attr_label_translations( $product_id, $lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_for_not_translated_language_for_attributes_label_translations() {

		$product_id = 10;
		$lang       = 'fr';

		$label_translations = [ 'de' => [ 'color' => 'colore' ] ];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product_id, 'attr_label_translations', true ],
			'return' => $label_translations
		] );

		$subject = $this->get_subject();

		$this->assertEquals( [], $subject->get_attr_label_translations( $product_id, $lang ) );
	}
	/**
	 * @test
	 */
	public function it_should_return_all_language_for_attributes_label_translations() {

		$product_id = 10;

		$label_translations = [ 'de' => [ 'color' => 'colore' ] ];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product_id, 'attr_label_translations', true ],
			'return' => $label_translations
		] );

		$subject = $this->get_subject();

		$this->assertEquals( $label_translations, $subject->get_attr_label_translations( $product_id ) );
	}


}
