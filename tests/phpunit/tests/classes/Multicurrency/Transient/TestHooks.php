<?php

namespace WCML\Multicurrency\Transient;

use WPML\LIB\WP\OnActionMock;
use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Settings as McSettings;

/**
 * @group multicurrency
 */
class TestHooks extends \OTGS_TestCase {

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
	public function itShouldAddCurrencyCodeWhenSetting() {
		$key      = 'transient-key';
		$currency = 'EUR';
		$value    = '{}';

		\WP_Mock::userFunction( 'WCML\functions\getClientCurrency', [
			'return' => $currency,
		] );

		\WP_Mock::userFunction( 'get_transient', [
			'args'   => [ $key . '_' . $currency ],
			'return' => $value,
		] );

		Hooks::addHooks( $key );

		$this->assertEquals(
			$value,
			$this->runFilter( 'pre_transient_' . $key )
		);
	}

	/**
	 * @test
	 */
	public function itShouldAddCurrencyCodeWhenGetting() {
		$key      = 'transient-key';
		$currency = 'EUR';
		$value    = '{}';

		\WP_Mock::userFunction( 'WCML\functions\getClientCurrency', [
			'return' => $currency,
		] );

		\WP_Mock::userFunction( 'set_transient', [
			'args'   => [ $key . '_' . $currency, $value ],
			'return' => true,
		] );

		Hooks::addHooks( $key );

		$this->assertTrue(
			$this->runFilter( 'set_transient_' . $key, $value )
		);
	}

	/**
	 * @test
	 */
	public function itShouldRemoveAllWhenDeleting() {
		$key        = 'transient-key';
		$currencies = [ 'GBP', 'EUR', 'USD' ];

		FunctionMocker::replace( McSettings::class . '::getActiveCurrencyCodes', $currencies );

		foreach ( $currencies as $currency ) {
			\WP_Mock::userFunction( 'delete_transient', [
				'times' => 1,
				'args'  => [ $key . '_' . $currency ],
			] );	
		}

		Hooks::addHooks( $key );

		$this->runAction( 'delete_transient_' . $key );
	}

}
