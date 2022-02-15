<?php

namespace WCML\MultiCurrency\Resolver;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestResolverForDefault extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldGetClientCurrencyFromDefaultOption() {
		$defaultCurrency = 'EUR';

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option' )->andReturn( $defaultCurrency );

		$this->assertEquals( $defaultCurrency, ( new ResolverForDefault() )->getClientCurrency() );
	}
}
