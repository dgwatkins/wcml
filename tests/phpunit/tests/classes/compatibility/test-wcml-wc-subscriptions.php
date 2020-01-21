<?php

class Test_WCML_WC_Subscriptions extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var wpdb */
	private $wpdb;

	public function setUp()
	{
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();
	}

	private function get_subject(){
		return new WCML_WC_Subscriptions( $this->woocommerce_wpml, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function actions_on_init_front_end() {
		\WP_Mock::wpFunction(
			'is_admin',
			array(
				'return' => false,
			)
		);

		\WP_Mock::wpFunction(
			'is_cart',
			[
				'return' => false,
				'times' => 1,
			]
		);

		\WP_Mock::wpFunction(
			'is_checkout',
			[
				'return' => false,
				'times' => 1,
			]
		);

		\WP_Mock::wpFunction(
			'wcml_is_multi_currency_on',
			array(
				'return' => true,
			)
		);

		$subject = $this->get_subject();

		WP_Mock::expectActionAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1 );
		WP_Mock::expectActionAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1 );

		WP_Mock::expectFilterAdded( 'wcs_get_subscription', array( $subject, 'filter_subscription_items' ) );

		\WP_Mock::wpFunction( 'wcs_cart_contains_resubscribe', array(
			'return' => false
		) );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function actions_on_init_back_end() {
		\WP_Mock::wpFunction(
			'is_admin',
			array(
				'return' => true,
			)
		);

		$subject = $this->get_subject();

		WP_Mock::expectActionNotAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1 );
		WP_Mock::expectActionNotAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1 );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function maybe_backup_recurring_carts() {

		$subject = $this->get_subject();

		$cart                  = new stdClass();
		$cart->recurring_carts = rand_str();

		$other_cart                  = new stdClass();
		$other_cart->recurring_carts = false;

		$subject->maybe_backup_recurring_carts( $cart );

		$subject->maybe_restore_recurring_carts( $other_cart );

		$this->assertEquals( $cart->recurring_carts, $other_cart->recurring_carts );
	}

	/**
	 * @test
	 */
	public function woocommerce_subscription_price_from_custom_price() {

		$price           = rand( 1, 100 );
		$expected_price  = rand( 1, 100 );
		$variation_id    = rand( 1, 100 );
		$client_currency = rand_str();

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_type', 'get_meta' ) )
		                ->getMock();

		$product->method( 'get_type' )->willReturn( 'variable-subscription' );

		$product->method( 'get_meta' )->with( '_min_price_variation_id', true )->willReturn( $variation_id );

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $variation_id, '_wcml_custom_prices_status', true ),
			'return' => true
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $variation_id, '_price_' . $client_currency, true ),
			'return' => $expected_price
		) );

		$subject = $this->get_subject();

		$filtered_price = $subject->woocommerce_subscription_price_from( $price, $product );

		$this->assertEquals( $expected_price, $filtered_price );
	}

	/**
	 * @test
	 */
	public function woocommerce_subscription_price_from_auto_converted_price() {

		$price          = rand( 1, 100 );
		$expected_price = rand( 1, 100 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_type', 'get_meta' ) )
		                ->getMock();

		$product->method( 'get_type' )->willReturn( 'variable-subscription' );

		$product->method( 'get_meta' )->with( '_min_price_variation_id', true )->willReturn( false );

		\WP_Mock::onFilter( 'wcml_raw_price_amount' )
		        ->with( $price )
		        ->reply( $expected_price );

		$subject = $this->get_subject();

		$filtered_price = $subject->woocommerce_subscription_price_from( $price, $product );

		$this->assertEquals( $expected_price, $filtered_price );
	}
	/**
	 * @test
	 */
	public function woocommerce_subscription_price_from_auto_converted_price_for_subscription_variation_type() {

		$price          = rand( 1, 100 );
		$expected_price = rand( 1, 100 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_type', 'get_meta' ) )
		                ->getMock();

		$product->method( 'get_type' )->willReturn( 'subscription_variation' );

		$product->method( 'get_meta' )->with( '_min_price_variation_id', true )->willReturn( false );

		\WP_Mock::onFilter( 'wcml_raw_price_amount' )
		        ->with( $price )
		        ->reply( $expected_price );

		$subject = $this->get_subject();

		$filtered_price = $subject->woocommerce_subscription_price_from( $price, $product );

		$this->assertEquals( $expected_price, $filtered_price );
	}

	/**
	 * @test
	 */
	public function it_should_force_client_currency_for_resubscribe_link() {
		$subscription_id     = 10;
		$_GET['resubscribe'] = $subscription_id;

		$subject = $this->force_client_currency_for_subscription_mock( $subscription_id );
		$subject->maybe_force_client_currency_for_subscription();

		unset( $_GET['resubscribe'] );
	}

	/**
	 * @test
	 */
	public function it_should_force_client_currency_for_renewal_early_link() {
		$subscription_id                    = 10;
		$_GET['subscription_renewal_early'] = $subscription_id;

		$subject = $this->force_client_currency_for_subscription_mock( $subscription_id );
		$subject->maybe_force_client_currency_for_subscription();

		unset( $_GET['subscription_renewal_early'] );
	}

	/**
	 * @test
	 */
	public function it_should_force_client_currency_for_subscription_cart_contains_resubscribe() {
		$subscription_id = 11;

		$wcs_cart_contains_resubscribe['subscription_resubscribe']['subscription_id'] = $subscription_id;

		\WP_Mock::wpFunction(
			'is_cart',
			[
				'return' => true,
				'times'  => 1,
			]
		);

		\WP_Mock::wpFunction( 'wcs_cart_contains_resubscribe', [
			'return' => $wcs_cart_contains_resubscribe,
			'times' => 1,
		] );

		$subject = $this->force_client_currency_for_subscription_mock( $subscription_id );
		$subject->maybe_force_client_currency_for_subscription();
	}

	/**
	 * @test
	 */
	public function it_should_force_client_currency_for_subscription_cart_contains_renewal_early() {
		$subscription_id = 12;

		$early_renewal_cart_item['subscription_renewal']['subscription_renewal_early'] = $subscription_id;

		\WP_Mock::wpFunction(
			'is_cart',
			[
				'return' => true,
				'times'  => 1,
			]
		);

		\WP_Mock::wpFunction( 'wcs_cart_contains_early_renewal', [
			'return' => $early_renewal_cart_item,
			'times' => 1,
		] );

		\WP_Mock::wpFunction( 'wcs_cart_contains_resubscribe', [
			'return' => false,
			'times' => 1,
		] );

		$subject = $this->force_client_currency_for_subscription_mock( $subscription_id );
		$subject->maybe_force_client_currency_for_subscription();
	}

	private function force_client_currency_for_subscription_mock( $subscription_id ) {
		$subscription_currency = 'EUR';
		$client_currency       = 'USD';

		\WP_Mock::wpFunction( 'get_post_meta', [
			'args'   => [ $subscription_id, '_order_currency', true ],
			'return' => $subscription_currency
		] );

		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( [ 'set_client_currency', 'get_client_currency' ] )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );
		$this->woocommerce_wpml->multi_currency->expects( $this->once() )->method( 'set_client_currency' )->willReturn( true );

		return $this->get_subject();
	}

	/**
	 * @test
	 */
	public function does_not_force_client_currency_for_resubscribe_subscription_when_MC_is_off() {

		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array(
			'return' => false
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'times' => 0
		) );

		$subject = $this->get_subject();
		$subject->maybe_force_client_currency_for_subscription();
	}

	/**
	 * @test
	 */
	public function subscriptions_product_sign_up_fee_filter_custom_price() {

		$subscription_sign_up_fee          = mt_rand( 1, 100 );
		$expected_subscription_sign_up_fee = mt_rand( 101, 200 );
		$product_id                        = mt_rand( 201, 300 );
		$client_currency                   = rand_str();

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_id' ) )
		                ->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );

		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array(
			'return' => true
		) );

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_original_product_id' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $product_id );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wcml_custom_prices_status', true ),
			'return' => true
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_subscription_sign_up_fee_' . $client_currency, true ),
			'return' => $expected_subscription_sign_up_fee
		) );

		\WP_Mock::wpFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => rand_str()
		) );

		$subject = $this->get_subject();

		$filtered_subscription_sign_up_fee = $subject->subscriptions_product_sign_up_fee_filter( $subscription_sign_up_fee, $product );

		$this->assertEquals( $expected_subscription_sign_up_fee, $filtered_subscription_sign_up_fee );
	}

	/**
	 * @test
	 */
	public function subscriptions_product_sign_up_fee_filter_converted_price() {

		$subscription_sign_up_fee          = mt_rand( 1, 100 );
		$expected_subscription_sign_up_fee = mt_rand( 101, 200 );
		$product_id                        = mt_rand( 201, 300 );

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( rand_str() );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_id' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $product_id );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_id' ) )
		                ->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );

		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array(
			'return' => true
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wcml_custom_prices_status', true ),
			'return' => false
		) );

		\WP_Mock::wpFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => rand_str()
		) );

		\WP_Mock::onFilter( 'wcml_raw_price_amount' )
		        ->with( $subscription_sign_up_fee )
		        ->reply( $expected_subscription_sign_up_fee );

		$subject = $this->get_subject();

		$filtered_subscription_sign_up_fee = $subject->subscriptions_product_sign_up_fee_filter( $subscription_sign_up_fee, $product );

		$this->assertEquals( $expected_subscription_sign_up_fee, $filtered_subscription_sign_up_fee );
	}

	/**
	 * @test
	 */
	public function it_does_not_filter_subscriptions_product_sign_up_fee_for_default_currency() {

		$subscription_sign_up_fee          = mt_rand( 1, 100 );
		$client_currency                   = rand_str();


		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		\WP_Mock::wpFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $client_currency
		) );

		$subject = $this->get_subject();

		$filtered_subscription_sign_up_fee = $subject->subscriptions_product_sign_up_fee_filter( $subscription_sign_up_fee, null );

		$this->assertSame( $subscription_sign_up_fee, $filtered_subscription_sign_up_fee );
	}


	/**
	 * @test
	 */
	public function it_set_allowed_variations_types_in_xliff() {

		$subject = $this->get_subject();

		$filtered_types = $subject->set_allowed_variations_types_in_xliff( array() );

		$this->assertSame( array( 'variable-subscription', 'subscription_variation' ), $filtered_types );
	}

	/**
	 * @test
	 */
	public function it_should_update_subscription_custom_prices_values() {

		WP_Mock::passthruFunction( 'wc_format_decimal' );

		$code = 'USD';
		$sing_up_fee = 100;

		$_POST[ '_custom_subscription_sign_up_fee' ][ $code ] = $sing_up_fee;

		$expected_prices = array(
			'_subscription_sign_up_fee' => $sing_up_fee
		);

		$subject = $this->get_subject();

		$filtered_prices = $subject->update_custom_prices_values( array(), $code );

		$this->assertSame( $expected_prices, $filtered_prices );

		unset( $_POST[ '_custom_subscription_sign_up_fee' ] );
	}

	/**
	 * @test
	 */
	public function it_should_update_variation_subscription_custom_prices_values() {

		WP_Mock::passthruFunction( 'wc_format_decimal' );

		$code = 'USD';
		$variation_id = 10;
		$sing_up_fee = 100;

		$_POST[ '_custom_variation_subscription_sign_up_fee' ][ $code ][ $variation_id ] = $sing_up_fee;

		$expected_prices = array(
			'_subscription_sign_up_fee' => $sing_up_fee
		);

		$subject = $this->get_subject();

		$filtered_prices = $subject->update_custom_prices_values( array(), $code, $variation_id );

		$this->assertSame( $expected_prices, $filtered_prices );

		unset( $_POST[ '_custom_variation_subscription_sign_up_fee' ] );
	}

	/**
	 * @test
	 */
	public function it_should_filter_subscription_items() {

		$items = [];

		$subscription = $this->getMockBuilder( 'WC_Subscription' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( [ 'get_items' ] )
		                     ->getMock();
		$subscription->expects( $this->once() )->method( 'get_items' )->willReturn( $items );
		$this->woocommerce_wpml->orders = $this->getMockBuilder( 'WCML_Orders' )
		                                       ->disableOriginalConstructor()
		                                       ->setMethods( [ 'adjust_order_item_in_language' ] )
		                                       ->getMock();

		$this->woocommerce_wpml->orders->expects( $this->once() )->method( 'adjust_order_item_in_language' )->with( $items )->willReturn( true );
		$subject = $this->get_subject();

		$subject->filter_subscription_items( $subscription );

	}

}
