<?php
require_once __DIR__ . '/../../../inc/wcml-core-functions.php';

/**
 * @author OnTheGo Systems
 * @group  wcml_price_custom_fields
 */
class Test_WCML_Core_Functions extends OTGS_TestCase {
	/**
	 * @test
	 */
	public function it_does_NOT_filter_the_price_keys() {
		$default_keys = array(
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		);

		$object_id = 123;
		$this->assertSame( $default_keys, wcml_price_custom_fields( $object_id ) );
	}

	/**
	 * @test
	 */
	public function it_filters_the_price_keys_using_the_deprecated_filter_handle() {
		$default_keys = array(
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		);

		$object_id = 123;

		$filtered_keys = array( 'key1', 'key2', 'key3' );
		WP_Mock::onFilter( 'wcml_price_custom_fields_filtered' )->with( $default_keys, $object_id )->reply( $filtered_keys );

		$this->assertSame( $filtered_keys, wcml_price_custom_fields( $object_id ) );
	}

	/**
	 * @test
	 */
	public function it_filters_the_price_keys() {
		$default_keys = array(
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		);

		$object_id = 123;

		$filtered_keys = array( 'key1', 'key2', 'key3' );
		WP_Mock::onFilter( 'wcml_price_custom_fields' )->with( $default_keys, $object_id )->reply( $filtered_keys );

		$this->assertSame( $filtered_keys, wcml_price_custom_fields( $object_id ) );
	}

	/**
	 * @test
	 */
	public function it_filters_the_price_keys_using_both_the_deprecated_and_current_filter_handle() {
		$default_keys = array(
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		);

		$object_id = 123;

		$filtered_keys_1 = array( 'key1', 'key2', 'key3' );
		$filtered_keys_2 = array( 'key3', 'key4', 'key5' );
		WP_Mock::onFilter( 'wcml_price_custom_fields_filtered' )->with( $default_keys, $object_id )->reply( $filtered_keys_1 );
		WP_Mock::onFilter( 'wcml_price_custom_fields' )->with( $filtered_keys_1, $object_id )->reply( $filtered_keys_2 );

		$this->assertSame( $filtered_keys_2, wcml_price_custom_fields( $object_id ) );
	}

	/**
	 * @test
	 */
	public function it_returns_the_default_keys_if_the_filter_does_not_return_an_array() {
		$default_keys = array(
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		);

		$object_id = 123;

		WP_Mock::onFilter( 'wcml_price_custom_fields_filtered' )->with( $default_keys, $object_id )->reply( 'a-non-array-value' );
		WP_Mock::onFilter( 'wcml_price_custom_fields' )->with( 'a-non-array-value', $object_id )->reply( 'a-non-array-value' );

		$this->assertSame( $default_keys, wcml_price_custom_fields( $object_id ) );
	}

}
