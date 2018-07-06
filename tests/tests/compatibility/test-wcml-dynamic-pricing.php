<?php

/**
 * Class Test_WCML_Dynamic_Pricing
 */
class Test_WCML_Dynamic_Pricing extends WCML_UnitTestCase {

	public $sitepress;
	private $default_language;
	private $second_language;
	private $rate;
	private $settings_helper;

	function setUp() {
		global $sitepress;
		parent::setUp();
		$this->rate = random_int( 1, 999 );
		$this->sitepress = $sitepress;
		$this->default_language = $sitepress->get_default_language();
		$active_languages = $this->sitepress->get_active_languages();
		unset( $active_languages[ $this->default_language ] );
		$this->second_language = array_rand( $active_languages );
		add_filter( 'wcml_raw_price_amount', array( $this, 'raw_price_amount' ) );
		$this->settings_helper = wpml_load_settings_helper();
		$this->settings_helper->set_taxonomy_translatable( 'product_cat' );
	}

	/**
	 * @test
	 */
	public function filter_price() {
		$amount = random_int( 1, 9999 );
		$module = new stdClass();
		$module->available_rulesets = array(
			'rule_key' => array(
				'rules' => array(
					'r_key' => array(
						'amount' => $amount,
						'type'   => 'fixed_product',
					),
				),
			),
		);
		$modules = array(
			'mod_key' => $module,
		);
		$expected_obj = new stdClass();
		$expected_obj->available_rulesets = array(
			'rule_key' => array(
				'rules' => array(
					'r_key' => array(
						'amount' => $amount / $this->rate,
						'type'   => 'fixed_product',
					),
				),
			),
		);
		$expected = array(
			'mod_key' => $expected_obj,
		);
		$dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
		$this->assertEquals( $expected, $dynamic_pricing->filter_price( $modules ) );
	}


	/**
	 * @test
	 */
	public function filter_price_by_user_role() {
		$amount = random_int( 1, 9999 );
		$module = new stdClass();
		$module->available_rulesets = array(
			'rule_key' => array(
				'amount' => $amount,
				'type'   => 'fixed_product',
			),
		);
		$modules = array(
			'mod_key' => $module,
		);
		$expected_obj = new stdClass();
		$expected_obj->available_rulesets = array(
			'rule_key' => array(
				'amount' => $amount / $this->rate,
				'type'   => 'fixed_product',
			),
		);
		$expected = array(
			'mod_key' => $expected_obj,
		);
		$dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
		$this->assertEquals( $expected, $dynamic_pricing->filter_price( $modules ) );
	}

	public function raw_price_amount( $amount ) {
		return $amount / $this->rate;
	}

	/**
	 * @test
	 */
	public function woocommerce_dynamic_pricing_is_applied_to() {
		$product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );
		$tr_product = $this->wcml_helper->add_product( $this->second_language, $product->trid, random_string() );
		$product_obj = wc_get_product( $product->id );
		$tr_product_obj = wc_get_product( $tr_product->id );

		$product_category = wpml_test_insert_term( $this->default_language, 'product_cat', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $product_category['term_id'], 'tax_product_cat' );
		$tr_catrgory = wpml_test_insert_term( $this->second_language, 'product_cat', $trid, random_string() );

		wp_set_post_terms( $product->id, array( $product_category['term_id'] ), 'product_cat' );
		$dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
		$obj = new stdClass();
		$obj->available_rulesets = array(
			'dummy_item' => 'dummy_rule',
		);
		$this->assertTrue( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $product_obj, null, $obj, $product_category['term_id'] ) );
		$this->assertTrue( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $product_obj, null, $obj, array( $product_category['term_id'] ) ) );
		$this->assertFalse( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $tr_product_obj, null, $obj, $product_category['term_id'] ) );
		$this->assertFalse( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $tr_product_obj, null, $obj, array( $product_category['term_id'] ) ) );

		$this->sitepress->set_term_filters_and_hooks();
		$this->sitepress->set_setting( 'sync_post_taxonomies', true );
		wp_set_post_terms( $product->id, array( $product_category['term_id'] ), 'product_cat' );
		$this->assertTrue( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $product_obj, null, $obj, $product_category['term_id'] ) );
		$this->assertTrue( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $product_obj, null, $obj, array( $product_category['term_id'] ) ) );
		$this->sitepress->switch_lang( $this->second_language );
		$this->assertTrue( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $tr_product_obj, null, $obj, $product_category['term_id'] ) );
		$this->assertTrue( $dynamic_pricing->woocommerce_dynamic_pricing_is_applied_to( false, $tr_product_obj, null, $obj, array( $product_category['term_id'] ) ) );
		$this->sitepress->switch_lang( $this->default_language );
	}

	/**
	 * @test
	 */
	public function woocommerce_dynamic_pricing_get_rule_amount() {
		$dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
		$amount = random_int( 1, 999 );

		foreach ( array( 'fixed_price', 'price_discount', 'dummy_type' ) as $type ) {
			$rule = array(
				'type' => $type,
			);
			if ( 'dummy_type' !== $type ) {
				$this->assertEquals( $amount / $this->rate, $dynamic_pricing->woocommerce_dynamic_pricing_get_rule_amount( $amount, $rule ) );
			} else {
				$this->assertEquals( $amount, $dynamic_pricing->woocommerce_dynamic_pricing_get_rule_amount( $amount, $rule ) );
			}
		}
	}

	/**
	 * @test
	 */
	public function dynamic_pricing_product_rules() {
		$dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
		$amount1 = random_int( 1, 999 );
		$amount2 = random_int( 1, 999 );
		$amount3 = random_int( 1, 999 );
		$rules = array(
			'r_key' => array(
				'rules' => array(
					'key' => array(
						'type'   => 'price_discount',
						'amount' => $amount1,
					),
					'key1' => array(
						'type'   => 'fixed_price',
						'amount' => $amount2,
					),
					'key2' => array(
						'type'   => 'dummy_key',
						'amount' => $amount3,
					),
				),
			),
		);

		$expected = array(
			'r_key' => array(
				'rules' => array(
					'key' => array(
						'type'   => 'price_discount',
						'amount' => $amount1 / $this->rate,
					),
					'key1' => array(
						'type'   => 'fixed_price',
						'amount' => $amount2 / $this->rate,
					),
					'key2' => array(
						'type'   => 'dummy_key',
						'amount' => $amount3,
					),
				),
			),
		);

		$this->assertEquals( $expected, $dynamic_pricing->dynamic_pricing_product_rules( $rules ) );
	}

	/**
	 * @test
	 */
	public function calculate_totals_exception() {
		$dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
		$this->assertFalse( $dynamic_pricing->calculate_totals_exception() );
	}

	function tearDown() {
		parent::tearDown();
		$this->settings_helper->set_taxonomy_not_translatable( 'product_cat' );
	}
}
