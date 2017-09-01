<?php

/**
 * Class Test_WCML_WC_Shortcode_Product_Category
 */
class Test_WCML_WC_Shortcode_Product_Category extends OTGS_TestCase {

	public function get_subject( $sitepress = null ) {
		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new WCML_WC_Shortcode_Product_Category( $sitepress );
	}

	public function get_sitepress() {

		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_shortcode_products_query', array(
			$subject,
			'translate_category'
		), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function should_translate_category() {
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$category = rand_str();

		$args = [
			'tax_query' => [
				[
					'taxonomy' => 'product_cat',
					'terms'    => $category,
					'field'    => 'slug',
					'operator' => 'IN'
				]
			]
		];

		$atts = [
			'category' => $category
		];

		\WP_Mock::userFunction( 'remove_filter', [
			'times'  => 1,
			'args'   => [ 'terms_clauses', [ $sitepress, 'terms_clauses' ], 10 ],
			'return' => true
		] );

		$category_translation            = rand_str( 32 );
		$category_term_translation       = $this->getMockBuilder( 'WP_Term' )
		                                        ->disableOriginalConstructor()
		                                        ->getMock();
		$category_term_translation->slug = $category_translation;
		\WP_Mock::userFunction( 'get_terms', [
			'times'  => 1,
			'args'   => [ [ 'slug' => $atts['category'], 'taxonomy' => 'product_cat' ] ],
			'return' => [ $category_term_translation ]
		] );
		\WP_Mock::expectFilterAdded( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );

		\WP_Mock::userFunction( 'wp_list_pluck', [
			'times'  => 1,
			'args'   => [ [ $category_term_translation ], 'slug' ],
			'return' => [ $category_translation ]
		] );

		$args = $subject->translate_category( $args, $atts );

		$this->assertSame( $category_translation, $args['tax_query'][0][0]['terms'][0] );

	}

	/**
	 * @test
	 */
	public function translate_category_should_not_do_anything() {
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$args = [
			'tax_query' => [
				[
					'taxonomy' => 'product_cat',
					'terms'    => rand_str(),
					'field'    => 'slug',
					'operator' => 'IN'
				]
			]
		];

		$atts = [
			'category' . rand_str(32) => rand_str(32)
		];

		\WP_Mock::userFunction( 'remove_filter', [ 'times'  => 0 ] );
		\WP_Mock::userFunction( 'get_terms', [ 'times'  => 0 ] );
		\WP_Mock::expectFilterNotAdded( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );

		\WP_Mock::userFunction( 'wp_list_pluck', [ 'times'  => 0 ] );

		$args_not_filtered = $subject->translate_category( $args, $atts );

		$this->assertSame( $args_not_filtered, $args );

	}

}