<?php

namespace WCML\MultiCurrency\Resolver;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestComposedResolver extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldResolveWithFirstResolver() {
		$firstResolution  = 'EUR';
		$secondResolution = 'USD';

		$resolvers = [
			self::getResolver( $firstResolution ),
			self::getResolver( $secondResolution ),
		];

		$this->assertEquals(
			$firstResolution,
			( new ComposedResolver( $resolvers ) )->getClientCurrency()
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldResolveWithSecondResolver() {
		$firstResolution  = null;
		$secondResolution = 'USD';

		$resolvers = [
			self::getResolver( $firstResolution ),
			self::getResolver( $secondResolution ),
		];

		$this->assertEquals(
			$secondResolution,
			( new ComposedResolver( $resolvers ) )->getClientCurrency()
		);
	}

	/**
	 * @param string $resolvedCurrency
	 *
	 * @return Resolver
	 */
	private static function getResolver( $resolvedCurrency ) {
		return new class( $resolvedCurrency ) implements Resolver {
			private $currency;

			public function __construct( $currency ) {
				$this->currency = $currency;
			}

			public function getClientCurrency() {
				return $this->currency;
			}
		};
	}
}
