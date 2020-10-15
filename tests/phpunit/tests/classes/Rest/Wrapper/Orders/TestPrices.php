<?php

namespace WCML\Rest\Wrapper\Orders;

/**
 * @group rest
 * @group rest-orders
 */
class TestOrders extends \OTGS_TestCase {

	/** @var WCML_Multi_Currency_Orders */
	private $wcmlMultiCurrencyOrders;

	public function setUp() {
		parent::setUp();
		$this->wcmlMultiCurrencyOrders = $this->getMockBuilder( 'WCML_Multi_Currency_Orders' )
		                                      ->disableOriginalConstructor()
		                                      ->setMethods( [
			                                      'set_converted_totals_for_item'
		                                      ] )
		                                      ->getMock();
	}

	function get_subject() {
		return new Prices( $this->wcmlMultiCurrencyOrders );
	}


	/**
	 * @test
	 */
	public function set_order_currency_valid_currency() {

		$expected_currency = rand_str();
		$request1          = $this->getMockBuilder( 'WP_REST_Request' )
		                          ->disableOriginalConstructor()
		                          ->setMethods( [ 'get_params' ] )
		                          ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'currency' => $expected_currency
		] );

		$woocommerce_currencies = [
			$expected_currency => rand_str(),
			rand_str()         => rand_str(),
			rand_str()         => rand_str()
		];
		\WP_Mock::wpFunction( 'get_woocommerce_currencies', [ 'return' => $woocommerce_currencies ] );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id',
			             'get_items',
			             'calculate_totals'
		             ] )
		             ->getMock();

		$post->ID = random_int( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		$line_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		$items     = [ $line_item ];
		$post->method( 'get_items' )->willReturn( $items );

		\WP_Mock::wpFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $post->ID, '_order_currency', $expected_currency ]
		] );


		$this->wcmlMultiCurrencyOrders->method( 'set_converted_totals_for_item' )->with( $line_item, [], $post->ID )->willReturn( true );

		$post->method( 'calculate_totals' )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );
	}


}
