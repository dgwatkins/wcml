<?php

class Test_WCML_URLS extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;

	private $url_translation;

	function setUp(){

		parent::setUp();

		$this->url_translation =& $this->woocommerce_wpml->url_translation;
		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';

	}


	function test_url_string_name(){
		$this->assertEquals( 'URL product_cat tax slug', $this->url_translation->url_string_name( 'product_cat' ) );
		$this->assertEquals( 'URL slug: product', $this->url_translation->url_string_name( 'product' ) );
		$this->assertEquals( 'URL attribute slug: color', $this->url_translation->url_string_name( 'attribute_slug', 'color' ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-1033
	 */

	function test_translate_bases_in_rewrite_rules_with_empty_string() {

		$empty_string = '';
		$filtered_values = $this->url_translation->translate_bases_in_rewrite_rules( $empty_string );
		$this->assertEquals( $empty_string, $filtered_values );
	}

	function test_check_wc_tax_url_on_redirect(){
		global $wp_query;

		$cat = $this->wcml_helper->add_term( 'Test', 'product_cat', $this->default_language );
		$cat_es = $this->wcml_helper->add_term( 'Test Es', 'product_cat', $this->second_language, false, $cat->trid  );

		$wp_query->is_tax = true;
		$wp_query->queried_object = new stdClass();
		$wp_query->queried_object->term_id = $cat_es->term_id;
		$wp_query->queried_object->taxonomy = 'product_cat';
		$this->sitepress->switch_lang( $this->second_language );
		$expected_url = 'http://example.org/?product_cat=test-es&lang=es';

		$this->switch_to_langs_as_params('', true);

		$filtered_url = $this->url_translation->check_wc_tax_url_on_redirect(
			'',
			'http://example.org/?product_cat=test&lang=' . $this->second_language
		);
		$this->assertEquals( $expected_url, $filtered_url );
	}

	function test_translate_product_base(){

		$posts_slug_translation = array( 'on' => 0 );
		$this->sitepress->set_setting( 'posts_slug_translation', $posts_slug_translation, true );


		global $ICL_Pro_Translation;
		$ICL_Pro_Translation = $this->getMockBuilder( 'WPML_Pro_Translation' )->disableOriginalConstructor()->getMock();

		$this->url_translation->translate_product_base();

		$posts_slug_translation = $this->sitepress->get_setting( 'posts_slug_translation' );

		$this->assertEquals( 1, $posts_slug_translation['on'] );
		$this->assertEquals( 1, $posts_slug_translation['types']['product'] );

	}

	function test_get_woocommerce_product_base(){

		$base = $this->url_translation->get_woocommerce_product_base();

		$this->assertEquals( 'product', $base );

	}

	// covers add_default_slug_translations too
	function test_register_product_and_taxonomy_bases(){

		$this->url_translation->register_product_and_taxonomy_bases();

		// Product base
		$context = $this->url_translation->url_strings_context();
		$name    = $this->url_translation->url_string_name( 'product' );

		$string_id = icl_get_string_id( 'product',  $context, $name );

		$string = icl_get_string_by_id( $string_id );

		$this->assertEquals( 'product', $string );

		$string_translations = icl_get_string_translations_by_id( $string_id );

		$this->assertEquals( 'produkt', $string_translations['de']['value'] );
		$this->assertEquals( 10, $string_translations['de']['status'] );
		$this->assertEquals( 'producto', $string_translations['es']['value'] );
		$this->assertEquals( 10, $string_translations['es']['status'] );
		$this->assertEquals( 'produit', $string_translations['fr']['value'] );
		$this->assertEquals( 10, $string_translations['fr']['status'] );
		$this->assertEquals( 'prodotto', $string_translations['it']['value'] );
		$this->assertEquals( 10, $string_translations['it']['status'] );

		// Product category base
		$context = $this->url_translation->url_strings_context();
		$name    = $this->url_translation->url_string_name( 'product_cat' );

		$permalink_options = $this->url_translation->wc_permalinks;
		$category_base = !empty( $permalink_options['category_base'] ) ? $permalink_options['category_base'] : $this->url_translation->default_product_category_base;
		$string_id = icl_get_string_id( $category_base,  $context, $name );

		$string = icl_get_string_by_id( $string_id );

		$this->assertEquals( $category_base, $string );

		$string_translations = icl_get_string_translations_by_id( $string_id );

		$this->assertEquals( 'produkt-kategorie', $string_translations['de']['value'] );
		$this->assertEquals( 10, $string_translations['de']['status'] );
		$this->assertEquals( 'categoria-producto', $string_translations['es']['value'] );
		$this->assertEquals( 10, $string_translations['es']['status'] );
		$this->assertEquals( 'categorie-produit', $string_translations['fr']['value'] );
		$this->assertEquals( 10, $string_translations['fr']['status'] );
		$this->assertEquals( 'categoria-prodotto', $string_translations['it']['value'] );
		$this->assertEquals( 10, $string_translations['it']['status'] );

		// Product tag base
		$context = $this->url_translation->url_strings_context();
		$name    = $this->url_translation->url_string_name( 'product_tag' );

		$permalink_options = $this->url_translation->wc_permalinks;
		$category_base = !empty( $permalink_options['tag_base'] ) ? $permalink_options['tag_base'] : $this->url_translation->default_product_tag_base;
		$string_id = icl_get_string_id( $category_base,  $context, $name );

		$string = icl_get_string_by_id( $string_id );

		$this->assertEquals( $category_base, $string );

		$string_translations = icl_get_string_translations_by_id( $string_id );

		$this->assertEquals( 'produkt-schlagwort', $string_translations['de']['value'] );
		$this->assertEquals( 10, $string_translations['de']['status'] );
		$this->assertEquals( 'producto-etiqueta', $string_translations['es']['value'] );
		$this->assertEquals( 10, $string_translations['es']['status'] );
		$this->assertEquals( 'etiquette-produit', $string_translations['fr']['value'] );
		$this->assertEquals( 10, $string_translations['fr']['status'] );
		$this->assertEquals( 'tag-prodotto', $string_translations['it']['value'] );
		$this->assertEquals( 10, $string_translations['it']['status'] );


	}

	function test_force_bases_in_strings_languages(){

		$this->sitepress->switch_lang( 'fr' );
		$rewrite['categorie-produit/(.+?)/?$'] = 'index.php?product_cat=$matches[1]';
		$rewrite['etiquette-produit/(.+?)/?$'] = 'index.php?product_tag=$matches[1]';

		$rewrite = $this->url_translation->force_bases_in_strings_languages( $rewrite );

		$this->assertTrue( !isset( $rewrite['categorie-produit/(.+?)/?$'] ) );
		$this->assertTrue( isset( $rewrite['product-category/(.+?)/?$'] ) );
		$this->assertTrue( !isset( $rewrite['etiquette-produit/(.+?)/?$'] ) );
		$this->assertTrue( isset( $rewrite['product-tag/(.+?)/?$'] ) );

		$this->sitepress->switch_lang( 'en' );

	}

	function test_translate_bases_in_rewrite_rules(){

		$rewrite['product-category/(.+?)/?$'] = 'index.php?product_cat=$matches[1]';
		$rewrite['etiquette-produit/(.+?)/?$'] = 'index.php?product_tag=$matches[1]';


		$this->sitepress->switch_lang( 'fr' );
		$rewrite = $this->url_translation->translate_bases_in_rewrite_rules( $rewrite );

		$this->assertTrue( isset( $rewrite['categorie-produit/(.+?)/?$'] ) );
		$this->assertTrue( !isset( $rewrite['product-category/(.+?)/?$'] ) );
		$this->assertTrue( isset( $rewrite['etiquette-produit/(.+?)/?$'] ) );
		$this->assertTrue( !isset( $rewrite['product-tag/(.+?)/?$'] ) );

		$this->sitepress->switch_lang( 'en' );

	}

	function test_translate_taxonomy_base(){

		global $wp_rewrite;
		$wp_rewrite->extra_permastructs = array();

		// CATEGORIES
		$wp_rewrite->extra_permastructs['product_cat']['struct'] = 'product-category/%product_cat%';
		$wp_rewrite->extra_permastructs['product_cat'] = array(
			'struct' => 'product-category/%product_cat%',
			'ep_mask'=> 0,
			'paged'  => 1,
			'feed'   => 1,
			'forcomments' => '',
			'walk_dirs'   => 1,
			'endpoints'   => 1
		);

		$this->switch_to_langs_in_dirs( '/%postname%/', true );
		$wp_rewrite->generate_rewrite_rules( '/%postname%/' );


		$term    = $this->wcml_helper->add_term( 'Shoes', 'product_cat', 'en' );
		$term_fr = wpml_test_insert_term( 'fr', 'product_cat', $term->trid, 'Le Shoes' );
		$term_fr = get_term( $term_fr['term_id'], 'product_cat' );

		$this->assertEquals( 'http://example.org/categorie-produit/le-shoes/?lang=fr', get_term_link( $term_fr->term_id ) );


		// TAGS
		$wp_rewrite->extra_permastructs['product_tag']['struct'] = 'product-tag/%product_tag%';
		$wp_rewrite->extra_permastructs['product_tag'] = array(
			'struct' => 'product-tag/%product_tag%',
			'ep_mask'=> 0,
			'paged'  => 1,
			'feed'   => 1,
			'forcomments' => '',
			'walk_dirs'   => 1,
			'endpoints'   => 1
		);

		$this->switch_to_langs_in_dirs( '/%postname%/', true );
		$wp_rewrite->generate_rewrite_rules( '/%postname%/' );


		$term    = $this->wcml_helper->add_term( 'Some Tag', 'product_tag', 'en' );
		$term_fr = wpml_test_insert_term( 'fr', 'product_tag', $term->trid, 'Some French Tag' );
		$term_fr = get_term( $term_fr['term_id'], 'product_tag' );

		global $wp_taxonomies;
		$wp_taxonomies['product_tag']->rewrite['hierarchical'] = 0; // need this for some reason

		$this->assertEquals( 'http://example.org/etiquette-produit/some-french-tag/?lang=fr', get_term_link( $term_fr->term_id ) );


	}

}