<?php

/**
 * Class Test_WCML_Product_Addons
 */
class Test_WCML_Product_Addons extends WCML_UnitTestCase {

	const ENABLE_MULTI_CURRENCY = 1;
	const MULTI_CURRENCY_OFF = 2;

	public $sitepress;
	private $default_language;
	private $second_language;
	private $rate;
	private $settings_helper;

	function setUp() {
		global $sitepress;
		parent::setUp();
		$this->sitepress        = $sitepress;
		$this->default_language = $sitepress->get_default_language();
		$active_languages       = $this->sitepress->get_active_languages();
		unset( $active_languages[ $this->default_language ] );
		$this->second_language = array_rand( $active_languages );

		$this->rate = random_int( 1, 999 );
		add_filter( 'wcml_raw_price_amount', array( $this, 'raw_price_amount' ) );
		$this->settings_helper = wpml_load_settings_helper();
		$this->settings_helper->set_taxonomy_translatable( 'product_cat' );
	}

	/**
	 * @test
	 */
	public function register_addons_strings() {
		$name        = random_string();
		$description = random_string();
		$label       = random_string();
		$context     = 'wc_product_addons_strings';

		$addon_id  = random_int( 1, 999 );
		$option_id = random_int( 1, 999 );

		$addons = array(
			$addon_id => array(
				'type'        => 'dummy_addon',
				'position'    => 'top',
				'name'        => $name,
				'description' => $description,
				'options'     => array(
					$option_id => array(
						'label' => $label,
					),
				),
			),
		);

		$global_product_addon = wpml_test_insert_post( $this->default_language, 'global_product_addon', false, random_string() );
		$product_addons       = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
		$product_addons->register_addons_strings( null, $global_product_addon, '_product_addons', $addons );

		$this->assertTrue( icl_get_string_id( $name, $context, $global_product_addon . '_addon_dummy_addon_top_name' ) > 0 );
		$this->assertTrue( icl_get_string_id( $description, $context, $global_product_addon . '_addon_dummy_addon_top_description' ) > 0 );
		$this->assertTrue( icl_get_string_id( $label, $context, $global_product_addon . '_addon_dummy_addon_top_option_label_' . $option_id ) > 0 );
	}

	/**
	 * @test
	 */
	public function translate_addons_strings() {
		$name        = random_string();
		$description = random_string();
		$label       = random_string();

		$tr_name        = random_string();
		$tr_description = random_string();
		$tr_label       = random_string();

		$global_product_addon = wpml_test_insert_post( $this->default_language, 'global_product_addon', false, random_string() );
		$addon_id             = random_int( 1, 999 );
		$option_id            = random_int( 1, 999 );

		do_action( 'wpml_register_single_string', 'wc_product_addons_strings', $global_product_addon . '_addon_dummy_addon_top_name', $name );
		do_action( 'wpml_register_single_string', 'wc_product_addons_strings', $global_product_addon . '_addon_dummy_addon_top_description', $description );
		do_action( 'wpml_register_single_string', 'wc_product_addons_strings', $global_product_addon . '_addon_dummy_addon_top_option_label_' . $option_id, $label );

		$string_id = icl_get_string_id( $name, 'wc_product_addons_strings', $global_product_addon . '_addon_dummy_addon_top_name' );
		icl_add_string_translation( $string_id, $this->second_language, $tr_name, ICL_TM_COMPLETE );

		$string_id = icl_get_string_id( $description, 'wc_product_addons_strings', $global_product_addon . '_addon_dummy_addon_top_description' );
		icl_add_string_translation( $string_id, $this->second_language, $tr_description, ICL_TM_COMPLETE );

		$string_id = icl_get_string_id( $label, 'wc_product_addons_strings', $global_product_addon . '_addon_dummy_addon_top_option_label_' . $option_id );
		icl_add_string_translation( $string_id, $this->second_language, $tr_label, ICL_TM_COMPLETE );

		$addons = array(
			$addon_id => array(
				'type'        => 'dummy_addon',
				'position'    => 'top',
				'name'        => $name,
				'description' => $description,
				'options'     => array(
					$option_id => array(
						'label' => $label,
					),
				),
			),
		);

		$expected = array(
			array(
				$addon_id => array(
					'type'        => 'dummy_addon',
					'position'    => 'top',
					'name'        => $tr_name,
					'description' => $tr_description,
					'options'     => array(
						$option_id => array(
							'label' => $tr_label,
						),
					),
				),
			),
		);
		update_post_meta( $global_product_addon, '_product_addons', $addons );
		$product_addons = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
		$this->sitepress->switch_lang( $this->second_language );
		$this->assertEquals( $expected, $product_addons->translate_addons_strings( null, $global_product_addon, '_product_addons', null ) );
		$this->sitepress->switch_lang( $this->default_language );
	}

	/**
	 * @test
	 */
	public function product_addons_filter() {
		$price    = random_int( 1, 99 );
		$addons   = array(
			'addon_id' => array(
				'options' => array(
					'key' => array(
						'price' => $price,
					),
				),
			),
		);
		$expected = array(
			'addon_id' => array(
				'options' => array(
					'key' => array(
						'price' => $price / $this->rate,
					),
				),
			),
		);

		$product_addons = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
		$this->assertEquals( $expected, $product_addons->product_addons_filter( $addons ) );
	}

	/**
	 * @test
	 */
	public function addons_product_terms() {
		$product_category = wpml_test_insert_term( $this->default_language, 'product_cat', false, random_string() );
		$trid             = $this->sitepress->get_element_trid( $product_category['term_id'], 'tax_product_cat' );
		$tr_category      = wpml_test_insert_term( $this->second_language, 'product_cat', $trid, random_string() );

		$product_terms = array(
			'key' => $tr_category['term_id'],
		);

		$expected = array(
			'key' => $product_category['term_id'],
		);

		$product_addons = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
		$this->assertEquals( $expected, $product_addons->addons_product_terms( $product_terms ) );
	}

	/**
	 * @test
	 */
	public function custom_box_html_data() {
		$product    = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid       = $this->sitepress->get_element_trid( $product, 'post_product' );
		$tr_product = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );

		$name        = random_string();
		$description = random_string();
		$label       = random_string();

		$tr_name        = random_string();
		$tr_description = random_string();
		$tr_label       = random_string();

		$addon_id  = random_int( 1, 999 );
		$option_id = random_int( 1, 999 );

		$tr_addon_id  = random_int( 1, 999 );
		$tr_option_id = random_int( 1, 999 );

		$product_addons = array(
			$addon_id => array(
				'name'        => $name,
				'description' => $description,
				'options'     => array(
					$option_id => array(
						'label' => $label,
					),
				),
			),
		);

		$tr_product_addons = array(
			$tr_addon_id => array(
				'name'        => $tr_name,
				'description' => $tr_description,
				'options'     => array(
					$tr_option_id => array(
						'label' => $tr_label,
					),
				),
			),
		);

		$expected = array(
			'addon_' . $addon_id . '_name'                            => array( 'original' => $name ),
			'addon_' . $addon_id . '_description'                     => array( 'original' => $description ),
			'addon_' . $addon_id . '_option_' . $option_id . '_label' => array( 'original' => $label ),
		);

		update_post_meta( $product, '_product_addons', $product_addons );

		$product_addons = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
		$this->assertEquals( $expected, $product_addons->custom_box_html_data( array(), $product, null ) );

		update_post_meta( $tr_product, '_product_addons', $tr_product_addons );

		$expected[ 'addon_' . $tr_addon_id . '_name' ]                               = array( 'translation' => $tr_name );
		$expected[ 'addon_' . $tr_addon_id . '_description' ]                        = array( 'translation' => $tr_description );
		$expected[ 'addon_' . $tr_addon_id . '_option_' . $tr_option_id . '_label' ] = array( 'translation' => $tr_label );
		$translation                                                                 = new stdClass();
		$translation->ID                                                             = $tr_product;
		$this->assertEquals( $expected, $product_addons->custom_box_html_data( array(), $product, $translation ) );
	}

	/**
	 * @test
	 */
	public function addons_update() {
		$product     = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid        = $this->sitepress->get_element_trid( $product, 'post_product' );
		$name        = random_string();
		$description = random_string();
		$label       = random_string();
		$addon_id    = random_int( 1, 999 );
		$option_id   = random_int( 1, 999 );

		$product_addons = array(
			$addon_id => array(
				'name'        => random_string(),
				'description' => random_string(),
				'options'     => array(
					$option_id => array(
						'label' => random_string(),
					),
				),
			),
		);

		$expected = array(
			$addon_id => array(
				'name'        => $name,
				'description' => $description,
				'options'     => array(
					$option_id => array(
						'label' => $label,
					),
				),
			),
		);

		$data = array(
			md5( 'addon_' . $addon_id . '_name' )                            => $name,
			md5( 'addon_' . $addon_id . '_description' )                     => $description,
			md5( 'addon_' . $addon_id . '_option_' . $option_id . '_label' ) => $label,
		);

		update_post_meta( $product, '_product_addons', $product_addons );
		$tr_product     = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );
		$product_addons = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
		$product_addons->addons_update( $product, $tr_product, $data );
		$output = get_post_meta( $tr_product, '_product_addons', true );
		$this->assertEquals( $expected, $output );
	}

	/**
	 * @test
	 */
	public function filter_booking_addon_product_in_cart_contents_mc_is_off() {

		$cart_item         = array();
		$cart_item['data'] = new stdClass();

		$product_addons = new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );

		$filtered_cart_item = $product_addons->filter_booking_addon_product_in_cart_contents( $cart_item );
		$this->assertEquals( $cart_item, $filtered_cart_item );
	}

	/**
	 * @test
	 */
	public function filter_booking_addon_product_in_cart_contents_bookable_without_addons() {

		$cart_item         = array();
		$cart_item['data'] = new stdClass();

		$product_addons = new WCML_Product_Addons( $this->sitepress, self::MULTI_CURRENCY_OFF );

		$product_id        = $this->wcml_helper->add_product( $this->sitepress->get_default_language(), false, rand_str() );
		$cart_item['data'] = new WC_Product_Booking( $product_id->id );

		$filtered_cart_item = $product_addons->filter_booking_addon_product_in_cart_contents( $cart_item );
		$this->assertEquals( $cart_item, $filtered_cart_item );
	}

	/**
	 * @test
	 */
	public function filter_booking_addon_product_in_cart_contents_bookable_with_addons() {

		$cart_item = array();

		$product_addons = new WCML_Product_Addons( $this->sitepress, self::MULTI_CURRENCY_OFF );

		$product_id        = $this->wcml_helper->add_product( $this->sitepress->get_default_language(), false, rand_str() );
		$cart_item['data'] = new WC_Product_Booking( $product_id->id );

		$product_price = 10;
		$cart_item['data']->set_price( $product_price );

		$addon_price         = 10;
		$cart_item['addons'] = array( array( 'price' => $addon_price ) );

		$filtered_cart_item = $product_addons->filter_booking_addon_product_in_cart_contents( $cart_item );
		$this->assertEquals( $product_price + $addon_price, $filtered_cart_item['data']->get_price() );
	}

	public function raw_price_amount( $amount ) {
		return $amount / $this->rate;
	}

	function tearDown() {
		parent::tearDown();
		remove_filter( 'wcml_raw_price_amount', array( $this, 'raw_price_amount' ) );
		$this->settings_helper->set_taxonomy_not_translatable( 'product_cat' );
	}
}
