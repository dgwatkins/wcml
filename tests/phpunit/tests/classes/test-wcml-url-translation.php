<?php

class Test_WCML_url_translation extends OTGS_TestCase {

	private $options;

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction('_x');
	}

	public function get_woocommerce_multilingual(){

		return $this->getMockBuilder('woocommerce_wpml')
		     ->disableOriginalConstructor()
		     ->getMock();

	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress( $wp_api = null ) {
		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api' ) )
		                  ->getMock();

		if( null === $wp_api ){
			$wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'constant' ) )
		            ->getMock();
	}

	private function get_subject( $woocommerce_wpml = null, $sitepress = null ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_multilingual();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Url_Translation( $woocommerce_wpml, $sitepress, $this->stubs->wpdb() );

	}

	/**
	 * @test
	 */
	public function set_up_hooks(){

		$woocommerce_wpml = $this->get_woocommerce_multilingual();
		$woocommerce_wpml->settings['url_translation_set_up'] = true;

		$subject =  new WCML_Url_Translation( $woocommerce_wpml, $this->get_sitepress(), $this->stubs->wpdb() );

		\WP_Mock::wpFunction( 'get_option', array(
			'values' => 'woocommerce_permalinks',
			'return' => array()
		) );

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => true
		) );

		\WP_Mock::expectActionNotAdded( 'init', array( $subject, 'fix_post_object_rewrite_slug' ), 6 );

		$subject->set_up();

	}

	/**
	 * @test
	 */
	public function use_untranslated_default_url_bases(){

		$url_translation = new WCML_Url_Translation( $this->get_woocommerce_multilingual(), $this->get_sitepress(), $this->stubs->wpdb() );

		$url_translation->default_product_base = rand_str();
		$url_translation->default_product_category_base = rand_str();
		$url_translation->default_product_tag_base = rand_str();

		\WP_Mock::wpFunction( 'get_option', array(
			'values' => 'permalink_structure',
			'return' => false
		) );

		// set all
		$permalinks = [
			'product_base' => '',
			'category_base' => '',
			'tag_base' => ''
		];
		$filtered = $url_translation->use_untranslated_default_url_bases( $permalinks );

		$this->assertEquals( $filtered['product_base'], $url_translation->default_product_base );
		$this->assertEquals( $filtered['category_base'], $url_translation->default_product_category_base );
		$this->assertEquals( $filtered['tag_base'], $url_translation->default_product_tag_base );

		// do not set when already set
		$permalinks = [
			'product_base' => rand_str()
		];
		$filtered = $url_translation->use_untranslated_default_url_bases( $permalinks );
		$this->assertNotEquals( $filtered['product_base'], $url_translation->default_product_base );
		$this->assertEquals( $filtered['product_base'], $permalinks['product_base'] );

	}

	/**
	 * @test
	 */
	public function translate_attributes_bases_in_rewrite_rules() {

		$woocommerce_wpml = $this->get_woocommerce_multilingual();
		$woocommerce_wpml->strings = $this->getMockBuilder( 'WCML_WC_Strings' )
		                                  ->disableOriginalConstructor()
		                                  ->setMethods( array( 'get_string_language' ) )
		                                  ->getMock();

		$woocommerce_wpml->strings->method( 'get_string_language' )->willReturn( rand_str() );

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language' ) )
		                  ->getMock();

		$sitepress->method( 'get_current_language' )->willReturn( rand_str() );

		$subject = new WCML_Url_Translation( $woocommerce_wpml, $sitepress , $this->stubs->wpdb() );

		$attribute_base_slug = 'product-attribute';
		$attribute_slug = 'color';

		$attribute_taxonomies = array();
		$attr_object = new stdClass();
		$attr_object->attribute_name = $attribute_slug;
		$attribute_taxonomies[] = $attr_object;

		\WP_Mock::wpFunction( 'wc_get_attribute_taxonomies', array(
			'return' => $attribute_taxonomies
		) );

		$taxonomy_object = new stdClass();
		$taxonomy_object->rewrite[ 'slug' ] = $attribute_base_slug.'/'.$attribute_slug;

		\WP_Mock::wpFunction( 'get_taxonomy', array(
			'values' => 'pa_'.$attribute_slug,
			'return' => $taxonomy_object
		) );

		$this->rewrite_rules_attribute_base_is_translated( $subject, $attribute_base_slug, $attribute_slug );
		$this->rewrite_rules_attribute_slug_is_translated_without_base( $subject, $attribute_base_slug, $attribute_slug );
		$this->rewrite_rules_attribute_base_not_translated( $subject, $attribute_base_slug, $attribute_slug );

	}

	/**
	 * @test
	 */
	public function it_should_return_attribute_slug_source_slug_language(){

		$base = 'attribute_slug-color';
		$slug_language = rand_str();

		$woocommerce_wpml = $this->get_woocommerce_multilingual();
		$woocommerce_wpml->strings = $this->getMockBuilder( 'WCML_WC_Strings' )
		                                  ->disableOriginalConstructor()
		                                  ->setMethods( array( 'get_string_language' ) )
		                                  ->getMock();

		$woocommerce_wpml->strings->method( 'get_string_language' )->with( $base, 'WordPress', 'URL attribute slug: color')->willReturn( $slug_language );

		$subject = new WCML_Url_Translation( $woocommerce_wpml, $this->get_sitepress(), $this->stubs->wpdb() );
		$this->assertSame( $slug_language, $subject->get_source_slug_language( $base ) );
	}

	/**
	 * @test
	 */
	public function translate_product_post_type_link() {

		$translated_slug = rand_str();
		$post = new stdClass();
		$post->ID = mt_rand( 1, 100 );
		$post->post_type = 'product';

		$woocommerce_wpml = $this->get_woocommerce_multilingual();
		$woocommerce_wpml->strings = $this->getMockBuilder( 'WCML_WC_Strings' )
		                                  ->disableOriginalConstructor()
		                                  ->setMethods( array( 'get_translation_from_woocommerce_mo_file' ) )
		                                  ->getMock();

		$woocommerce_wpml->strings->expects($this->once())->method( 'get_translation_from_woocommerce_mo_file' )->willReturn( $translated_slug );

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_language_for_element' ) )
		                  ->getMock();

		$sitepress->expects($this->once())->method( 'get_language_for_element' )->with( $post->ID, 'post_product' )->willReturn( rand_str() );

		$subject = new WCML_Url_Translation( $woocommerce_wpml, $sitepress , $this->stubs->wpdb() );

		$permalink_structure = array();
		$permalink_structure['product_rewrite_slug'] = '/'.rand_str().'/%product_cat%';

		\WP_Mock::wpFunction( 'wc_get_permalink_structure', array(
			'return' => $permalink_structure
		) );

		$permalink = rand_str().'/uncategorized/'.rand_str();

		$filtered_permalink = $subject->translate_product_post_type_link( $permalink, $post );

		$this->assertContains( $translated_slug, $filtered_permalink );

		$post->post_type = rand_str();
		$permalink = rand_str();
		$filtered_permalink = $subject->translate_product_post_type_link( $permalink, $post );

		$this->assertEquals( $permalink, $filtered_permalink );

	}

	/**
	 * @test
	 */
	public function it_should_remove_query_vars_filter_if_ST_not_active_when_switching_blog(){

		$wp_api = $this->get_wpml_wp_api_mock();

		$wp_api->expects( $this->once() )
		       ->method( 'constant' )
		       ->with( 'WPML_ST_PATH' )
		       ->willReturn( rand_str() );

		WP_Mock::userFunction( 'is_plugin_active', array(
				'return' => false
			)
		);

		\WP_Mock::userFunction( 'remove_filter', array(
			'return' => true,
			'times'  => 1
		) );


		$subject = $this->get_subject( null, $this->get_sitepress( $wp_api ) );
		$subject->maybe_remove_query_vars_filter();
	}

	public function rewrite_rules_attribute_base_is_translated( $subject, $attribute_base_slug, $attribute_slug ){

		$translated_attribute_base_slug = 'product-attribute-translated';
		$translated_attribute_slug = 'color-translated';

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $attribute_base_slug, $subject->url_strings_context(), $subject->url_string_name( 'attribute' ) )
		        ->reply( $translated_attribute_base_slug );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $attribute_slug, $subject->url_strings_context(), $subject->url_string_name( 'attribute_slug', $attribute_slug ) )
		        ->reply( $translated_attribute_slug );

		$rule_string = rand_str();

		$values = array();
		$values[ $attribute_base_slug.'/'.$attribute_slug.'/([^/]+)/?$' ] = $rule_string;

		$expected_values = array();
		$expected_values[ $translated_attribute_base_slug.'/'.$translated_attribute_slug.'/([^/]+)/?$' ] = $rule_string;

		$filtered_rules = $subject->translate_attributes_bases_in_rewrite_rules( $values );

		$this->assertEquals( $expected_values, $filtered_rules );

	}

	public function rewrite_rules_attribute_slug_is_translated_without_base( $subject, $attribute_base_slug, $attribute_slug ){

		$translated_attribute_base_slug = '';
		$translated_attribute_slug = 'color-translated';

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $attribute_base_slug, $subject->url_strings_context(), $subject->url_string_name( 'attribute' ) )
		        ->reply( $translated_attribute_base_slug );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $attribute_slug, $subject->url_strings_context(), $subject->url_string_name( 'attribute_slug', $attribute_slug ) )
		        ->reply( $translated_attribute_slug );

		$rule_string = rand_str();

		$values = array();
		$values[ $attribute_slug.'/([^/]+)/?$' ] = $rule_string;

		$expected_values = array();
		$expected_values[ $translated_attribute_slug.'/([^/]+)/?$' ] = $rule_string;

		$filtered_rules = $subject->translate_attributes_bases_in_rewrite_rules( $values );

		$this->assertEquals( $expected_values, $filtered_rules );

	}

	public function rewrite_rules_attribute_base_not_translated( $subject, $attribute_base_slug, $attribute_slug ){

		$translated_attribute_slug = 'color-translated';

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $attribute_base_slug, $subject->url_strings_context(), $subject->url_string_name( 'attribute' ) )
		        ->reply( $attribute_base_slug );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $attribute_slug, $subject->url_strings_context(), $subject->url_string_name( 'attribute_slug', $attribute_slug ) )
		        ->reply( $translated_attribute_slug );

		$rule_string = rand_str();

		$values = array();
		$values[ $attribute_base_slug.'/'.$attribute_slug.'/([^/]+)/?$' ] = $rule_string;

		$expected_values = array();
		$expected_values[ $attribute_base_slug.'/'.$translated_attribute_slug.'/([^/]+)/?$' ] = $rule_string;

		$filtered_rules = $subject->translate_attributes_bases_in_rewrite_rules( $values );

		$this->assertEquals( $expected_values, $filtered_rules );

	}

	/**
	 * @test
	 * @dataProvider shop_page_slugs
	 */
	public function it_should_translate_shop_page_base_in_rewrite_rules( $default_slug, $current_slug ) {

		$rules = [
			$default_slug.'/?$' => 'index.php?post_type=product',
			$default_slug.'/page/([0-9]{1,})/?$' => 'index.php?post_type=product&paged=$matches[1]'
		];
		$expected_rules = [
			$current_slug.'/?$' => 'index.php?post_type=product',
			$current_slug.'/page/([0-9]{1,})/?$' => 'index.php?post_type=product&paged=$matches[1]'
		];

		$default_language = 'en';
		$current_shop_id = 12;
		$default_shop_id = 10;

		\WP_Mock::userFunction( 'wc_get_page_id', [
			'args' => [ 'shop' ],
			'return' => $current_shop_id
		] );

		\WP_Mock::userFunction( 'get_post', [
			'args' => [ $current_shop_id ],
			'return' => new stdClass()
		] );

		\WP_Mock::userFunction( 'get_post', [
			'args' => [ $default_shop_id ],
			'return' => new stdClass()
		] );

		\WP_Mock::userFunction( 'get_page_uri', [
			'args' => [ $current_shop_id ],
			'return' => urlencode( $current_slug )
		] );

		\WP_Mock::userFunction( 'get_page_uri', [
			'args' => [ $default_shop_id ],
			'return' => urlencode( $default_slug )
		] );

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $current_shop_id, 'page', true, $default_language )
		        ->reply( $default_shop_id );

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_default_language' ] )
		                  ->getMock();

		$sitepress->method( 'get_default_language' )->willReturn( $default_language );

		$subject = $this->get_subject( null, $sitepress );
		$this->assertEquals( $expected_rules, $subject->translate_shop_page_base_in_rewrite_rules( $rules ) );

	}

	public function shop_page_slugs(){
		return [
			[ 'shop', 'tienda' ],
			[ 'shop', 'магазин' ],
			[ 'магазин', 'shop' ],
		];
	}


}