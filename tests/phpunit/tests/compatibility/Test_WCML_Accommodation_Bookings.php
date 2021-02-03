<?php

/**
 * @group compatibility
 * @group woocommerce-bookings
 */
class Test_WCML_Accommodation_Bookings extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldLoadAssets() {
		$bookings = $this->getbookings();
		$bookings->expects( $this->once() )
			->method( 'load_assets' )
			->with( 'accommodation-booking' );

		$subject = $this->getSubject( null, $bookings );

		$subject->load_assets();
	}

	private function getSubject( $woocommerce_wpml = null, $bookings = null ) {
		$woocommerce_wpml = $woocommerce_wpml ?: $this->getWoocommerceWpml();
		$bookings         = $bookings ?: $this->getbookings();

		return new WCML_Accommodation_Bookings( $woocommerce_wpml, $bookings );
	}

	private function getWoocommerceWpml() {
		return $this->getMockBuilder( '\woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getbookings() {
		return $this->getMockBuilder( '\WCML_Bookings' )
			->setMethods( [ 'load_assets' ] )
			->disableOriginalConstructor()
			->getMock();
	}
}
