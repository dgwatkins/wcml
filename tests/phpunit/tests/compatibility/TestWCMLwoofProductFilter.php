<?php

/**
 * Class TestWCMLwoofProductFilter
 *
 * @group compatibility
 */
class TestWCMLwoofProductFilter extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => 1
		] );
		WP_Mock::expectActionAdded( 'init', [ $subject, 'setupCurrencies'] );
		WP_Mock::expectFilterAdded( 'woof_get_meta_query', [ $subject, 'priceInDefaultCurrency' ], 10, 1 );
		WP_Mock::expectFilterAdded( 'wcml_exchange_rates', [ $subject, 'storeExchangeRates' ], 10, 1 );
		$subject->addHooks();
	}

	/**
	 * @test
	 */
	public function itDoesNOTAddHooksWhenMulticurrencyIsOff() {
		$subject = $this->getSubject();
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => 0
		] );
		WP_Mock::expectActionNotAdded( 'init', [ $subject, 'setupCurrencies'] );
		WP_Mock::expectFilterNotAdded( 'woof_get_meta_query', [ $subject, 'priceInDefaultCurrency' ], 10, 1 );
		WP_Mock::expectFilterNotAdded( 'wcml_exchange_rates', [ $subject, 'storeExchangeRates' ], 10, 1 );
		$subject->addHooks();
	}

	/**
	 * @test
	 */
	public function itSetupsCurrencies() {
		$subject = $this->getSubject();

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'return' => 'EUR'
		] );
		WP_Mock::onFilter( 'wcml_price_currency' )->with( 'EUR' )->reply( 'RUB' );

		$subject->setupCurrencies();
	}

	/**
	 * @test
	 */
	public function itStoresExchangeRates() {
		$subject = $this->getSubject();
		$rates = [
			'EUR' => 1,
			'RUB' => 10
		];
		$subject->storeExchangeRates( $rates );
	}

	/**
	 * @test
	 */
	public function itChangesPricesToDefaultCurrency() {
		$subject = $this->getSubject();

		$price = 100;
		$priceExpected = 10;
		$rates = [
			'EUR' => 1,
			'RUB' => 10
		];
		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'return' => 'EUR'
		] );
		WP_Mock::onFilter( 'wcml_price_currency' )->with( 'EUR' )->reply( 'RUB' );
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		$meta = [
			[
				'key' => '_price',
				'value' => [
					$price
				]
			]
		];
		$metaExpected = [
			[
				'key' => '_price',
				'value' => [
					$priceExpected
				]
			]
		];

		$subject->setupCurrencies();
		$subject->storeExchangeRates( $rates );
		$result = $subject->priceInDefaultCurrency( $meta );

		$this->assertSame( $result, $metaExpected );
	}

	/**
	 * @test
	 */
	public function itKeepsPricesNotChangedWhenItIsDefaultCurrency() {
		$subject = $this->getSubject();

		$price = $priceExpected= 100;
		$rates = [
			'EUR' => 1,
			'RUB' => 10
		];
		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'return' => 'EUR'
		] );
		WP_Mock::onFilter( 'wcml_price_currency' )->with( 'EUR' )->reply( 'EUR' );
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		$meta = [
			[
				'key' => '_price',
				'value' => [
					$price
				]
			]
		];
		$metaExpected = [
			[
				'key' => '_price',
				'value' => [
					$priceExpected
				]
			]
		];

		$subject->setupCurrencies();
		$subject->storeExchangeRates( $rates );
		$result = $subject->priceInDefaultCurrency( $meta );

		$this->assertSame( $result, $metaExpected );
	}

	/**
	 * @test
	 */
	public function itKeepsPricesNotChangedWhenMulticurrencyIsOff() {
		$subject = $this->getSubject();

		$price = $priceExpected = 100;
		$rates = [
			'EUR' => 1,
			'RUB' => 10
		];
		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'return' => 'EUR'
		] );
		WP_Mock::onFilter( 'wcml_price_currency' )->with( 'EUR' )->reply( 'RUB' );
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => false
		] );

		$meta = [
			[
				'key' => '_price',
				'value' => [
					$price
				]
			]
		];
		$metaExpected = [
			[
				'key' => '_price',
				'value' => [
					$priceExpected
				]
			]
		];

		$subject->setupCurrencies();
		$subject->storeExchangeRates( $rates );
		$result = $subject->priceInDefaultCurrency( $meta );

		$this->assertSame( $result, $metaExpected );
	}

	/**
	 * @test
	 */
	public function itKeepsMetaUntouchedIfItIsNotPrice() {
		$subject = $this->getSubject();

		$rates = [
			'EUR' => 1,
			'RUB' => 10
		];
		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'return' => 'EUR'
		] );
		WP_Mock::onFilter( 'wcml_price_currency' )->with( 'EUR' )->reply( 'RUB' );
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		] );

		$meta = $metaExpected = [];

		$subject->setupCurrencies();
		$subject->storeExchangeRates( $rates );
		$result = $subject->priceInDefaultCurrency( $meta );

		$this->assertSame( $result, $metaExpected );
	}

	private function getSubject() {
		return new WCML\Compatibility\WOOF\WooCommerceProductFilter();
	}
}