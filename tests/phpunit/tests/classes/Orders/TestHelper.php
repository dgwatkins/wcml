<?php

namespace WCML\Orders;

/**
 * @group orders
 */
class TestHelper extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrency() {
		$orderId  = 123;
		$currency = 'EUR';

		\WP_Mock::userFunction( 'did_action' )->with( 'woocommerce_after_register_post_type' )->andReturn( true );

		$order = $this->getOrder();
		$order->method( 'get_currency' )->willReturn( $currency );
		$order->method( 'get_status' )->willReturn( 'pending' );

		\WP_Mock::userFunction( 'wc_get_order', [
			'args'   => $orderId,
			'return' => $order,
		] );

		$this->assertEquals( $currency, Helper::getCurrency( $orderId ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldSetCurrency() {
		$orderId  = 123;
		$currency = 'EUR';

		$this->mockSetCurrency( $orderId, $currency );

		Helper::setCurrency( $orderId, $currency );
	}

	/**
	 * @return \WC_Order&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getOrder() {
		return $this->getMockBuilder( \WC_Order::class )
			->setMethods( [
				'get_currency',
				'set_currency',
				'get_status',
				'save',
			] )
			->getMock();
	}

	/**
	 * @param int    $orderId
	 * @param string $currency
	 *
	 * @return void
	 */
	private function mockSetCurrency( $orderId, $currency ) {
		$order = $this->getOrder();
		$order->expects( $this->once() )->method( 'set_currency' )->with( $currency );
		$order->expects( $this->once() )->method( 'save' );

		\WP_Mock::userFunction( 'wc_get_order', [
			'args'   => $orderId,
			'return' => $order,
		] );
	}
}
