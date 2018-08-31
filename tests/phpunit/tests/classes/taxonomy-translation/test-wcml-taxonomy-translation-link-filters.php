<?php

/**
 * Class Test_WCML_Taxonomy_Translation_Link_Filters
 * @group wcml-1988
 * @group taxonomy-translation
 */
class Test_WCML_Taxonomy_Translation_Link_Filters extends OTGS_TestCase {

	/**
	 * @var WCML_Attributes
	 */
	private $wcml_attributes;

	private $translatable_attributes;
	private $translatable_custom_taxonomies;

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction( '__' );
		\WP_Mock::wpPassthruFunction( 'esc_html__' );


		$this->wcml_attributes = $this->getMockBuilder( 'WCML_Attributes' )
		                              ->disableOriginalConstructor()
		                              ->setMethods( array( 'get_translatable_attributes' ) )
		                              ->getMock();

		$attribute_1                 = new stdClass();
		$attribute_1->attribute_name = rand_str();
		$attribute_2                 = new stdClass();
		$attribute_2->attribute_name = rand_str();

		$this->translatable_attributes = [ $attribute_1, $attribute_2 ];
		$this->wcml_attributes->method( 'get_translatable_attributes' )->willReturn( $this->translatable_attributes );

		$this->translatable_custom_taxonomies = [ rand_str() => new stdClass(), rand_str() => new stdClass() ];
		\WP_Mock::wpFunction( 'get_object_taxonomies', array( 'return' => $this->translatable_custom_taxonomies ) );

		$translatable_custom_taxonomies = $this->translatable_custom_taxonomies;
		\WP_Mock::wpFunction( 'is_taxonomy_translated', array(
			'return' => function ( $taxonomy ) use ( $translatable_custom_taxonomies ) {
				return isset( $translatable_custom_taxonomies[ $taxonomy ] );
			}
		) );

	}

	/**
	 * @test
	 */

	public function add_filters() {

		$subject = new WCML_Taxonomy_Translation_Link_Filters( $this->wcml_attributes );

		\WP_Mock::expectFilterAdded( 'wpml_notice_text', array(
			$subject,
			'override_translation_notice_text'
		), 10, 2 );

		\WP_Mock::expectFilterAdded( 'wpml_taxonomy_slug_translation_ui', array(
			$subject,
			'slug_translation_ui_class'
		), 10, 2 );

		$subject->add_filters();

	}

	/**
	 * @test
	 */
	public function override_translation_notice_text() {
		\WP_Mock::wpPassthruFunction( 'admin_url' );

		$subject = new WCML_Taxonomy_Translation_Link_Filters( $this->wcml_attributes );

		$notice = ['id' => rand_str(), 'group' => 'taxonomy-term-help-notices' ];

		$taxonomy = $this->getMockBuilder( 'WP_Taxonomy' )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$taxonomy->name                  = rand_str();
		$taxonomy->labels                = new stdClass();
		$taxonomy->labels->singular_name = rand_str();
		$taxonomy->labels->name          = rand_str();

		\WP_Mock::wpFunction( 'get_taxonomy', array( 'return' => $taxonomy ) );

		$text = rand_str();

		$subject->override_translation_notice_text( $text, $notice );
	}

	/**
	 * @test
	 */
	public function get_taxonomy_translation_screen_url() {


		\WP_Mock::wpFunction( 'add_query_arg', array(
			'return' => function ( $args, $url ) {
				$glue = strpos( $url, '?' ) ? '&' : '?';

				return $url . $glue . http_build_query( $args );
			}
		) );

		\WP_Mock::wpPassthruFunction( 'admin_url' );

		$subject = new WCML_Taxonomy_Translation_Link_Filters( $this->wcml_attributes );

		// Not translated taxonomy, return original url
		$taxonomy = rand_str();
		$url      = $subject->get_screen_url( $taxonomy );
		$this->assertFalse( $url );

		// Built in product_tag
		$taxonomy = 'product_tag';
		$url      = $subject->get_screen_url( $taxonomy );

		$this->assert_query_string_matches_values( $url, 'admin.php', array( 'page' => 'wpml-wcml', 'tab' => 'product_tag' ) );

		// Built in product_shipping_class
		$taxonomy = 'product_shipping_class';
		$url      = $subject->get_screen_url( $taxonomy );
		$this->assert_query_string_matches_values( $url, 'admin.php', array( 'page' => 'wpml-wcml', 'tab' => $taxonomy ) );

		// Attribute
		$taxonomy = 'pa_' . $this->translatable_attributes[0]->attribute_name;
		$url      = $subject->get_screen_url( $taxonomy );
		$this->assert_query_string_matches_values( $url, 'admin.php', array( 'page' => 'wpml-wcml', 'tab' => 'product-attributes', 'taxonomy' => $taxonomy ) );

		// Custom taxonomy
		$taxonomy = key( $this->translatable_custom_taxonomies );
		$url      = $subject->get_screen_url( $taxonomy );
		$this->assert_query_string_matches_values( $url, 'admin.php', array( 'page' => 'wpml-wcml', 'tab' => 'custom-taxonomies', 'taxonomy' => $taxonomy ) );

	}

	/**
	 * @test
	 */
	public function it_should_load_wcml_slug_translation_ui_class_for_attributes() {


		$subject = new WCML_Taxonomy_Translation_Link_Filters( $this->wcml_attributes );

		$mock = \Mockery::mock( 'overload:WCML_St_Taxonomy_UI' );

		$ui_class = $this->getMockBuilder( 'WPML_ST_Element_Slug_Translation_UI' );
		// Attribute
		$taxonomy          = 'pa_' . $this->translatable_attributes[0]->attribute_name;
		$filtered_ui_class = $subject->slug_translation_ui_class( $ui_class, $taxonomy );

		$this->assertNotSame( $ui_class, $filtered_ui_class );

	}

	private function assert_query_string_matches_values( $url, $expected_path, $expected_values ) {

		$parsed_url = parse_url( $url );

		$this->assertSame( $expected_path, $parsed_url['path'] );

		parse_str( $parsed_url['query'], $parsed_querystring );
		foreach ( $expected_values as $key => $value ) {
			$this->assertArrayHasKey( $key, $parsed_querystring );
			$this->assertSame( $value, $parsed_querystring[ $key ] );
		}
		foreach ( $parsed_querystring as $key => $value ) {
			$this->assertArrayHasKey( $key, $expected_values, $key . '=' . $value .' in querystring was not expected' );
		}
	}

}
