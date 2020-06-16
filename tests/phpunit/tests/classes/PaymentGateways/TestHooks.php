<?php

namespace WCML\PaymentGateways;

use tad\FunctionMocker\FunctionMocker;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;

/**
 * @group payment-gateways
 * @group wcml-3235
 */
class TestHooks extends \OTGS_TestCase {

	private function getSubject() {
		return new Hooks();
	}

	/**
	 * @test
	 */
	public function itAddsAdminWcPaymentSettingsHooks() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'is_admin', [
			'return' => true,
		] );

		$_GET['section'] = 'bacs';
		$_GET['page']    = 'wc-settings';

		\WP_Mock::expectActionAdded( 'woocommerce_update_options_checkout', [
			$subject,
			'updateSettingsOnSave'
		], $subject::PRIORITY );

		\WP_Mock::expectActionAdded( 'admin_notices', [
			$subject,
			'maybeAddNotice'
		]);
		$subject->add_hooks();

		unset( $_GET );
	}

	/**
	 * @test
	 */
	public function itAddsFrontHooks() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'is_admin', [
			'return' => false,
		] );

		\WP_Mock::expectFilterAdded( 'woocommerce_available_payment_gateways', [
			$subject,
			'filterByCountry'
		], $subject::PRIORITY );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldUpdateSettingsOnSave() {

		$subject = $this->getSubject();

		$gatewaySettings = [
			'ID'        => 'bacs',
			'mode'      => 'include',
			'countries' => 'UA,ES'
		];

		$_POST[ $subject::OPTION_KEY ] = $gatewaySettings;

		$expectedSettings = [
			'bacs' => [
				'mode'      => 'include',
				'countries' => [ 'UA', 'ES' ]
			]
		];

		\WP_Mock::userFunction( 'update_option', [
			'args'   => [ $subject::OPTION_KEY, $expectedSettings ],
			'return' => true,
		] );

		$subject->updateSettingsOnSave();

		unset( $_POST );
	}

	/**
	 * @test
	 */
	public function itShouldSetToAllWhenUpdatingSettingsWithHackedData() {

		$subject = $this->getSubject();

		$gatewaySettings = [
			'ID'        => 'bacs',
			'mode'      => 'hacked',
			'countries' => 'UA,ES'
		];

		$_POST[ $subject::OPTION_KEY ] = $gatewaySettings;

		$expectedSettings = [
			'bacs' => [
				'mode'      => 'all',
				'countries' => [ 'UA', 'ES' ]
			]
		];

		\WP_Mock::userFunction( 'update_option', [
			'args'   => [ $subject::OPTION_KEY, $expectedSettings ],
			'return' => true,
		] );

		$subject->updateSettingsOnSave();

		unset( $_POST );
	}

	/**
	 * @test
	 */
	public function itShouldFilterByCountry() {

		$subject = $this->getSubject();

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getUserCountry', 'UA' );

		$gateway1     = new \stdClass();
		$gateway1->id = 'bacs';
		$gateway2     = new \stdClass();
		$gateway2->id = 'paypal';

		$paymentGateways = [ $gateway1, $gateway2 ];

		$settings = [
			'bacs'   => [
				'mode'      => 'include',
				'countries' => [ 'UA', 'ES' ]
			],
			'paypal' => [
				'mode'      => 'exclude',
				'countries' => [ 'UA' ]
			],
		];

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ $subject::OPTION_KEY, false ],
			'return' => $settings,
		] );

		$filteredGateways = $subject->filterByCountry( $paymentGateways );

		$this->assertEquals( [ $gateway1 ], $filteredGateways );

		unset( $_POST );
	}
}