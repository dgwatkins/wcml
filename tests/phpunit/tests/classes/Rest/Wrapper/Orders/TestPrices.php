<?php

namespace WCML\Rest\Wrapper\Orders;

use tad\FunctionMocker\FunctionMocker;
use WCML\Orders\Helper as OrdersHelper;

/**
 * @group rest
 * @group rest-orders
 */
class TestOrders extends \OTGS_TestCase {

	function get_subject() {
		return new Prices();
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

		$order = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id',
			             'get_items',
			             'calculate_totals'
		             ] )
		             ->getMock();

		$order->ID = random_int( 1, 100 );
		$order->method( 'get_id' )->willReturn( $order->ID );

		$line_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		$items     = [ $line_item ];
		$order->method( 'get_items' )->willReturn( $items );

		$setCurrency = FunctionMocker::replace( OrdersHelper::class . '::setCurrency', null );

		$order->method( 'calculate_totals' )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $order, $request1, true );

		$setCurrency->wasCalledWithOnce( [ $order->ID, $expected_currency ] );
	}


}
