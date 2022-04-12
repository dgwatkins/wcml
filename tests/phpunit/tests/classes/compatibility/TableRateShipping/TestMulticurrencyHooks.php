<?php

namespace WCML\Compatibility\TableRateShipping;

use WP_Mock;
use tad\FunctionMocker\FunctionMocker;

/**
 * @group compatibility
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldAddHooks() {
		FunctionMocker::replace( 'constant', function( $name ) {
			return 'TABLE_RATE_SHIPPING_VERSION' === $name ? '3.0.10' : null;
		});

		$subject = $this->getSubject();

		WP_Mock::expectFilterAdded( 'woocommerce_table_rate_query_rates_args', [ $subject, 'filterQueryRatesArgs' ] );
		WP_Mock::expectFilterAdded( 'woocommerce_table_rate_package_row_base_price', [ $subject, 'filterProductBasePrice' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldNotAddHooks() {
		FunctionMocker::replace( 'constant', function( $name ) {
			return 'TABLE_RATE_SHIPPING_VERSION' === $name ? '3.0.12' : null;
		});

		$subject = $this->getSubject();

		WP_Mock::expectFilterNotAdded( 'woocommerce_table_rate_query_rates_args', [ $subject, 'filterQueryRatesArgs' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldFilterProductBasePrice() {
		$default_currency = 'USD';
		$client_currency  = 'EUR';

		$product_id    = 1;
		$product_price = 10;
		$qty           = 3;

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'times' => 1,
			'return' => $default_currency
		));

		$multicurrency = $this->getMockBuilder( '\WCML_Multi_Currency' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_client_currency' ) )
			->getMock();

		$multicurrency->method('get_client_currency')->willReturn( $client_currency );


		$product = $this->getMockBuilder( '\WC_Product' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_id' ) )
			->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );

		$woocommerce_wpml = $this->getMockBuilder( '\woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( '\WCML_Products' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_product_price_from_db' ] )
			->getMock();

		$woocommerce_wpml->products->method( 'get_product_price_from_db' )
			->with( $product_id )
			->willReturn( $product_price );

		$subject = $this->getSubject( $woocommerce_wpml, $multicurrency );

		$this->assertEquals( $product_price * $qty, $subject->filterProductBasePrice( 10, $product, $qty ) );
	}

	private function getSubject( $woocommerce_wpml = null, $multicurrency = null ) {
		if ( ! $woocommerce_wpml ) {
			$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( ! $multicurrency ) {
			$multicurrency = $this->getMockBuilder( 'WCML_Multi_Currency' )
				->disableOriginalConstructor()
				->getMock();
		}

		return new MulticurrencyHooks( $woocommerce_wpml, $multicurrency );
	}

}
