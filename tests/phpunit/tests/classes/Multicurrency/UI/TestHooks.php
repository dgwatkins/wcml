<?php

namespace WCML\Multicurrency\UI;

/**
 * @group multicurrency
 */
class TestHooks extends \OTGS_TestCase {

	const DEFAULT_CURRENCY = 'USD';
	const UPDATED = '2020-05-18 08:00:00';
	const NONCE = 'the nonce';

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'loadAssets' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itLoadsAssets() {
		$activeLangs = [
			'en' => [
				'code'         => 'en',
				'display_name' => 'English',
			],
			'fr' => [
				'code'         => 'fr',
				'display_name' => 'French',
			],
		];

		$currencies = [
			self::DEFAULT_CURRENCY => [
				'code'      => self::DEFAULT_CURRENCY,
				'languages' => [ 'en' => 1 ],
				'foo'       => 'bar1',
			],
			'EUR' => [
				'code'      => 'EUR',
				'languages' => [ 'en' => 1, 'fr' => 1 ],
				'foo'       => 'bar2',
				'updated'   => self::UPDATED,
			],
		];

		$gateway1Id       = 'gateway1';
		$gateway1Title    = 'The Gateway 1';
		$gateway1Settings = [
			self::DEFAULT_CURRENCY => [
				'currency' => self::DEFAULT_CURRENCY,
				'value'    => 'value for ' . self::DEFAULT_CURRENCY,
			],
			'EUR' => [
				'currency' => 'EUR',
				'value'    => 'value for EUR',
			],
		];

		$gateway1 = $this->getGateway( $gateway1Id, $gateway1Title, $gateway1Settings );

		$gateways = [ $gateway1 ];

		$defaultCurrencies = [
			'en' => self::DEFAULT_CURRENCY,
			'fr' => '0',
		];

		$codeToFlag = function( $code ) { return "flag:$code"; };

		$multiCurrency = $this->getMulticurrency();
		$multiCurrency->method( 'get_currencies' )
			->with( true )
			->willReturn( $currencies );

		$currenciesPaymentGateways = $this->getCurrenciesPaymentGateways();
		$currenciesPaymentGateways->method( 'is_enabled' )
			->willReturnMap( [
				[ self::DEFAULT_CURRENCY, false ],
				[ 'EUR', true ],
			] );
		$currenciesPaymentGateways->method( 'get_gateways' )
			->willReturn( $gateways );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_active_languages' )
			->willReturn( $activeLangs );
		$sitepress->method( 'get_flag_url' )
			->willReturnCallback( $codeToFlag );

		$subject = $this->getSubject( $multiCurrency, $currenciesPaymentGateways, $sitepress, $defaultCurrencies );

		\WP_Mock::userFunction( 'wp_enqueue_script', [
			'times' => 1,
			'args'  => [
				Hooks::HANDLE,
				WCML_PLUGIN_URL . '/dist/js/multicurrencyOptions/app.js',
				[],
				WCML_VERSION
			],
		] );

		\WP_Mock::userFunction( 'wp_create_nonce', [
			'args'   => [ Hooks::HANDLE ],
			'return' => self::NONCE,
		] );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'return' => self::DEFAULT_CURRENCY,
		] );

		\WP_Mock::userFunction( 'get_woocommerce_currencies', [
			'return' => [
				'USD' => 'US Dollar',
				'EUR' => 'Euro',
				'GBP' => 'Pound Sterling',
			],
		] );

		\WP_Mock::userFunction( 'get_woocommerce_currency_symbol', [
			'return' => function( $code ) {
				return wpml_collect( [
					'USD' => '$',
					'EUR' => '€',
					'GBP' => '£',
				] )->get( $code );
			}
		] );

		\WP_Mock::passthruFunction( 'add_query_arg' );

		\WP_Mock::userFunction( 'wp_localize_script', [
			'times'  => 1,
			'return' => function( $handle, $varName, $data ) {
				$this->assertEquals( Hooks::HANDLE, $handle );
				$this->assertEquals( 'wcmlMultiCurrency', $varName );
				$this->assertEquals( self::NONCE, $data['nonce'] );
				$this->checkActiveCurrencies( $data['activeCurrencies'] );
				$this->checkAllCurrencies( $data['allCurrencies'] );
				$this->checkLanguages( $data['languages'] );
				$this->checkGateways( $data['gateways'] );
				$this->assertInternalType( 'array', $data['strings'] );
			}
		] );

		\WP_Mock::userFunction( 'wp_enqueue_style', [
			'times' => 1,
			'args'  => [
				Hooks::HANDLE,
				WCML_PLUGIN_URL . '/dist/css/multicurrencyOptions/styles.css',
				[],
				WCML_VERSION
			],
		] );

		$subject->loadAssets();
	}

	private function checkActiveCurrencies( array $currencies ) {
		$this->assertCount( 2, $currencies );

		foreach ( $currencies as $currency ) {
			$this->assertTrue( isset(
				$currency['code'],
				$currency['isDefault'],
				$currency['languages'],
				$currency['gatewaysEnabled'],
				$currency['gatewaysSettings']
			) );

			$isDefault = self::DEFAULT_CURRENCY === $currency['code'];

			$this->assertEquals(
				$isDefault ? [ 'en' => 1 ] : [ 'en' => 1, 'fr' => 1 ],
				$currency['languages']
			);

			$this->assertEquals(
				$isDefault ? false : true,
				$currency['gatewaysEnabled']
			);

			$this->assertEquals(
				$isDefault ? null :
					'Set on ' . date( 'F j, Y g:i a', strtotime( self::UPDATED ) ),
				$currency['formattedLastRateUpdate']
			);

			$this->assertSame( $isDefault ? true : false, $currency['isDefault'] );
			$this->assertEquals(
				'value for ' . $currency['code'],
				$currency['gatewaysSettings']['gateway1']['value']
			);
		}
	}

	private function checkAllCurrencies( array $allCurrencies ) {
		$this->assertEquals( [
				(object) [ 'code' => 'USD', 'label' => 'US Dollar', 'symbol' => '$' ],
				(object) [ 'code' => 'EUR', 'label' => 'Euro', 'symbol' => '€' ],
				(object) [ 'code' => 'GBP', 'label' => 'Pound Sterling', 'symbol' => '£' ],
			],
			$allCurrencies
		);
	}

	private function checkLanguages( array $languages ) {
		$this->assertEquals( [
				(object) [ 'code' => 'en', 'displayName' => 'English', 'flagUrl' => 'flag:en', 'defaultCurrency' => 'USD' ],
				(object) [ 'code' => 'fr', 'displayName' => 'French', 'flagUrl' => 'flag:fr', 'defaultCurrency' => '0' ],
			],
			$languages
		);
	}

	private function checkGateways( array $gateways ) {
		$this->assertEquals( [
				[
					'id'       => 'gateway1',
					'title'    => 'The Gateway 1',
					'settings' => [
						'USD' => [
							'currency' => 'USD',
							'value'    => 'value for USD',
						],
						'EUR' => [
							'currency' => 'EUR',
							'value'    => 'value for EUR',
						],
					],
				],
			],
			$gateways
		);
	}

	private function getSubject( $multiCurrency = null, $currenciesPaymentGateways = null, $sitepress = null, $defaultCurrencies = [] ) {
		$multiCurrency             = $multiCurrency ?: $this->getMulticurrency();
		$currenciesPaymentGateways = $currenciesPaymentGateways ?: $this->getCurrenciesPaymentGateways();
		$sitepress                 = $sitepress ?: $this->getSitepress();

		return new Hooks( $multiCurrency, $currenciesPaymentGateways, $sitepress, $defaultCurrencies );
	}

	private function getMulticurrency() {
		return $this->getMockBuilder( '\WCML_Multi_Currency' )
			->setMethods( [ 'get_currencies' ] )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getCurrenciesPaymentGateways() {
		return $this->getMockBuilder( '\WCML_Currencies_Payment_Gateways' )
			->disableOriginalConstructor()
			->setMethods( [ 'is_enabled', 'get_gateways' ] )
			->getMock();
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'get_active_languages', 'get_flag_url' ] )
			->getMock();
	}

	private function getGateway( $id, $title, $settings ) {
		$gateway = $this->getMockBuilder( '\WCML_Payment_Gateway' )
			->setMethods( [ 'get_output_model' ] )
			->disableOriginalConstructor()
			->getMock();

		$gateway->method( 'get_output_model' )
			->willReturn( [
				'id'       => $id,
				'title'    => $title,
				'settings' => $settings,
			] );

		return $gateway;
	}
}