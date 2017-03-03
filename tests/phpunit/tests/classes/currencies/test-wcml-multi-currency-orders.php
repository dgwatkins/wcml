<?php

class Test_WCML_Multi_Currency_Orders extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WC_Order */
	private $order;

	private $is_admin = false;
	private $current_screen = '';

	private $post_meta = [];
	private $options = [];

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array() )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->order = $this->getMockBuilder( 'WC_Order' )
		                    ->disableOriginalConstructor()
							->setMethods( array( 'get_id', 'set_id' ) )
		                    ->getMock();
		$this->order->method( 'get_id' )->will( $this->returnCallback(
			function (){
				return $this->id;
			}
		) );
		$this->order->method( 'set_id' )->will( $this->returnCallback(
			function ( $id ){
				return $this->id = $id;
			}
		) );



		$that = $this;
		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => function () use ( $that ) {
				return $that->is_admin;
			},
		) );
		$this->current_screen = new stdClass();
		$this->current_screen->id = '';
		\WP_Mock::wpFunction( 'get_current_screen', array(
			'return' => function () use ( $that ) {
				return $that->current_screen;
			},
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) use ( $that ) {
				return $that->post_meta[ $id ][ $meta ] = $value;
			},
		) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'return' => function ( $id, $meta, $single ) use ( $that ) {
				return $that->post_meta[ $id ][ $meta ];
			},
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) use ( $that ) {
				return $that->options[$option_name];
			},
		) );

		\WP_Mock::wpFunction( 'update_option', array(
			'return' => function ( $option_name, $option_value ) use ( $that ) {
				$that->options[$option_name] = $option_value;
			},
		) );


	}

	/**
	 * @return WCML_REST_API_Support
	 */
	private function get_subject(){
		return new WCML_Multi_Currency_Orders( $this->woocommerce_wpml, $this->sitepress );
	}

	/**
	 * @test
	 */
	public function get_currency_for_new_order(){

		$subject = $this->get_subject();

		$original_currency = rand_str();

		// Not admin
		$this->is_admin = false;
		$this->current_screen->id = '';
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $original_currency, $filtered_currency );


		// Not order page
		$this->is_admin = false;
		$this->current_screen->id = 'show_order';
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $original_currency, $filtered_currency );

		// Admin, order page, meta set (return original)
		$this->is_admin = true;
		$this->current_screen->id = 'shop_order';
		$this->order->set_id( rand(1, 1000) );
		update_post_meta( $this->order->get_id(), 'order_currency', 'EUR' );
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $original_currency, $filtered_currency );

		// Admin, order page, meta empty (return cookie) - No COOKIE
		$this->is_admin = true;
		$this->current_screen->id = 'shop_order';
		$this->order->set_id( rand(1, 1000) );
		update_post_meta( $this->order->get_id(), 'order_currency', false );
		update_option( 'woocommerce_currency', $wocommerce_currency = rand_str() );
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $wocommerce_currency, $filtered_currency );

		// Admin, order page, meta empty (return cookie) - Yes COOKIE
		$this->is_admin = true;
		$this->current_screen->id = 'shop_order';
		$this->order->set_id( rand(1, 1000) );
		update_post_meta( $this->order->get_id(), 'order_currency', false );
		$_COOKIE['_wcml_order_currency'] = rand_str();
		$filtered_currency = $subject->get_currency_for_new_order( $original_currency, $this->order );
		$this->assertEquals( $_COOKIE['_wcml_order_currency'], $filtered_currency );

	}
}
