<?php

/**
 * Class Test_WCML_Bookings
 */
class Test_WCML_Bookings extends WCML_UnitTestCase {

	private $bookings;
	private $currencies;
	private $usd_code;
	private $euro_code;
	private $default_language;
	private $second_language;
	private $usd_price;
	private $eur_price;
	public $sitepress;
	public $wpdb;
	private $booking_term;
	private $booking_term_tranlsation;
	private $tp;

	function setUp() {
		global $sitepress, $wpdb;
		if ( ! class_exists( 'WC_Bookings' ) ) {
			$this->markTestSkipped(
				'The WC Bookings extension is not loaded.'
			);
		}
		parent::setUp();

		// Product type
		if ( ! get_term_by( 'slug', sanitize_title( 'booking' ), 'product_type' ) ) {
			wp_insert_term( 'booking', 'product_type' );
		}

		$this->usd_code  = 'USD';
		$this->euro_code = 'EUR';
		$this->usd_price = random_int( 1, 999 );
		$this->eur_price = random_int( 1, 999 );
		$this->default_language = $this->sitepress->get_default_language();
		$active_languages = $this->sitepress->get_active_languages();
		unset( $active_languages[ $this->default_language ] );
		$this->second_language = array_rand( $active_languages );

		$this->currencies = array(
			$this->usd_code  => array( 'languages' => array( $this->default_language, $this->second_language ) ),
			$this->euro_code => array( 'languages' => array( $this->default_language, $this->second_language ) ),
		);


		wpml_test_reg_custom_post_type( 'bookable_person', true );
		wpml_test_reg_custom_post_type( 'bookable_resource', true );
		wpml_test_reg_custom_post_type( 'wc_booking', true );

		$this->sitepress = $sitepress;
		$this->wpdb = $wpdb;
		$this->tp = new WPML_Element_Translation_Package;
		$this->booking_term = get_term_by( 'name', 'booking', 'product_type', ARRAY_A );
		$trid = $this->sitepress->get_element_trid( $this->booking_term['term_id'], 'tax_product_type' );
		$this->booking_term_tranlsation = wpml_test_insert_term( $this->second_language, 'product_type', $trid, 'booking' . $this->second_language );
	}

	/**
	 * @test
	 */
	public function save_custom_costs() {
		$product_id = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$_POST['_wcml_custom_costs_nonce'] = wp_create_nonce( 'wcml_save_custom_costs' );

		$woocommerce_wpml = $this->get_test_subject();

		$woocommerce_wpml->products
			->method( 'get_original_product_id' )
			->willReturn( $product_id );

		$this->bookings = $this->get_wcml_booking_object( $woocommerce_wpml );

		// Check bail out.
		$_POST['_wcml_custom_costs'] = 1;
		$this->assertFalse( $this->bookings->save_custom_costs( 0 ) );

		// Check with disabled custom costs.
		$_POST['_wcml_custom_costs'] = 0;
		$this->bookings->save_custom_costs( $product_id );
		$this->assertEquals( '0', get_post_meta( $product_id, '_wcml_custom_costs_status', true ) );
		$this->assertEquals( '', get_post_meta( $product_id, '_wc_booking_pricing', true ) );

		// Enable custom costs.
		$_POST['_wcml_custom_costs'] = 1;

		$this->check_update_booking_costs( $product_id );
		$this->check_update_booking_pricing( $product_id );
		$this->check_update_booking_person_cost( $product_id );
		$this->check_update_booking_person_block_cost( $product_id );
		$this->check_update_booking_resource_cost( $product_id );
		$this->check_update_booking_resource_block_cost( $product_id );
		unset( $_POST['_wcml_custom_costs'] );
		unset( $_POST['_wcml_custom_costs_nonce'] );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_costs`
	 * @param $product_id
	 */
	private function check_update_booking_costs( $product_id ) {
		foreach ( $this->currencies as $currency_code => $currency_data ) {
			$_POST['wcml_wc_booking_cost'][ $currency_code ] = 100;
			$_POST['wcml_wc_booking_base_cost'][ $currency_code ] = 101;
			$_POST['wcml_wc_display_cost'][ $currency_code ] = 102;
		}

		$this->bookings->save_custom_costs( $product_id );
		$this->assertEquals( '1', get_post_meta( $product_id, '_wcml_custom_costs_status', true ) );

		foreach ( $this->currencies as $currency_code => $currency_data ) {
			$this->assertEquals( '100', get_post_meta( $product_id, '_wc_booking_cost_' . $currency_code, true ) );
			$this->assertEquals( '101', get_post_meta( $product_id, '_wc_booking_base_cost_' . $currency_code, true ) );
			$this->assertEquals( '102', get_post_meta( $product_id, '_wc_display_cost_' . $currency_code, true ) );
		}
		unset( $_POST['wcml_wc_booking_cost'] );
		unset( $_POST['wcml_wc_booking_base_cost'] );
		unset( $_POST['wcml_wc_display_cost'] );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_pricing`
	 * @param $product_id
	 */
	private function check_update_booking_pricing( $product_id ) {
		$dummy_bookings = array(
			'test_booking_key' => array(
				'base_cost_' . $this->usd_code  => 100,
				'cost_' . $this->usd_code       => 101,
				'base_cost_' . $this->euro_code => 102,
				'cost_' . $this->euro_code      => 103,
			),
		);
		add_post_meta( $product_id, '_wc_booking_pricing', $dummy_bookings );
		// Booking pricing costs part. Handled by
		foreach ( $this->currencies as $currency_code => $currency_data ) {
			$_POST['wcml_wc_booking_pricing_base_cost'][ $currency_code ]['test_booking_key'] = 900;
			$_POST['wcml_wc_booking_pricing_cost'][ $currency_code ]['test_booking_key'] = 901;
		}

		$this->bookings->save_custom_costs( $product_id );
		$result = get_post_meta( $product_id, '_wc_booking_pricing', true );
		$this->assertTrue( isset( $result['test_booking_key'] ) );

		foreach ( $this->currencies as $currency_code => $currency_data ) {
			$this->assertEquals( 900, $result['test_booking_key'][ 'base_cost_' . $currency_code ] );
			$this->assertEquals( 901, $result['test_booking_key'][ 'cost_' . $currency_code ] );
		}
		unset( $_POST['wcml_wc_booking_pricing_base_cost'] );
		unset( $_POST['wcml_wc_booking_pricing_cost'] );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_person_cost`
	 * @param $product_id
	 */
	private function check_update_booking_person_cost( $product_id ) {
		unset( $_POST['_wcml_custom_costs'] );
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$_POST['wcml_wc_booking_person_cost'] = array(
			$bookable_person => array(
				$this->usd_code  => $this->usd_price,
				$this->euro_code => $this->eur_price,
			),
		);

		$_POST['_wcml_custom_costs'] = 1;
		$this->bookings->save_custom_costs( $product_id );

		$this->assertEquals( $this->usd_price, get_post_meta( $bookable_person, 'cost_' . $this->usd_code, true ) );
		$this->assertEquals( $this->eur_price, get_post_meta( $bookable_person, 'cost_' . $this->euro_code, true ) );

		unset( $_POST['wcml_wc_booking_person_cost'] );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_person_block_cost`
	 * @param $product_id
	 */
	private function check_update_booking_person_block_cost( $product_id ) {
		unset( $_POST['_wcml_custom_costs'] );
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$_POST['wcml_wc_booking_person_block_cost'] = array(
			$bookable_person => array(
				$this->usd_code  => $this->usd_price,
				$this->euro_code => $this->eur_price,
			),
		);

		// Enable custom costs.
		$_POST['_wcml_custom_costs'] = 1;

		$this->bookings->save_custom_costs( $product_id );

		$this->assertEquals( $this->usd_price, get_post_meta( $bookable_person, 'block_cost_' . $this->usd_code, true ) );
		$this->assertEquals( $this->eur_price, get_post_meta( $bookable_person, 'block_cost_' . $this->euro_code, true ) );

		unset( $_POST['wcml_wc_booking_person_block_cost'] );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_resource_cost`
	 * @param $product_id
	 */
	private function check_update_booking_resource_cost( $product_id ) {
		$resource_id = random_int( 90, 999 );
		$_POST['wcml_wc_booking_resource_cost'] = array(
			$resource_id => array(
				$this->usd_code  => $this->usd_price,
				$this->euro_code => $this->eur_price,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$custom_costs = get_post_meta( $product_id, '_resource_base_costs', true );
		$this->assertTrue( isset( $custom_costs['custom_costs'] ) );

		$this->assertEquals( $this->usd_price, $custom_costs['custom_costs'][ $this->usd_code ][ $resource_id ] );
		$this->assertEquals( $this->eur_price, $custom_costs['custom_costs'][ $this->euro_code ][ $resource_id ] );

		unset( $_POST['wcml_wc_booking_resource_cost'] );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_resource_block_cost`
	 * @param $product_id
	 */
	private function check_update_booking_resource_block_cost( $product_id ) {
		$resource_id = random_int( 90, 999 );
		$_POST['wcml_wc_booking_resource_block_cost'] = array(
			$resource_id => array(
				$this->usd_code  => 100,
				$this->euro_code => 101,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$custom_costs = get_post_meta( $product_id, '_resource_block_costs', true );
		$this->assertTrue( isset( $custom_costs['custom_costs'] ) );

		$this->assertEquals( 100, $custom_costs['custom_costs'][ $this->usd_code ][ $resource_id ] );
		$this->assertEquals( 101, $custom_costs['custom_costs'][ $this->euro_code ][ $resource_id ] );

		unset( $_POST['wcml_wc_booking_resource_block_cost'] );
	}

	private function get_test_subject( ) {
		$woocommerce_wpml = $this->get_wcml_mock();
		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();

		$woocommerce_wpml->multi_currency
			->method( 'get_currencies' )
			->willReturn( $this->currencies );

		$woocommerce_wpml->multi_currency
			->method( 'get_client_currency' )
			->willReturn( 'USD' );

		$woocommerce_wpml->products = $this->get_wcml_products_mock();
		$woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->willReturn( $this->default_language );

		$woocommerce_wpml->multi_currency->prices = $this->get_wcml_multi_currency_prices_mock();

		return $woocommerce_wpml;
	}

	/**
	 * Requires bookings plugin integration to avoid SQL issues.
	 * @test
	 */
	public function save_resource_translation() {
		$post_name = random_string();
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$bookable_resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, random_string() );
		$data = array(
			array(
				'finished'   => true,
				'field_type' => 'wc_bookings:resource:' . $bookable_resource . ':name',
				'data'       => $post_name,
			),
		);
		$job = new stdClass();
		$job->language_code = $this->second_language;

		$bookings = $this->get_wcml_booking_object();
		$relationship = array(
			'product_id'    => $product,
			'resource_id'   => $bookable_resource,
			'sort_order'    => 'ASC',
		);
		$this->wpdb->insert( $this->wpdb->prefix . 'wc_booking_relationships',  $relationship );
		$bookings->save_resource_translation( $product, $data, $job );
	}

	/**
	 * @test
	 */
	public function sync_resource_costs() {
		$product1 = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$product2 = wpml_test_insert_post( $this->second_language, 'product', false, random_string() );
		$bookable_resource1 = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, random_string() );
		$bookings = $this->get_wcml_booking_object();
		$expected = array(
			'custom_costs' => array(
				'custom_costs' => array(
					$this->default_language => array(
						$bookable_resource1 => array(
							$this->usd_code  => $this->usd_price,
							$this->euro_code => $this->eur_price,
						),
					),
				),
			),
			$bookable_resource1 => array(
				$this->usd_code  => $this->usd_price,
				$this->euro_code => $this->eur_price,
			),
		);
		add_post_meta( $product1, 'base_cost', $expected );

		$bookings->sync_resource_costs( $product1, $product2, 'base_cost', $this->second_language );

		$output = get_post_meta( $product2, 'base_cost', true );
		$trns_resource_id = apply_filters( 'translate_object_id', $bookable_resource1, 'bookable_resource', true, $this->second_language );
		$this->assertTrue( isset( $output['custom_costs'] ) );
		$this->assertTrue( isset( $output['custom_costs'][ $this->default_language ] ) );
		$this->assertTrue( isset( $output['custom_costs'][ $this->default_language ][ $trns_resource_id ] ) );
		$this->assertEquals( $this->usd_price, $output['custom_costs'][ $this->default_language ][ $trns_resource_id ][ $this->usd_code ] );
		$this->assertEquals( $this->eur_price, $output['custom_costs'][ $this->default_language ][ $trns_resource_id ][ $this->euro_code ] );

		$this->assertTrue( isset( $output[ $trns_resource_id ] ) );
		$this->assertEquals( $this->usd_price, $output[ $trns_resource_id ][ $this->usd_code ] );
		$this->assertEquals( $this->eur_price, $output[ $trns_resource_id ][ $this->euro_code ] );
	}

	/**
	 * @test
	 */
	public function sync_persons() {
		$block_cost = random_int( 0, 9999 );
		$cost = random_int( 0, 9999 );
		$min = random_int( 0, 9999 );
		$max = random_int( 0, 9999 );
		$rates[ $this->euro_code ] = random_int( 1, 12 );
		$rates[ $this->usd_code ] = random_int( 1, 12 );
		$woocommerce_wpml = $this->get_test_subject();
		$woocommerce_wpml->settings['enable_multi_currency'] = 2;

		$woocommerce_wpml->multi_currency->prices
			->method( 'convert_price_amount' )
			->willReturnMap( array(
				array(
					$block_cost,
					$this->usd_code,
					$block_cost * $rates[ $this->usd_code ],
				),
				array(
					$block_cost,
					$this->euro_code,
					$block_cost * $rates[ $this->euro_code ],
				),
				array(
					$cost,
					$this->usd_code,
					$cost * $rates[ $this->usd_code ],
				),
				array(
					$cost,
					$this->euro_code,
					$cost * $rates[ $this->euro_code ],
				),
			) );

		$bookings = $this->get_wcml_booking_object( $woocommerce_wpml );
		// Add products.
		$product            = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid               = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translated_product = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );

		// Add persons.
		$bookable_person            = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string(), $product );
		$trid                       = $this->sitepress->get_element_trid( $bookable_person, 'post_bookable_person' );
		$translated_bookable_person = wpml_test_insert_post( $this->second_language, 'bookable_person', $trid, random_string(), $translated_product );

		// Add costs
		update_post_meta( $bookable_person, 'block_cost', $block_cost );
		update_post_meta( $bookable_person, 'cost', $cost );
		update_post_meta( $bookable_person, 'max', $max );
		update_post_meta( $bookable_person, 'min', $min );

		foreach ( $this->currencies as $currency_code => $currency ) {
			update_post_meta( $bookable_person, 'block_cost_' . $currency_code, $rates[ $currency_code ] * $block_cost );
			update_post_meta( $bookable_person, 'cost_' . $currency_code, $rates[ $currency_code ] * $cost );
		}

		$bookings->sync_persons( $product, $translated_product, $this->second_language, false );
		$expected = array(
			'block_cost' => $block_cost,
			'cost'       => $cost,
			'min'        => $min,
			'max'        => $max,
		);
		$this->check_meta_values( $translated_bookable_person, $expected );

		// Enable custom costs for this specific post.
		update_post_meta( $bookable_person, '_wcml_custom_costs_status', true );
		$expected = array();
		foreach ( $this->currencies as $currency_code => $currency ) {
			$expected[ 'block_cost_' . $currency_code ] = $rates[ $currency_code ] * $block_cost;
			$expected[ 'cost_' . $currency_code ] = $rates[ $currency_code ] * $cost;
		}
		$bookings->sync_persons( $product, $translated_product, $this->second_language, false );
		$this->check_meta_values( $translated_bookable_person, $expected );
	}

	/**
	 * Used by `Test_WCML_Bookings::sync_persons`
	 * @param $post_id
	 * @param $expected
	 */
	private function check_meta_values( $post_id, $expected ) {
		foreach ( $expected as $meta_key => $expected_value ) {
			$this->assertEquals( $expected_value, get_post_meta( $post_id, $meta_key, true ) );
		}
	}

	/**
	 * @test
	 */
	public function get_cookie_booking_currency() {
		$bookings = $this->get_wcml_booking_object();
		$_COOKIE['_wcml_booking_currency'] = 'USD';
		$backup = get_option( 'woocommerce_currency', '' );
		update_option( 'woocommerce_currency', 'EUR', 'no' );
		$this->assertEquals( 'USD', $bookings->get_cookie_booking_currency() );
		unset( $_COOKIE['_wcml_booking_currency'] );
		$this->assertEquals( 'EUR', $bookings->get_cookie_booking_currency() );
		update_option( 'woocommerce_currency', $backup );
	}

	/**
	 * @test
	 */
	public function filter_booking_currency_symbol() {
		global $pagenow;
		$_COOKIE['_wcml_booking_currency'] = 'USD';
		$_GET['page'] = 'create_booking';
		$pagenow = 'edit.php';
		$bookings = $this->get_wcml_booking_object();
		$this->assertEquals( 10, has_filter( 'woocommerce_currency_symbol', array( $bookings, 'filter_booking_currency_symbol' ) ) );
		$this->assertEquals( '&#36;', $bookings->filter_booking_currency_symbol( $_COOKIE['_wcml_booking_currency'] ) );
		unset( $_COOKIE['_wcml_booking_currency'] );
		$this->assertEquals( 'test', $bookings->filter_booking_currency_symbol( 'test' ) );
		$_COOKIE['_wcml_booking_currency'] = random_string();
		$this->assertEquals( '', $bookings->filter_booking_currency_symbol( $_COOKIE['_wcml_booking_currency'] ) );
		unset( $_GET['page'] );
	}

	/**
	 * @test
	 */
	public function custom_box_html_data() {
		$bookings = $this->get_wcml_booking_object();
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translation = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );
		$translation = get_post( $translation );

		// If product is not bookable.
		$this->assertEquals( 'this is data', $bookings->custom_box_html_data( 'this is data', $product, $translation, $this->second_language ) );

		// Add booking term
		wp_set_post_terms( $product, array( (int) $this->booking_term['term_id'] ), 'product_type' );
		wp_set_post_terms( $translation->ID, array( (int) $this->booking_term_tranlsation['term_id'] ), 'product_type' );

		$this->add_and_check_resource_labels( $bookings, $product, $translation );
		$this->add_and_check_original_resources( $bookings, $product, $translation );
		$this->add_and_check_original_persons( $bookings, $product, $translation );
	}

	/**
	 * @param $bookings
	 * @param $product
	 * @param $translation
	 */
	private function add_and_check_resource_labels( $bookings, $product, $translation ) {
		update_post_meta( $product, '_wc_booking_has_resources', 'yes' );
		update_post_meta( $product, '_wc_booking_resouce_label', $this->default_language . '_wc_booking_resouce_label' );
		update_post_meta( $translation->ID, '_wc_booking_resouce_label', $this->second_language . '_wc_booking_resouce_label' );

		$output = $bookings->custom_box_html_data( array(), $product, $translation, $this->second_language );

		$this->assertEquals( $this->default_language . '_wc_booking_resouce_label', $output['_wc_booking_resouce_label']['original'] );
		$this->assertEquals( $this->second_language . '_wc_booking_resouce_label', $output['_wc_booking_resouce_label']['translation'] );
	}

	/**
	 * @param $bookings
	 * @param $product
	 * @param $translation
	 */
	private function add_and_check_original_resources( $bookings, $product, $translation ) {
		$resource_cost = random_int( 1, 999 );
		$resource_title = random_string();
		$bookable_resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, $this->default_language . $resource_title );
		$trid = $this->sitepress->get_element_trid( $bookable_resource, 'post_bookable_resource' );
		wpml_test_insert_post( $this->second_language, 'bookable_resource', $trid, $this->second_language . $resource_title );

		$resource_data = array(
			$bookable_resource => $resource_cost,
			'custom_costs'     => array(),
		);

		update_post_meta( $product, '_resource_base_costs', $resource_data );
		$output = $bookings->custom_box_html_data( array(), $product, $translation, $this->second_language );
		$this->assertEquals( $this->default_language . $resource_title, $output[ 'bookings-resource_' . $bookable_resource . '_title' ]['original'] );
		$this->assertEquals( $this->second_language . $resource_title, $output[ 'bookings-resource_' . $bookable_resource . '_title' ]['translation'] );
	}

	private function add_and_check_original_persons( $bookings, $product, $translation ) {
		$excerpt = random_string();
		$resource_title = random_string();
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, $this->default_language . $resource_title, $product );
		$trid = $this->sitepress->get_element_trid( $bookable_person, 'post_bookable_person' );
		$bookable_person_tr = wpml_test_insert_post( $this->second_language, 'bookable_person', $trid, $this->second_language . $resource_title, $translation->ID );

		wp_update_post(
			array(
				'ID'           => $bookable_person,
				'post_excerpt' => $this->default_language . $excerpt,
			)
		);

		wp_update_post(
			array(
				'ID'           => $bookable_person_tr,
				'post_excerpt' => $this->second_language . $excerpt,
			)
		);

		$output = $bookings->custom_box_html_data( array(), $product, $translation, $this->second_language );
		$this->assertEquals( $this->default_language . $resource_title, $output[ 'bookings-person_' . $bookable_person . '_title' ]['original'] );
		$this->assertEquals( $this->second_language . $resource_title, $output[ 'bookings-person_' . $bookable_person . '_title' ]['translation'] );
		$this->assertEquals( $this->default_language . $excerpt, $output[ 'bookings-person_' . $bookable_person . '_description' ]['original'] );
		$this->assertEquals( $this->second_language . $excerpt, $output[ 'bookings-person_' . $bookable_person . '_description' ]['translation'] );
	}

	/**
	 * @test
	 */
	public function duplicate_person() {
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid  = $this->sitepress->get_element_trid( $product, 'post_product' );
		$tr_product = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );

		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );

		$bookings = $this->get_wcml_booking_object();
		$tr_person = $bookings->duplicate_person( $tr_product, $bookable_person, $this->second_language );
		$this->duplicate_person_checks( $tr_person, $tr_product );

		$empty_class = new stdClass();
		$bookings = $this->get_wcml_booking_object( );
		$tr_person = $bookings->duplicate_person( $tr_product, $bookable_person, $this->second_language );
		$this->duplicate_person_checks( $tr_person, $tr_product );
	}

	private function duplicate_person_checks( $tr_person, $tr_product ) {
		$tr_person_post = get_post( $tr_person );
		$this->assertEquals( '', get_post_meta( $tr_person, '_icl_lang_duplicate_of', true ) );
		$this->assertEquals( $tr_product, $tr_person_post->post_parent );
		$lang_details = $this->sitepress->get_element_language_details( $tr_person, 'post_bookable_person' );
		$this->assertEquals( $this->second_language, $lang_details->language_code );
		$this->assertEquals( $this->default_language, $lang_details->source_language_code );
	}

	/**
	 * @test
	 */
	public function filter_pricing_cost() {
		$woocommerce_wpml = $this->get_test_subject();
		$woocommerce_wpml->settings['enable_multi_currency'] = 1;
		$block_cost = random_int( 1, 9999 );
		$cost = random_int( 1, 9999 );
		$rates[ $this->usd_code ] = random_int( 1, 9999 );
		$rates[ $this->euro_code ] = random_int( 1, 9999 );
		$woocommerce_wpml->multi_currency->prices
			->method( 'convert_price_amount' )
			->willReturnMap( array(
				array(
					$block_cost,
					$this->usd_code,
					$block_cost * $rates[ $this->usd_code ],
				),
				array(
					$block_cost,
					$this->euro_code,
					$block_cost * $rates[ $this->euro_code ],
				),
				array(
					$cost,
					$this->usd_code,
					$cost * $rates[ $this->usd_code ],
				),
				array(
					$cost,
					$this->euro_code,
					$cost * $rates[ $this->euro_code ],
				),
			) );

		$bookings = $this->get_wcml_booking_object( $woocommerce_wpml );
		$this->assertEquals( $cost, $bookings->filter_pricing_cost( $cost, 'filed', 'name', 'name_key' ) );

		$woocommerce_wpml->settings['enable_multi_currency'] = 2;
		$bookings = $this->get_wcml_booking_object( $woocommerce_wpml );
		$this->assertEquals( $block_cost, $bookings->filter_pricing_cost( $cost, array( 'cost_USD' => $block_cost, 'modifier' => '' ), 'cost_', 'name_key' ) );

		$bookings = $this->get_wcml_booking_object( $woocommerce_wpml );
		$this->assertEquals( $cost * $rates[ $this->usd_code ], $bookings->filter_pricing_cost( $cost, array( 'modifier' => '' ), 'cost_', 'name_key' ) );
		$this->assertEquals( $block_cost * $rates[ $this->usd_code ], $bookings->filter_pricing_cost( $block_cost, array( 'base_modifier' => '' ), 'base_cost_', 'name_key' ) );

		$product = $this->create_bookable_product( $this->default_language );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translation = $this->create_bookable_product( $this->second_language, $trid );

		$woocommerce_wpml->products
			->method( 'get_original_product_id' )
			->willReturn( $product );

		$bookings = new WCML_Bookings( $this->sitepress, $woocommerce_wpml, $this->wpdb, $this->tp );
		$this->assertEquals( $cost * $rates[ $this->usd_code ], $bookings->filter_pricing_cost( $cost, array( 'modifier' => '' ), 'cost_', 'name_key' ) );
		$this->assertEquals( $block_cost * $rates[ $this->usd_code ], $bookings->filter_pricing_cost( $block_cost, array( 'base_modifier' => '' ), 'base_cost_', 'name_key' ) );

		update_post_meta( $product, '_wc_booking_pricing', array( 'name_key' => array( 'cost_USD' => $cost * 1.1, 'modifier' => '' ) ) );

		$_POST['add-to-cart'] = $translation;
		$this->assertEquals( $cost * 1.1, $bookings->filter_pricing_cost( $cost, array( 'modifier' => '' ), 'cost_', 'name_key' ) );
		unset( $_POST['add-to-cart'] );
	}

	/**
	 * @test
	 */
	public function get_translated_booking_product_id() {
		$bookings = $this->get_wcml_booking_object();

		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$product = $this->create_bookable_product( $this->default_language );
		update_post_meta( $wc_booking, '_booking_product_id', $product );

		$this->assertEquals( '', $bookings->get_translated_booking_product_id( $wc_booking, $this->second_language ) );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translation = $this->create_bookable_product( $this->second_language, $trid );
		$this->assertEquals( $translation, $bookings->get_translated_booking_product_id( $wc_booking, $this->second_language ) );
	}

	/**
	 * @test
	 */
	public function get_translated_booking_resource_id() {
		$bookings = $this->get_wcml_booking_object();

		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$bookable_resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, random_string() );
		update_post_meta( $wc_booking, '_booking_resource_id', $bookable_resource );

		$this->assertEquals( '', $bookings->get_translated_booking_resource_id( $wc_booking, $this->second_language ) );
		$trid = $this->sitepress->get_element_trid( $bookable_resource, 'post_bookable_resource' );
		$translation = wpml_test_insert_post( $this->second_language, 'bookable_resource', $trid, random_string() );
		$this->assertEquals( $translation, $bookings->get_translated_booking_resource_id( $wc_booking, $this->second_language ) );
	}

	/**
	 * @test
	 */
	public function get_translated_booking_persons_ids() {
		$bookings = $this->get_wcml_booking_object();

		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$bookable_person1 = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$bookable_person1_count = random_int( 1, 999 );
		$bookable_person2 = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$bookable_person2_count = random_int( 1, 999 );
		$trid = $this->sitepress->get_element_trid( $bookable_person2, 'post_bookable_person' );
		$tr_bookable_person2 = wpml_test_insert_post( $this->second_language, 'bookable_person', $trid, random_string() );

		update_post_meta( $wc_booking, '_booking_persons', array( $bookable_person1 => $bookable_person1_count, $bookable_person2 => $bookable_person2_count ) );

		$output = $bookings->get_translated_booking_persons_ids( $wc_booking, $this->second_language );

		$this->assertEquals( $bookable_person1_count, $output[0] );
		$this->assertEquals( $bookable_person2_count, $output[ $tr_bookable_person2 ] );
	}

	/**
	 * @test
	 */
	public function update_status_for_translations() {
		$bookings = $this->get_wcml_booking_object();

		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string(), $product );
		$trid = $this->sitepress->get_element_trid( $wc_booking, 'post_wc_booking' );
		$tr_wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', $trid, random_string(), $product );
		$this->assertEquals( 'publish', get_post_status( $tr_wc_booking ) );
		wp_update_post(
			array(
				'ID'          => $wc_booking,
				'post_status' => 'draft',
			)
		);
		update_post_meta( $tr_wc_booking, '_booking_duplicate_of', $wc_booking );
		update_post_meta( $tr_wc_booking, '_language_code', $this->second_language );
		$bookings->update_status_for_translations( $wc_booking );
		wp_cache_init();
		$this->assertEquals( 'draft', get_post_status( $tr_wc_booking ) );
	}

	/**
	 * @test
	 */
	public function booking_filters_query() {
		$query = new stdClass();
		$query->query_vars = array();
		$query->query_vars['post_type'] = 'wc_booking';

		$bookings = $this->get_wcml_booking_object();

		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$product1 = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$product2 = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$output = $bookings->booking_filters_query( $query );
		$expected = array(
			'relation' => 'OR',
			array(
				'key'   => '_language_code',
				'value' => $this->default_language,
				'compare ' => '=',
			),
			array(
				'key'   => '_booking_product_id',
				'value' => array( $product, $product1, $product2 ),
				'compare ' => 'IN',
			)
		);

		$this->assertEquals( $expected, $output->query_vars['meta_query'][0] );
	}

	/**
	 * @test
	 */
	public function bookings_in_date_range_query() {
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$tr_product = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );

		$wc_booking  = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string(), $product );
		update_post_meta( $wc_booking, '_booking_product_id', $product );
		$wc_booking1  = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string(), $product );
		update_post_meta( $wc_booking1, '_booking_product_id', $tr_product );

		$bookings = $this->get_wcml_booking_object();
		$output = $bookings->bookings_in_date_range_query( array( $wc_booking, $wc_booking1 ) );
		$expected = array( $wc_booking );

		$this->assertEquals( $expected, $output );
	}

	/**
	 * @test
	 */
	public function delete_bookings() {
		$bookings = $this->get_wcml_booking_object();

		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $wc_booking, 'post_wc_booking' );
		$tr_wc_booking = wpml_test_insert_post( $this->second_language, 'wc_booking', $trid, random_string() );
		update_post_meta( $tr_wc_booking, '_booking_duplicate_of', $wc_booking );

		$wc_booking1 = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $wc_booking1, 'post_wc_booking' );
		$tr_wc_booking1 = wpml_test_insert_post( $this->second_language, 'wc_booking', $trid, random_string() );

		$bookings->delete_bookings( $wc_booking );
		$this->assertEquals( null, get_post( $tr_wc_booking ) );
		$bookings->delete_bookings( $wc_booking1 );
		$this->assertTrue( get_post( $tr_wc_booking1 ) instanceof WP_Post );
	}

	/**
	 * @test
	 */
	public function trash_bookings() {
		$bookings = $this->get_wcml_booking_object();

		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $wc_booking, 'post_wc_booking' );
		$tr_wc_booking = wpml_test_insert_post( $this->second_language, 'wc_booking', $trid, random_string() );
		update_post_meta( $tr_wc_booking, '_booking_duplicate_of', $wc_booking );

		$wc_booking1 = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $wc_booking1, 'post_wc_booking' );
		$tr_wc_booking1 = wpml_test_insert_post( $this->second_language, 'wc_booking', $trid, random_string() );

		$bookings->trash_bookings( $wc_booking );
		wp_cache_init();
		$this->assertEquals( 'trash', get_post_status( $tr_wc_booking ) );
		$bookings->trash_bookings( $wc_booking1 );
		wp_cache_init();
		$this->assertEquals( 'publish', get_post_status( $tr_wc_booking1 ) );
	}

	/**
	 * @test
	 */
	public function save_person_translation() {
		$name                = random_string();
		$product             = $this->create_bookable_product( $this->default_language );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$tr_product             = $this->create_bookable_product( $this->second_language, $trid );

		$bookable_person     = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );

		$bookable_person1    = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $bookable_person1, 'post_bookable_person' );
		$tr_bookable_person1 = wpml_test_insert_post( $this->second_language, 'bookable_person', $trid, random_string() );

		$data = array(
			array(
				'finished'   => true,
				'field_type' => 'wc_bookings:person:' . $bookable_person . ':name',
				'data'       => $name,
			),
			array(
				'finished'   => true,
				'field_type' => 'wc_bookings:person:' . $bookable_person1 . ':name',
				'data'       => $name,
			),
			array(
				'finished'   => false,
				'field_type' => 'wc_bookings:person:' . $bookable_person . ':name',
				'data'       => random_string(),
			),
			array(
				'finished'   => true,
				'field_type' => $bookable_person . ':name',
				'data'       => random_string(),
			),
		);
		$job = new stdClass();
		$job->language_code = $this->second_language;
		$bookings = $this->get_wcml_booking_object();
		// No idea why, but without these 2 lines `translate_object_id` filter returns original post ID, even if 3rd arugment is false.
		remove_all_filters( 'translate_object_id' );
		add_filter( 'translate_object_id', 'icl_object_id', 10, 4 );

		$bookings->save_person_translation( $tr_product, $data, $job );
		wp_cache_init();

		// After save_person_translation is called translation will have name specified in job data above.
		$this->assertEquals( $name, get_the_title( $tr_bookable_person1 ) );

		$person_id_translated = apply_filters( 'translate_object_id', $bookable_person, 'bookable_person', false, $this->second_language );
		$this->assertEquals( $tr_product, wp_get_post_parent_id( $person_id_translated ) );
		$this->assertEquals( $name, get_the_title( $person_id_translated ) );
	}

	/**
	 * @test
	 */
	public function duplicate_booking_for_translations() {
		$booking_title = random_string();
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, $booking_title, $product );
		update_post_meta( $wc_booking, '_booking_product_id', $product );
		$bookings = $this->get_wcml_booking_object();
		$meta_data = array(
			'_booking_order_item_id' => 0,
			'_booking_cost'          => random_int( 1, 999 ),
			'_booking_start'         => random_int( 1, 999 ),
			'_booking_end'           => random_int( 1, 999 ),
			'_booking_all_day'       => random_int( 1, 999 ),
			'_booking_parent_id'     => $product,
			'_booking_customer_id'   => random_int( 1, 999 ),
			'_booking_duplicate_of'  => $wc_booking,
			'_language_code'         => $this->second_language,
		);

		foreach ( $meta_data as $key => $value ) {
			update_post_meta( $wc_booking, $key, $value );
		}

		$bookings->duplicate_booking_for_translations( $wc_booking, $this->second_language );
		$tr_wc_booking = apply_filters( 'translate_object_id', $wc_booking, 'wc_booking', false, $this->second_language );
		foreach ( $meta_data as $key => $value ) {
			$this->assertEquals( $value, get_post_meta( $tr_wc_booking, $key, true ) );
		}
	}

	/**
	 * @test
	 */
	public function wcml_js_lock_fields_ids() {
		$expected = array(
			'_wc_booking_has_resources',
			'_wc_booking_has_persons',
			'_wc_booking_duration_type',
			'_wc_booking_duration',
			'_wc_booking_duration_unit',
			'_wc_booking_calendar_display_mode',
			'_wc_booking_requires_confirmation',
			'_wc_booking_user_can_cancel',
			'_wc_accommodation_booking_min_duration',
			'_wc_accommodation_booking_max_duration',
			'_wc_accommodation_booking_max_duration',
			'_wc_accommodation_booking_calendar_display_mode',
			'_wc_accommodation_booking_requires_confirmation',
			'_wc_accommodation_booking_user_can_cancel',
			'_wc_accommodation_booking_cancel_limit',
			'_wc_accommodation_booking_cancel_limit_unit',
			'_wc_accommodation_booking_qty',
			'_wc_accommodation_booking_min_date',
			'_wc_accommodation_booking_min_date_unit',
			'_wc_accommodation_booking_max_date',
			'_wc_accommodation_booking_max_date_unit',
			'bookings_pricing select',
			'bookings_resources select',
			'bookings_availability select',
			'bookings_persons input[type="checkbox"]',
		);
		$bookings = $this->get_wcml_booking_object();
		$this->assertEquals( $expected, $bookings->wcml_js_lock_fields_ids( array() ) );
	}

	/**
	 * @test
	 */
	public function append_persons_to_translation_package() {
		set_current_screen( 'admin' );
		$tp = new WPML_Element_Translation_Package;
		$bookings = $this->get_wcml_booking_object();

		$product = $this->create_bookable_product( $this->default_language );
		$product_obj = get_post( $product );

		$this->assertEquals( array(), $bookings->append_persons_to_translation_package( array(), $product_obj ) );
		$person_title = random_string();
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, $person_title, $product );

		$bookable_person = get_post( $bookable_person );
		$expected = array(
			'contents' => array(
				'wc_bookings:person:' . $bookable_person->ID . ':name' => array(
					'translate' => 1,
					'data'      => $tp->encode_field_data( $bookable_person->post_title, 'base64' ),
					'format'    => 'base64',
				),
				'wc_bookings:person:' . $bookable_person->ID . ':description' => array(
					'translate' => 1,
					'data'      => $tp->encode_field_data( $bookable_person->post_excerpt, 'base64' ),
					'format'    => 'base64',
				),
			),
		);
		$this->assertEquals( $expected, $bookings->append_persons_to_translation_package( array(), $product_obj ) );

		set_current_screen( 'front' );
	}

	/**
	 * @test
	 */
	public function append_resources_to_translation_package() {
		set_current_screen( 'admin' );
		$tp = new WPML_Element_Translation_Package;
		$bookings = $this->get_wcml_booking_object();

		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$product_obj = get_post( $product );
		wp_set_post_terms( $product, array( (int) $this->booking_term['term_id'] ), 'product_type' );

		$this->assertEquals( array(), $bookings->append_resources_to_translation_package( array(), $product_obj ) );
		$person_title = random_string();
		$bookable_resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, $person_title, $product );
		$this->wpdb->insert(
			$this->wpdb->prefix . 'wc_booking_relationships',
			array(
				'resource_id' => $bookable_resource,
				'product_id'  => $product,
				'sort_order'  => 'ASC',
			),
			array(
				'%d',
				'%d',
				'%s',
			)
		);
		update_post_meta( $product, '_wc_booking_has_resources', 'yes' );
		$bookable_resource = get_post( $bookable_resource );
		$expected = array(
			'contents' => array(
				'wc_bookings:resource:' . $bookable_resource->ID . ':name' => array(
					'translate' => 1,
					'data'      => $tp->encode_field_data( $bookable_resource->post_title, 'base64' ),
					'format'    => 'base64',
				),
			),
		);
		$this->assertEquals( $expected, $bookings->append_resources_to_translation_package( array(), $product_obj ) );
		set_current_screen( 'front' );
	}

	/**
	 * @test
	 */
	public function sync_bookings() {
		$wc_booking = wpml_test_insert_post( $this->default_language, 'wc_booking', false, random_string() );
		$product = $this->create_bookable_product( $this->default_language );
		update_post_meta( $wc_booking, '_booking_product_id', $product );

		$bookings = $this->get_wcml_booking_object();

		$meta_data = array(
			'_booking_order_item_id' => 0,
			'_booking_cost'          => random_int( 1, 999 ),
			'_booking_start'         => random_int( 1, 999 ),
			'_booking_end'           => random_int( 1, 999 ),
			'_booking_all_day'       => random_int( 1, 999 ),
			'_booking_parent_id'     => $product,
			'_booking_customer_id'   => random_int( 1, 999 ),
			'_language_code'         => $this->second_language,
		);

		foreach ( $meta_data as $key => $value ) {
			update_post_meta( $wc_booking, $key, $value );
		}

		$bookings->sync_bookings( $product, null, $this->second_language );

		$tr_wc_booking = apply_filters( 'translate_object_id', $wc_booking, 'wc_booking', false, $this->second_language );

		foreach ( $meta_data as $key => $value ) {
			$this->assertEquals( $value, get_post_meta( $tr_wc_booking, $key, true ) );
		}

		$this->assertEquals( $wc_booking, get_post_meta( $tr_wc_booking, '_booking_duplicate_of', true ) );
	}

	/**
	 * @test
	 */
	public function sync_booking_data() {
		$product = $this->create_bookable_product( $this->default_language );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translation = $this->create_bookable_product( $this->second_language, $trid );
		$bookings = $this->get_wcml_booking_object();
		// Add resource.
		$person_title = random_string();
		$bookable_resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, $person_title, $product );
		$trid = $this->sitepress->get_element_trid( $bookable_resource, 'post_bookable_resource' );
		$resource_translation = wpml_test_insert_post( $this->second_language, 'bookable_resource', $trid, $person_title, $translation );
		$bookable_resource1 = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, $person_title, $product );

		update_post_meta( $bookable_resource1, 'dummy_key', 'dummy_value' );

		$qty = random_int( 1, 999 );
		$available = random_int( 1, 999 );
		update_post_meta( $bookable_resource, 'qty', $qty );
		update_post_meta( $bookable_resource, '_wc_booking_availability', $available );
		$this->wpdb->insert(
			$this->wpdb->prefix . 'wc_booking_relationships',
			array( 'resource_id' => $bookable_resource, 'product_id'  => $product, 'sort_order'  => '1', ),
			array( '%d', '%d', '%s', )
		);
		$this->wpdb->insert(
			$this->wpdb->prefix . 'wc_booking_relationships',
			array( 'resource_id' => $resource_translation, 'product_id'  => $translation, 'sort_order'  => '0', ),
			array( '%d', '%d', '%s', )
		);
		$this->wpdb->insert(
			$this->wpdb->prefix . 'wc_booking_relationships',
			array( 'resource_id' => $bookable_resource1, 'product_id'  => $product, 'sort_order'  => '0', ),
			array( '%d', '%d', '%s', )
		);
		update_post_meta( $product, '_wc_booking_has_resources', 'yes' );

		$bookings->sync_booking_data( $product, null );
		$this->assertEquals( $qty, get_post_meta( $resource_translation, 'qty', true ) );
		$this->assertEquals( $available, get_post_meta( $resource_translation, '_wc_booking_availability', true ) );
		$sort_order = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT sort_order FROM {$this->wpdb->prefix}wc_booking_relationships WHERE resource_id = %d AND product_id = %d",
				$resource_translation,
				$translation
			)
		);
		$this->assertEquals( '1', $sort_order[0]->sort_order );

		// Check if resource duplicated.
		$trns_resource_id = apply_filters( 'translate_object_id', $bookable_resource1, 'bookable_resource', true, $this->second_language );
		$sort_order = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT sort_order, resource_id FROM {$this->wpdb->prefix}wc_booking_relationships WHERE product_id = %d",
				$translation
			)
		);
		$this->assertEquals( $trns_resource_id, $sort_order[1]->resource_id );
		$this->assertEquals( 'dummy_value', get_post_meta( $trns_resource_id, 'dummy_key', true ) );
	}

	/**
	 * @test
	 *
	 * Given a product in the default language with a resource, add a translated resources to the product translations
	 */
	public function add_product_resource(){

		// products
		$product = $this->create_bookable_product( $this->default_language );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$product_translation = $this->create_bookable_product( $this->second_language, $trid );

		// resources
		$resource_title = random_string();
		$resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, $resource_title, $product );

		$relationship['sort_order'] = random_int(1, 999);
		$relationship['resource_id'] = $resource;
		$relationship['product_id'] = $product;
		$this->wpdb->insert( $this->wpdb->prefix . 'wc_booking_relationships', $relationship );

		$qty = random_int( 1, 999 );
		$available = random_int( 1, 999 );
		update_post_meta( $resource, 'qty', $qty );
		update_post_meta( $resource, '_wc_booking_availability', $available );

		$trid = $this->sitepress->get_element_trid( $resource, 'post_bookable_resource' );
		$resource_translation = wpml_test_insert_post( $this->second_language, 'bookable_resource', $trid, $resource_title );

		$resource_data = new stdClass();
		$resource_data->resource_id = $resource;
		$resource_data->sort_order = $relationship['sort_order'];
		$wcml_bookings_object = $this->get_wcml_booking_object();
		$wcml_bookings_object->add_product_resource( $product_translation, $resource_translation, $resource_data );


		$translation_relationship = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT sort_order, resource_id FROM {$this->wpdb->prefix}wc_booking_relationships WHERE product_id = %d",
				$product_translation
			)
		);

		$this->assertEquals( $translation_relationship->sort_order, $relationship['sort_order'] );
		$this->assertEquals( $translation_relationship->resource_id, $resource_translation );

		$this->assertEquals( $qty, get_post_meta( $resource_translation, 'qty', true ) );
		$this->assertEquals( $available, get_post_meta( $resource_translation, '_wc_booking_availability', true ) );

		$wcml_bookings_object->remove_resource_from_product( $product_translation, $resource_translation );

		$translation_relationship = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT sort_order, resource_id FROM {$this->wpdb->prefix}wc_booking_relationships WHERE product_id = %d",
				$product_translation
			)
		);
		$this->assertNull( $translation_relationship );



	}

	private function create_bookable_product( $lang, $trid = false ) {
		$product = wpml_test_insert_post( $lang, 'product', $trid, random_string() );
		wp_set_post_terms( $product, array( (int) $this->booking_term['term_id'] ), 'product_type' );
		return $product;
	}

	public function get_wcml_booking_object($woocommerce_wpml = false ){

		if( !$woocommerce_wpml) {
			$woocommerce_wpml = $this->get_test_subject();
		}

		$subject = new WCML_Bookings( $this->sitepress, $woocommerce_wpml, $this->wpdb , $this->tp );
		$subject->add_hooks();

		return $subject;
	}

	/**
	 * @test
	 */
	public function filter_wc_booking_cost() {
		$block_cost = random_int( 1, 99999 );
		$cost = random_int( 1, 99999 );
		$check = random_int( 1, 9999 );
		$base_costs = random_int( 1, 9999 );
		$rates = array(
			$this->usd_code  => random_int( 1, 9 ),
			$this->euro_code => random_int( 1, 9 ),
		);
		$woocommerce_wpml = $this->get_test_subject();
		$woocommerce_wpml->settings['enable_multi_currency'] = 2;

		$woocommerce_wpml->multi_currency->prices
			->method( 'convert_price_amount' )
			->willReturnMap( array(
				array(
					$block_cost,
					$this->usd_code,
					$block_cost,
				),
				array(
					$base_costs,
					$this->usd_code,
					$base_costs,
				),
				array(
					(string) $base_costs,
					$this->usd_code,
					$base_costs,
				)
			) );

		$bookings = $this->get_wcml_booking_object( $woocommerce_wpml );
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translation = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );

		$woocommerce_wpml->products
			->method( 'get_original_product_id' )
			->willReturn( $translation );

		$bookings = new WCML_Bookings( $this->sitepress, $woocommerce_wpml, $this->wpdb, $this->tp );
		update_post_meta( $product, '_resource_base_costs', array( 1 => $block_cost ) );
		$output = $bookings->filter_wc_booking_cost( $check, $product, '_resource_base_costs', true );
		$this->assertEquals( $block_cost, $output[0][1] );
		$this->assertEquals( $check, $bookings->filter_wc_booking_cost( $check, $translation, '_resource_base_costs', true ) );

		$woocommerce_wpml = $this->get_test_subject();
		$woocommerce_wpml->settings['enable_multi_currency'] = 2;
		$display_cost = random_int( 1, 9999 );
		$woocommerce_wpml->multi_currency->prices
			->method( 'convert_price_amount' )
			->willReturnMap( array(
				array(
					$display_cost,
					$this->usd_code,
					$display_cost,
				)
			) );
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$woocommerce_wpml->products
			->method( 'get_original_product_id' )
			->willReturn( $product );

		$bookings = new WCML_Bookings( $this->sitepress, $woocommerce_wpml, $this->wpdb, $this->tp);

		update_post_meta( $product, '_wc_display_cost_' . $this->usd_code, $base_costs );
		update_post_meta( $product, '_wcml_custom_costs_status', true );
		$this->assertEquals( $base_costs, $bookings->filter_wc_booking_cost( $check, $product, '_wc_display_cost', true ) );
	}

	/**
	 * @test
	 */
	public function filter_bundled_product_in_cart_contents() {
		$prod_qty = random_int( 1, 9000 );
		$booking = $this->create_bookable_product( $this->default_language );
		$trid = $this->sitepress->get_element_trid( $booking, 'post_product' );
		$translation = $this->create_bookable_product( $this->second_language, $trid );

		update_post_meta( $booking, '_wc_booking_pricing', array( 'base_cost' => random_int( 1, 999 ) ) );
		update_post_meta( $translation, '_wc_booking_pricing', array( 'base_cost' => random_int( 1, 999 ) ) );
		update_post_meta( $booking, '_wc_booking_qty', $prod_qty );
		update_post_meta( $translation, '_wc_booking_qty', $prod_qty );
		$cart_item = array(
			'data'    => new WC_Product_Booking( $booking ),
			'product_id' => $booking,
			'booking' => array(
				'_year'  => random_int( 1900, 2016 ),
				'_month' => random_int( 1, 12 ),
				'_day'   => random_int( 1, 30 ),
				'_qty'   => random_int( 1, 900 ),
			),
		);

		$woocommerce_wpml = $this->get_test_subject();
		$woocommerce_wpml->settings['enable_multi_currency'] = 2;

		$bookings = $this->get_wcml_booking_object( $woocommerce_wpml );
		$output = $bookings->filter_bundled_product_in_cart_contents( $cart_item, null, $this->second_language );

		$this->assertEquals( $translation, $output['data']->get_id() );
		$this->assertEquals( 'booking', $output['data']->get_type() );
		$this->assertEquals( $cart_item['booking']['_year'], $output['booking']['_year'] );
		$this->assertEquals( $cart_item['booking']['_month'], $output['booking']['_month'] );
		$this->assertEquals( $cart_item['booking']['_qty'], $output['booking']['_qty'] );
		$this->assertEquals( $prod_qty, get_post_meta( $output['data']->get_id(), '_wc_booking_qty', true ) );
	}
}
