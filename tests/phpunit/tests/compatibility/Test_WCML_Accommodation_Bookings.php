<?php

use WCML\Compatibility\WcBookings\SharedHooks;

/**
 * @group compatibility
 * @group woocommerce-bookings
 */
class Test_WCML_Accommodation_Bookings extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldLoadAssets() {
		$loadAssets = tad\FunctionMocker\replace( SharedHooks::class . '::load_assets' );

		$subject = $this->getSubject();

		$subject->load_assets();

		$loadAssets->wasCalledWithOnce( [ 'accommodation-booking' ] );
	}

	private function getSubject( $products = null, $multiCurrency = null ) {
		$woocommerce_wpml = $this->getMockBuilder( \woocommerce_wpml::class )->disableOriginalConstructor()->getMock();

		$woocommerce_wpml->products       = $products ?: $this->getProducts();
		$woocommerce_wpml->multi_currency = $multiCurrency ?: $this->getMultiCurrency();

		return new WCML_Accommodation_Bookings( $woocommerce_wpml );
	}

	private function getProducts() {
		return $this->getMockBuilder( WCML_Products::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMultiCurrency() {
		return $this->getMockBuilder( WCML_Multi_Currency::class )
			->setMethods( [] )
			->disableOriginalConstructor()
			->getMock();
	}
}
