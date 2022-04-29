<?php

/**
 * @group compatibility
 * @group wc-subscriptions
 */
class Test_WCML_WC_Subscriptions extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var wpdb */
	private $wpdb;

	public function setUp()	{
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( woocommerce_wpml::class )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();
	}

	private function get_subject() {
		return new WCML_WC_Subscriptions( $this->woocommerce_wpml, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function actions_on_init_front_end() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$subject = $this->get_subject();

		WP_Mock::expectFilterAdded( 'wcs_get_subscription', [ $subject, 'filter_subscription_items' ] );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function it_set_allowed_variations_types_in_xliff() {
		$subject = $this->get_subject();

		$filtered_types = $subject->set_allowed_variations_types_in_xliff( [] );

		$this->assertSame( [ 'variable-subscription', 'subscription_variation' ], $filtered_types );
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

	/**
	 * @test
	 */
	public function it_should_not_filter_subscription_items_if_false() {
		$subject = $this->get_subject();

		$this->assertFalse( $subject->filter_subscription_items( false ) );
	}
}
