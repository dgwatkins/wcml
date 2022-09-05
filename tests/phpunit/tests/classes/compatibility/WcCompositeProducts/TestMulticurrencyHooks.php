<?php

namespace WCML\Compatibility\WcCompositeProducts;

use WCML_Multi_Currency;
use WCML_Multi_Currency_Prices;
use woocommerce_wpml;

/**
 * @group compatibility
 * @group wc-composite-products
 * @group wcml-3687
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 *
	 * @return MulticurrencyHooks
	 */
	private function getSubject( $woocommerce_wpml = null ) {
		$woocommerce_wpml = $woocommerce_wpml ?: $this->getWooCommerceWpml();

		return new MulticurrencyHooks( $woocommerce_wpml );
	}

	private function getWooCommerceWpml() {
		return $this->getMockBuilder( woocommerce_wpml::class )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @param string $clientCurrency
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|WCML_Multi_Currency
	 */
	private function getMultiCurrency( $clientCurrency = 'EUR' ) {
		$mc = $this->getMockBuilder( WCML_Multi_Currency::class )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_client_currency' ] )
		            ->getMock();

		$mc->method( 'get_client_currency' )->willReturn( $clientCurrency );

		return $mc;
	}

	private function getMcPrices() {
		return $this->getMockBuilder( WCML_Multi_Currency_Prices::class )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'apply_rounding_rules' ] )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function add_price_rounding_filters(){
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );

		$subject = $this->getSubject();

		$filters = [
			'woocommerce_product_get_price',
			'woocommerce_product_get_sale_price',
			'woocommerce_product_get_regular_price',
			'woocommerce_product_variation_get_price',
			'woocommerce_product_variation_get_sale_price',
			'woocommerce_product_variation_get_regular_price'
		];

		foreach( $filters as $filter ){
			\WP_Mock::expectFilterAdded( $filter, [ $subject, 'apply_rounding_rules' ], $subject::PRICE_FILTERS_PRIORITY_AFTER_COMPOSITE );
		}

		$subject->add_price_rounding_filters();
	}

	/**
	 * @test
	 *
	 * @group wcml-2663
	 */
	public function it_should_apply_rounding_rules() {
		$price           = 15.20;
		$convertedPrice  = 24.60;
		$defaultCurrency = 'USD';
		$clientCurrency  = 'EUR';

		$woocommerce_wpml = $this->getWooCommerceWpml();

		$woocommerce_wpml->multi_currency = $this->getMultiCurrency( $clientCurrency );

		$woocommerce_wpml->multi_currency->prices = $this->getMcPrices();
		$woocommerce_wpml->multi_currency->prices->method( 'apply_rounding_rules' )->with( $price )->willReturn( $convertedPrice );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option' )->andReturn( $defaultCurrency );
		\WP_Mock::userFunction( 'is_composite_product' )->andReturn( true );

		$subject = $this->getSubject( $woocommerce_wpml );

		$this->assertSame( $convertedPrice, $subject->apply_rounding_rules( $price ) );
	}

	/**
	 * @test
	 * @group wcml-2663
	 */
	public function it_should_not_apply_rounding_rules_if_empty_price() {
		\WP_Mock::userFunction( 'is_composite_product' )->andReturn( true );

		$this->assertSame( '', $this->getSubject()->apply_rounding_rules( '' ) );
	}

	/**
	 * @test
	 * @group wcml-2663
	 */
	public function it_should_not_apply_rounding_rules_if_not_composite_product() {
		$price = 12.50;

		\WP_Mock::userFunction( 'is_composite_product' )->andReturn( false );

		$this->assertSame( $price, $this->getSubject()->apply_rounding_rules( $price ) );
	}

	/**
	 * @test
	 * @group wcml-2663
	 */
	public function it_should_not_apply_rounding_rules_if_in_default_currency() {
		$price          = 12.50;
		$clientCurrency = 'USD';

		\WP_Mock::userFunction( 'is_composite_product' )->andReturn( true );
		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option' )->andReturn( $clientCurrency );

		$woocommerce_wpml = $this->getWooCommerceWpml();

		$woocommerce_wpml->multi_currency = $this->getMultiCurrency( $clientCurrency );

		$subject = $this->getSubject( $woocommerce_wpml );

		$this->assertSame( $price, $subject->apply_rounding_rules( $price ) );
	}
}
