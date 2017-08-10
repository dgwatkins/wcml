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
	public function add_hooks() {

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'wcml_calculate_totals_exception', '__return_false' );

		$subject->add_hooks();
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

		$subject = $this->get_subject();

		WP_Mock::expectActionAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1, 1 );
		WP_Mock::expectActionAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1, 1 );

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

}
