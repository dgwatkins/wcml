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

	/**
	 *
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_set_currency_to_session() {
		global $woocommerce;

		$client_currency = 'UAH';

		$woocommerce          = $this->getMockBuilder( 'WooCommerce' )
		                             ->disableOriginalConstructor()
		                             ->getMock();
		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                             ->disableOriginalConstructor()
		                             ->setMethods( [ 'set' ] )
		                             ->getMock();

		$woocommerce->session->expects( $this->once() )->method( 'set' )->with( 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY, $client_currency )->willReturn( true );

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'return' => function ( $className ) {
				return new $className();
			},
		] );

		wcml_user_store_set( 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY, $client_currency );
	}

	/**
	 *
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_get_currency_from_session() {
		global $woocommerce;

		$client_currency = 'UAH';

		$woocommerce = $this->getMockBuilder( 'WooCommerce' )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                             ->disableOriginalConstructor()
		                             ->setMethods( [ 'get' ] )
		                             ->getMock();

		$woocommerce->session->expects( $this->once() )->method( 'get' )->willReturn( $client_currency );


		\WP_Mock::userFunction( 'WPML\Container\make', [
			'return' => function ( $className ) {
				return new $className();
			},
		] );

		$this->assertEquals( $client_currency, wcml_user_store_get( 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY ) );
	}

	/**
	 *
	 * @test
	 *
	 */
	public function it_should_set_currency_to_cookie() {

		\WP_Mock::onFilter( 'wcml_user_store_strategy' )
		        ->with( 'wc-session', 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY )
		        ->reply( 'cookie' );

		$client_currency = 'EUR';

		$cookieHandler = $this->getMockBuilder( 'WPML_Cookie' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'headers_sent', 'set_cookie' ] )
		                      ->getMock();

		$cookieHandler->expects( $this->once() )->method( 'headers_sent' )->willReturn( false );

		$session_expiration = time() + (int) 60 * 60 * 48;

		$cookieHandler->expects( $this->once() )->method( 'set_cookie' )->with( 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY, $client_currency, $session_expiration, COOKIEPATH, COOKIE_DOMAIN )->willReturn( true );

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'return' => function ( $className ) use ( $cookieHandler ) {
				return new $className( $cookieHandler );
			},
		] );

		wcml_user_store_set( 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY, $client_currency );
	}

	/**
	 *
	 * @test
	 *
	 */
	public function it_should_get_currency_from_cookie() {

		\WP_Mock::onFilter( 'wcml_user_store_strategy' )
		        ->with( 'wc-session', 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY )
		        ->reply( 'cookie' );

		$client_currency = 'EUR';

		$cookieHandler = $this->getMockBuilder( 'WPML_Cookie' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_cookie' ] )
		                      ->getMock();

		$cookieHandler->expects( $this->once() )->method( 'get_cookie' )->willReturn( $client_currency );

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'return' => function ( $className ) use ( $cookieHandler ) {
				return new $className( $cookieHandler );
			},
		] );

		$this->assertEquals( $client_currency, wcml_user_store_get( 'wcml_' . WCML_Multi_Currency::CURRENCY_STORAGE_KEY ) );
	}

}
