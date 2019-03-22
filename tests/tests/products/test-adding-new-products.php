<?php

/**
 * Class Test_Adding_New_Products
 */
class Test_Adding_New_Products extends WCML_UnitTestCase {

	/**
	 * @var SitePress
	 */
	public $sitepress;

	/**
	 * Setup test.
	 */
	public function setUp() {
		global $sitepress;
		parent::setUp();
		$this->sitepress = $sitepress;
	}

	/**
	 * Run product scenarios.
	 *
	 * @test
	 */
	public function product_scenarios() {
		$this->switch_to_admin();
		$default_language = $this->sitepress->get_default_language();

		$second_language = 'de';
		$third_language = 'it';

		foreach ( array(
			array( $default_language, $second_language ),
			array( $second_language, $default_language ),
			array( $second_language, $third_language ),
		) as $languages ) {
			$this->add_and_check_simple_product( $languages[0], $languages[1] );
			$this->add_and_check_variable_product( $languages[0], $languages[1] );
			$this->add_and_check_grouped_product( $languages[0], $languages[1] );
		}
	}

	/**
	 * Variable product checks.
	 *
	 * @param $orig_lang
	 * @param $second_lang
	 */
	private function add_and_check_variable_product( $orig_lang, $second_lang ) {
		global $woocommerce_wpml;
		$orig_product = $this->add_variable_product( $orig_lang );
		$translation = wpml_test_insert_post( $second_lang, 'product', $orig_product['post']->trid, random_string() );
		WCML_Helper::set_product_as_variable( $translation );
		wp_cache_init();

		$woocommerce_wpml->sync_product_data->sync_product_data( $orig_product['post']->id, $translation, $second_lang );

		$this->assertTrue( (bool) $woocommerce_wpml->products->is_variable_product( $orig_product['post']->id ) );
		$this->assertTrue( (bool) $woocommerce_wpml->products->is_variable_product( $translation ) );

		$this->assertFalse( (bool) $woocommerce_wpml->products->is_grouped_product( $orig_product['post']->id ) );
		$this->assertFalse( (bool) $woocommerce_wpml->products->is_grouped_product( $translation ) );

		$this->common_checks( $orig_product['post']->id, $translation, $orig_lang );
	}

	/**
	 * Grouped product scenario.
	 *
	 * @param $orig_lang
	 * @param $second_lang
	 */
	public function add_and_check_grouped_product( $orig_lang, $second_lang ) {
		global $woocommerce_wpml;
		$grouped_product = $this->add_simple_product( $orig_lang );

		// Grouped product type
		$term = get_term_by( 'name', 'grouped', 'product_type', ARRAY_A );
		if ( !$term ) {
			$term = wp_insert_term( 'grouped', 'product_type' );
		}

		wp_set_post_terms(
			$grouped_product['post']->id,
			array( $term[ 'term_id' ] ),
			'product_type',
			true
		);
		$translation = wpml_test_insert_post( $second_lang, 'product', $grouped_product['post']->trid, random_string() );
		wp_set_post_terms(
			$translation,
			array( $term[ 'term_id' ] ),
			'product_type',
			true
		);
		wp_cache_init();
		$woocommerce_wpml->sync_product_data->sync_product_data( $grouped_product['post']->id, $translation, $second_lang );

		$this->assertTrue( (bool) $woocommerce_wpml->products->is_grouped_product( $grouped_product['post']->id ) );
		$this->assertTrue( (bool) $woocommerce_wpml->products->is_grouped_product( $translation ) );

		$this->assertFalse( (bool) $woocommerce_wpml->products->is_variable_product( $grouped_product['post']->id ) );
		$this->assertFalse( (bool) $woocommerce_wpml->products->is_variable_product( $translation ) );


		$this->common_checks( $grouped_product['post']->id, $translation, $orig_lang );
	}

	/**
	 * Simple product scenario.
	 *
	 * @param $orig_lang
	 * @param $second_lang
	 */
	private function add_and_check_simple_product( $orig_lang, $second_lang ) {
		global $woocommerce_wpml;
		$orig_product = $this->add_simple_product( $orig_lang );
		$translation = $this->add_simple_product( $second_lang, $orig_product['post']->trid );

		$woocommerce_wpml->sync_product_data->sync_product_data( $orig_product['post']->id, $translation['post']->id, $second_lang );
		$this->assertFalse( (bool) $woocommerce_wpml->products->is_variable_product( $orig_product['post']->id ) );
		$this->assertFalse( (bool) $woocommerce_wpml->products->is_grouped_product( $orig_product['post']->id ) );

		$this->common_checks( $orig_product['post']->id, $translation['post']->id, $orig_lang );
	}

	/**
	 * Group some common checks.
	 *
	 * @param $original_id
	 * @param $translation_id
	 * @param $lang
	 */
	private function common_checks( $original_id, $translation_id, $lang ) {
		global $woocommerce_wpml;
		$cache_group = 'original_product_language';
		$this->assertEquals( $lang, $woocommerce_wpml->products->get_original_product_language( $original_id ) );
		wp_cache_delete( $original_id, $cache_group );
		$this->assertEquals( $lang, $woocommerce_wpml->products->get_original_product_language( $translation_id ) );
		wp_cache_delete( $translation_id, $cache_group );
		$this->assertTrue( (bool) $woocommerce_wpml->products->is_original_product( $original_id ) );
		$this->assertFalse( (bool) $woocommerce_wpml->products->is_original_product( $translation_id ) );
	}

	/**
	 *
	 * @param string $language
	 * @param bool $trid
	 * @param bool $title
	 * @param int $parent
	 *
	 * @return mixed
	 */
	private function add_simple_product( $language = 'en', $trid = false, $title = false, $parent = 0 ) {
		$product['price'] = random_int( 10, 9999 );
		$product['post'] = $this->wcml_helper->add_product(
			$language,
			$trid ? $trid : false,
			$title ? $title : random_string(),
			0 !== $parent ? $parent : 0,
			array(
				'_price'         => $product['price'],
				'_regular_price' => $product['price'],
			)
		);

		return $product;
	}

	/**
	 * @param string $language
	 *
	 * @return mixed
	 */
	private function add_variable_product( $language = 'en' ) {
		$title = random_string();
		WCML_Helper::register_attribute( 'color' );
		$first_attr = 'white_' . random_string();
		$second_attr = 'black_' . random_string();
		WCML_Helper::add_attribute_term( $first_attr, 'color', $language );
		WCML_Helper::add_attribute_term( $second_attr, 'color', $language );
		$variation_data = array(
			'product_title' => $title,
			'attribute' => array(
				'name' => 'pa_color',
			),
			'variations' => array(
				$first_attr => array(
					'price'     => 10.06,
					'regular'   => 10.06,
				),
				$second_attr => array(
					'price'     => 15.99,
					'regular'   => 15.99,
				),
			),
		);

		$product['post'] = $this->wcml_helper->add_variable_product( $variation_data, false, $language );

		return $product;
	}
}
