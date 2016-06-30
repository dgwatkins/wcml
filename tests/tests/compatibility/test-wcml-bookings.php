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

	function setUp() {
		global $sitepress;
		parent::setUp();
		$this->usd_code  = 'usd';
		$this->euro_code = 'eur';
		$this->usd_price = random_int( 1, 999 );
		$this->eur_price = random_int( 1, 999 );
		$this->default_language = $sitepress->get_default_language();
		$active_languages = $sitepress->get_active_languages();
		unset( $active_languages[ $this->default_language ] );
		$this->second_language = array_rand( $active_languages );

		$this->currencies = array(
			$this->usd_code  => array( 'languages' => array( $this->default_language, $this->second_language ) ),
			$this->euro_code => array( 'languages' => array( $this->default_language, $this->second_language ) ),
		);

		wpml_test_reg_custom_post_type( 'bookable_person', true );
		wpml_test_reg_custom_post_type( 'bookable_resource', true );
	}


	/**
	 * @test
	 */
	public function save_custom_costs() {
		global $sitepress, $wpdb;

		$woocommerce_wpml = $this->get_test_subject();
		$product_id = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$_POST['_wcml_custom_costs_nonce'] = wp_create_nonce( 'wcml_save_custom_costs' );
		$this->bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );

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
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_person_cost`
	 * @param $product_id
	 */
	private function check_update_booking_person_cost( $product_id ) {
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$_POST['wcml_wc_booking_person_cost'] = array(
			$bookable_person => array(
				$this->usd_code  => $this->usd_price,
				$this->euro_code => $this->eur_price,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$this->assertEquals( $this->usd_price, get_post_meta( $bookable_person, 'cost_' . $this->usd_code, true ) );
		$this->assertEquals( $this->eur_price, get_post_meta( $bookable_person, 'cost_' . $this->euro_code, true ) );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_person_block_cost`
	 * @param $product_id
	 */
	private function check_update_booking_person_block_cost( $product_id ) {
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string() );
		$_POST['wcml_wc_booking_person_block_cost'] = array(
			$bookable_person => array(
				$this->usd_code  => $this->usd_price,
				$this->euro_code => $this->eur_price,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$this->assertEquals( $this->usd_price, get_post_meta( $bookable_person, 'block_cost_' . $this->usd_code, true ) );
		$this->assertEquals( $this->eur_price, get_post_meta( $bookable_person, 'block_cost_' . $this->euro_code, true ) );
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
	}

	private function get_test_subject() {
		$woocommerce_wpml = $this->get_wcml_mock();
		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();

		$woocommerce_wpml->multi_currency
			->method( 'get_currencies' )
			->willReturn( $this->currencies );

		$woocommerce_wpml->multi_currency
			->method( 'get_client_currency' )
			->willReturn( 'usd' );

		$woocommerce_wpml->products = $this->get_wcml_products_mock();
		$woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->willReturn( $this->default_language );

		$woocommerce_wpml->multi_currency->prices = $this->get_wcml_multi_currency_prices_mock();

		return $woocommerce_wpml;
	}

	/**
	 * Requires bookings plugin integration to avoid SQL issues.
	 */
	public function save_resource_translation() {
		global $sitepress, $wpdb;
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
		$job->language_code = 'de';
		$woocommerce_wpml = $this->get_test_subject();
		$bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );
		$bookings->save_resource_translation( $product, $data, $job );
	}

	/**
	 * @test
	 */
	public function sync_resource_costs() {
		global $sitepress, $wpdb;
		$product1 = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$product2 = wpml_test_insert_post( 'de', 'product', false, random_string() );
		$bookable_resource1 = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, random_string() );
		$woocommerce_wpml = $this->get_test_subject();
		$bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );
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

		$bookings->sync_resource_costs( $product1, $product2, 'base_cost', 'de' );

		$output = get_post_meta( $product2, 'base_cost', true );
		$trns_resource_id = apply_filters( 'translate_object_id', $bookable_resource1, 'bookable_resource', true, 'de' );
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
		global $sitepress, $wpdb;
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

		$bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );
		// Add products.
		$product            = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid               = $sitepress->get_element_trid( $product, 'post_product' );
		$translated_product = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );

		// Add persons.
		$bookable_person            = wpml_test_insert_post( $this->default_language, 'bookable_person', false, random_string(), $product );
		$trid                       = $sitepress->get_element_trid( $bookable_person, 'post_bookable_person' );
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
		global $sitepress, $wpdb;
		$woocommerce_wpml = $this->get_test_subject();
		$bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );
		$_COOKIE['_wcml_booking_currency'] = 'usd';
		update_option( 'woocommerce_currency', 'eur', 'no' );
		$this->assertEquals( 'usd', $bookings->get_cookie_booking_currency() );
		unset( $_COOKIE['_wcml_booking_currency'] );
		$this->assertEquals( 'eur', $bookings->get_cookie_booking_currency() );
	}

	/**
	 * @test
	 */
	public function filter_booking_currency_symbol() {
		global $pagenow, $sitepress, $wpdb;
		$_COOKIE['_wcml_booking_currency'] = 'USD';
		$_GET['page'] = 'create_booking';
		$pagenow = 'edit.php';
		$woocommerce_wpml = $this->get_test_subject();
		$bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );
		$this->assertEquals( 10, has_filter( 'woocommerce_currency_symbol', array( $bookings, 'filter_booking_currency_symbol' ) ) );
		$this->assertEquals( '&#36;', $bookings->filter_booking_currency_symbol( $_COOKIE['_wcml_booking_currency'] ) );
		unset( $_COOKIE['_wcml_booking_currency'] );
		$this->assertEquals( 'test', $bookings->filter_booking_currency_symbol( 'test' ) );
		$_COOKIE['_wcml_booking_currency'] = random_string();
		$this->assertEquals( '', $bookings->filter_booking_currency_symbol( $_COOKIE['_wcml_booking_currency'] ) );
	}

	/**
	 * @test
	 */
	public function custom_box_html_data() {
		global $sitepress, $wpdb;
		$woocommerce_wpml = $this->get_test_subject();
		$bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml, $wpdb );
		$product = wpml_test_insert_post( $this->default_language, 'product', false, random_string() );
		$trid = $sitepress->get_element_trid( $product, 'post_product' );
		$translation = wpml_test_insert_post( $this->second_language, 'product', $trid, random_string() );
		$translation = get_post( $translation );

		// If product is not bookable.
		$this->assertEquals( 'this is data', $bookings->custom_box_html_data( 'this is data', $product, $translation, $this->second_language ) );

		// Add booking term
		$booking_term = wpml_test_insert_term( $this->default_language, 'product_type', false, 'booking' );
		wp_set_post_terms( $product, array( (int) $booking_term['term_id'] ), 'product_type' );
		$trid = $sitepress->get_element_trid( $booking_term['term_id'], 'tax_product_type' );
		$translated_booking_term = wpml_test_insert_term( $this->second_language, 'product_type', $trid, 'booking' . $this->second_language );
		wp_set_post_terms( $translation->ID, array( (int) $translated_booking_term['term_id'] ), 'product_type' );

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
		global $sitepress;
		$resource_cost = random_int( 1, 999 );
		$resource_title = random_string();
		$bookable_resource = wpml_test_insert_post( $this->default_language, 'bookable_resource', false, $this->default_language . $resource_title );
		$trid = $sitepress->get_element_trid( $bookable_resource, 'post_bookable_resource' );
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
		global $sitepress;
		$excerpt = random_string();
		$resource_title = random_string();
		$bookable_person = wpml_test_insert_post( $this->default_language, 'bookable_person', false, $this->default_language . $resource_title, $product );
		$trid = $sitepress->get_element_trid( $bookable_person, 'post_bookable_person' );
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
}


/**
 * Added for `custom_box_html_data` test.
 * wc_get_product($product_id)->product_type requires this class to recognize product_type as 'booking'
 */
class WC_Product_Booking extends WC_Product {
	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		if ( empty ( $this->product_type ) ) {
			$this->product_type = 'booking';
		}

		parent::__construct( $product );
	}
}