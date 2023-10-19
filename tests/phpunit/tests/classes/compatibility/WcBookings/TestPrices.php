<?php

namespace WCML\Compatibility\WcBookings;

/**
 * Class TestPrices
 *
 * @group compatibility
 * @group wc-bookings
 */
class TestPrices extends \OTGS_TestCase {

	private function getSubject() {
		return new Prices();
	}

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wcml_product_has_custom_prices', [ $subject, 'checkCustomCosts' ] , 10, 2);

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itChecksCustomPrices() {
		$productId = 123;

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_type' ] )
			->getMock();
		$product->method( 'get_type' )
			->willReturn( 'booking' );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $productId ],
			'return' => $product,
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $productId, '_wcml_custom_costs_status', true ],
			'return' => '1',
		] );

		$subject = $this->getSubject();

		$this->assertTrue( (bool) $subject->checkCustomCosts( false, $productId ) );
	}

}
