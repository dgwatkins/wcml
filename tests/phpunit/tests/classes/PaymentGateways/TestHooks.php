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

		\WP_Mock::expectActionAdded( 'woocommerce_settings_checkout', [ $subject, 'output' ], $subject::PRIORITY );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'loadAssets' ] );
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

	/**
	 * @test
	 */
	public function itLoadsAssets() {
		\WP_Mock::passthruFunction( 'sanitize_title' );

		$gatewayId       = 'bacs';
		$settings        = [
			$gatewayId => [
				'mode'      => 'include',
				'countries' => [ 'UA' ]
			]
		];
		$_GET['section'] = $gatewayId;

		$WC = $this->getMockBuilder( 'WooCommerce' )
		           ->disableOriginalConstructor()
		           ->getMock();

		$WC->countries = $this->getMockBuilder( 'WC_Countries' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( [ 'get_countries' ] )
		                      ->getMock();

		$WC->countries->method( 'get_countries' )->willReturn( $this->getCountriesList() );

		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ Hooks::OPTION_KEY, false ],
			'return' => $settings,
		] );

		\WP_Mock::userFunction( 'WC', [
			'return' => $WC,
			'times'  => 1,
		] );

		$enqueueResources = FunctionMocker::replace( '\WPML\LIB\WP\App\Resources::enqueue', function ( $app, $pluginBaseUrl, $pluginBasePath, $version, $domain, $localize ) use ( $settings, $gatewayId ) {
			$this->assertEquals( 'paymentGatewaysAdmin', $app );
			$this->assertEquals( WCML_PLUGIN_URL, $pluginBaseUrl );
			$this->assertEquals( WCML_PLUGIN_PATH, $pluginBasePath );
			$this->assertEquals( WCML_VERSION, $version );
			$this->assertEquals( 'woocommerce-multilingual', $domain );
			$this->assertEquals( 'wcmlPaymentGateways', $localize['name'] );
			$this->assertEquals( Hooks::OPTION_KEY, $localize['data']['endpoint'] );
			$this->checkAllCountries( $localize['data']['allCountries'] );
			$this->assertEquals( $settings[ $gatewayId ], $localize['data']['settings'] );
			$this->assertEquals( $gatewayId, $localize['data']['gatewayId'] );
			$this->assertInternalType( 'array', $localize['data']['strings'] );
		} );

		$subject->loadAssets();

		$enqueueResources->wasCalledOnce();
	}

	private function checkAllCountries( array $allCountries ) {

		$expectedCountries = [];

		foreach ( $this->getCountriesList() as $code => $country ) {
			$object              = new \stdClass();
			$object->code        = $code;
			$object->label       = html_entity_decode( $country );
			$expectedCountries[] = $object;
		}

		return $allCountries;

		$this->assertEquals( $expectedCountries, $allCountries );
	}


	private function getCountriesList() {
		return [
			'AF' => 'Afghanistan',
			'AX' => '&#197;land Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
		];
	}

	/**
	 * @test
	 */
	public function itShouldAddOutput() {

		$_GET['section'] = 'bacs';
		$_GET['page']    = 'wc-settings';

		$subject = $this->getSubject();

		ob_start();
		$subject->output();

		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals( '<div id="wcml-payment-gateways"></div>', $output );

		unset( $_GET );
	}
}