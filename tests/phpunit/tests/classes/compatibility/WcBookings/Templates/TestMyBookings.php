<?php

namespace WCML\Compatibility\WcBookings\Templates;

use WP_Mock;
use WPML\LIB\WP\OnActionMock;

class TestMyBookings extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itDisplaysAsTranslated() {
		$originalBookingId = 1;
		$bookingId         = 2;

		$originalProductId = 10;
		$productId         = null; // Not translated.

		$booking = $this->getMockBuilder( 'WC_Booking' )
			->setMethods( [ 'get_product', 'get_id', 'set_product_id' ] )
			->disableOriginalConstructor()
			->getMock();

		$booking->method( 'get_product' )->willReturn( $productId );
		$booking->method( 'get_id' )->willReturn( $bookingId );
		$booking->expects( $this->once() )->method( 'set_product_id' )->with( $originalProductId );

		WP_Mock::onFilter( 'wpml_original_element_id' )
			->with( false, $bookingId, 'post_wc_booking' )
			->reply( $originalBookingId );

		$originalBooking = $this->getMockBuilder( 'WC_Booking' )
			->setMethods( [ 'get_product_id' ] )
			->disableOriginalConstructor()
			->getMock();

		$originalBooking->method( 'get_product_id' )
			->willReturn( $originalProductId );

		WP_Mock::userFunction( 'get_wc_booking' )
			->with( $originalBookingId )
			->andReturn( $originalBooking );

		$tables = [
			'Upcoming' => [
				'bookings' => [
					$booking,
				]
			]
		];

		( new MyBookings() )->add_hooks();

		$this->runFilter( 'woocommerce_bookings_account_tables', $tables );
	}

}
