<?php

/**
 * Class Test_WCML_Bookings
 */
class Test_WCML_Bookings extends WCML_UnitTestCase {

	/**
	 * @var
	 */
	private $bookings;

	private $currencies;
	private $usd_code;
	private $euro_code;

	function setUp() {
		parent::setUp();
		$this->usd_code  = 'usd';
		$this->euro_code = 'eur';
		$this->currencies = array(
			$this->usd_code  => array( 'languages' => array( 'en', 'de' ) ),
			$this->euro_code => array( 'languages' => array( 'en', 'de' ) ),
		);
	}


	/**
	 * @test
	 */
	public function save_custom_costs() {
		global $sitepress;

		$woocommerce_wpml = $this->get_test_subject();

		$product_id = wpml_test_insert_post( 'en', 'product', false, random_string() );
		$_POST['_wcml_custom_costs_nonce'] = wp_create_nonce( 'wcml_save_custom_costs' );
		$this->bookings = new WCML_Bookings( $sitepress, $woocommerce_wpml );

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
		$bookable_person = wpml_test_insert_post( 'en', 'bookable_person', false, random_string() );
		$_POST['wcml_wc_booking_person_cost'] = array(
			$bookable_person => array(
				$this->usd_code  => 100,
				$this->euro_code => 101,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$this->assertEquals( 100, get_post_meta( $bookable_person, 'cost_' . $this->usd_code, true ) );
		$this->assertEquals( 101, get_post_meta( $bookable_person, 'cost_' . $this->euro_code, true ) );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_person_block_cost`
	 * @param $product_id
	 */
	private function check_update_booking_person_block_cost( $product_id ) {
		$bookable_person = wpml_test_insert_post( 'en', 'bookable_person', false, random_string() );
		$_POST['wcml_wc_booking_person_block_cost'] = array(
			$bookable_person => array(
				$this->usd_code  => 100,
				$this->euro_code => 101,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$this->assertEquals( 100, get_post_meta( $bookable_person, 'block_cost_' . $this->usd_code, true ) );
		$this->assertEquals( 101, get_post_meta( $bookable_person, 'block_cost_' . $this->euro_code, true ) );
	}

	/**
	 * Specific check of `WCML_Bookings::update_booking_resource_cost`
	 * @param $product_id
	 */
	private function check_update_booking_resource_cost( $product_id ) {
		$resource_id = random_int( 90, 999 );
		$_POST['wcml_wc_booking_resource_cost'] = array(
			$resource_id => array(
				$this->usd_code  => 100,
				$this->euro_code => 101,
			),
		);
		$this->bookings->save_custom_costs( $product_id );

		$custom_costs = get_post_meta( $product_id, '_resource_base_costs', true );
		$this->assertTrue( isset( $custom_costs['custom_costs'] ) );

		$this->assertEquals( 100, $custom_costs['custom_costs'][ $this->usd_code ][ $resource_id ] );
		$this->assertEquals( 101, $custom_costs['custom_costs'][ $this->euro_code ][ $resource_id ] );
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
			->willReturn( 'en' );

		$woocommerce_wpml->multi_currency->prices = $this->get_wcml_multi_currency_prices_mock();
		$woocommerce_wpml->multi_currency->prices
			->method( 'convert_price_amount' )
			->willReturn( 100 );

		return $woocommerce_wpml;
	}

	/**
	 * @test
	 */
	public function sync_bookings() {

	}
}
