<?php

/**
 * @group shortcodes
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
					->setMethods( [ 'get_current_language', 'get_default_language' ] )
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
	 * @group wcml-2981
	 */
	public function should_translate_category_by_slug_or_id() {
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$sitepress->method('get_current_language')->willReturn( rand_str() );
		$sitepress->method('get_default_language')->willReturn( rand_str() );

		$categoryBySlug = 'my-cool-category';
		$categoryById   = 123;

		$args = [
			'tax_query' => [
				[
					'taxonomy' => 'product_cat',
					'terms'    => $categoryBySlug,
					'field'    => 'slug',
					'operator' => 'IN'
				],
				[
					'taxonomy' => 'product_cat',
					'terms'    => $categoryById,
					'field'    => 'id',
					'operator' => 'IN'
				]
			]
		];

		$atts = [
			'category' => " $categoryBySlug ,  , $categoryById ",
		];

		$translatedCat1 = $this->getCategory( 'translated-1' );
		\WP_Mock::userFunction( 'get_term_by', [
			'args'   => [ 'slug', $categoryBySlug, 'product_cat' ],
			'return' => $translatedCat1
		] );

		$translatedCat2 = $this->getCategory( 'translated-2' );
		\WP_Mock::userFunction( 'get_term', [
			'args'   => [ $categoryById, 'product_cat' ],
			'return' => $translatedCat2
		] );

		$args = $subject->translate_category( $args, $atts );

		$this->assertSame( $translatedCat1->slug, $args['tax_query'][0][0]['terms'][0] );
		$this->assertSame( $translatedCat2->slug, $args['tax_query'][0][0]['terms'][1] );
	}

	/**
	 * @test
	 * @group wpmlcore-6202
	 */
	public function translate_category_should_not_do_anything() {
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$sitepress->method('get_current_language')->willReturn( rand_str() );
		$sitepress->method('get_default_language')->willReturn( rand_str() );

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
			'category' => '',
		];

		\WP_Mock::userFunction( 'remove_filter', [ 'times'  => 0 ] );
		\WP_Mock::userFunction( 'get_terms', [ 'times'  => 0 ] );

		$args_not_filtered = $subject->translate_category( $args, $atts );

		$this->assertSame( $args_not_filtered, $args );

	}

	/**
	 * @test
	 */
	public function translate_categories_using_simple_tax_query(){
		$sitepress = $this->get_sitepress();
		$subject   = $this->get_subject( $sitepress );

		$category_slugs = [
			0 => rand_str( 32 ),
			1 => rand_str( 32 ),
		];

		$args = [ 'product_cat' => implode(',', $category_slugs) ];

		\WP_Mock::userFunction( 'remove_filter', [
			'times'  => 1,
			'args'   => [ 'terms_clauses', [ $sitepress, 'terms_clauses' ], 10 ],
			'return' => true
		] );
		\WP_Mock::expectFilterAdded( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 3 );

		$category_term_translations = [
			'0' => $this->getCategory( 'cat1' ),
			'1' => $this->getCategory( 'cat2' ),
		];
		$category_slugs_translated = $category_term_translations[0]->slug . ',' . $category_term_translations[1]->slug;

		\WP_Mock::userFunction( 'get_terms', [
			'times'  => 1,
			'args'   => [ [ 'slug' => $category_slugs, 'taxonomy' => 'product_cat' ] ],
			'return' => $category_term_translations
		] );


		$args_filtered = $subject->translate_categories_using_simple_tax_query( $args );

		$this->assertSame( $category_slugs_translated, $args_filtered['product_cat'] );

	}

	private function getCategory( $slug ) {
		$term = $this->getMockBuilder( 'WP_Term' )
			->disableOriginalConstructor()
			->getMock();
		$term->slug = $slug;

		return $term;
	}
}