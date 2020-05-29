<?php

namespace WCML\Multicurrency\UI;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group multicurrency
 * @group wcml-3178
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
		\WP_Mock::passthruFunction( 'admin_url' );

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

		$wcmlSettings['default_currencies'] = [
			'en' => self::DEFAULT_CURRENCY,
			'fr' => '0',
		];

		$wcmlSettings['currency_mode'] = 'by_location';

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

		$subject = $this->getSubject( $multiCurrency, $currenciesPaymentGateways, $sitepress, $wcmlSettings );

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

		$WC = $this->getMockBuilder( 'WooCommerce' )
		           ->disableOriginalConstructor()
		           ->getMock();

		$WC->countries = $this->getMockBuilder( 'WC_Countries' )
		           ->disableOriginalConstructor()
		           ->setMethods( [ 'get_countries' ] )
		           ->getMock();
		
		$WC->countries->method( 'get_countries' )->willReturn( $this->getCountriesList() );

		\WP_Mock::userFunction( 'WC', [
			'return' => $WC
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'woocommerce_maxmind_geolocation_settings' ],
			'return' => []
		] );

		\WP_Mock::passthruFunction( 'add_query_arg' );

		$enqueueResources = FunctionMocker::replace( '\WPML\LIB\WP\App\Resources::enqueue', function( $app, $pluginBaseUrl, $pluginBasePath, $version, $domain, $localize ) {
			$this->assertEquals( 'multicurrencyOptions', $app );
			$this->assertEquals( WCML_PLUGIN_URL, $pluginBaseUrl );
			$this->assertEquals( WCML_PLUGIN_PATH, $pluginBasePath );
			$this->assertEquals( WCML_VERSION, $version );
			$this->assertEquals( 'woocommerce-multilingual', $domain );
			$this->assertEquals( 'wcmlMultiCurrency', $localize['name'] );
			$this->assertEquals( Hooks::HANDLE, $localize['data']['endpoint'] );
			$this->checkActiveCurrencies( $localize['data']['activeCurrencies'] );
			$this->checkAllCurrencies( $localize['data']['allCurrencies'] );
			$this->checkLanguages( $localize['data']['languages'] );
			$this->checkGateways( $localize['data']['gateways'] );
			$this->checkAllCountries( $localize['data']['allCountries'] );
			$this->assertEquals( 'by_location', $localize['data']['mode'] );
			$this->assertFalse( $localize['data']['maxMindKeyExist'] );
			$this->assertInternalType( 'array', $localize['data']['strings'] );
		} );

		$subject->loadAssets();

		$enqueueResources->wasCalledOnce();
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

	private function checkAllCountries( array $allCountries ) {

		$expectedCountries = [];

		foreach( $this->getCountriesList() as $code => $country ){
			$object = new \stdClass();
			$object->code = $code;
			$object->label = html_entity_decode( $country );
			$expectedCountries[] = $object;
		}

		return $allCountries;

		$this->assertEquals( $expectedCountries,
			$allCountries
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

	private function getSubject( $multiCurrency = null, $currenciesPaymentGateways = null, $sitepress = null, $wcmlSettings = [] ) {
		$multiCurrency             = $multiCurrency ?: $this->getMulticurrency();
		$currenciesPaymentGateways = $currenciesPaymentGateways ?: $this->getCurrenciesPaymentGateways();
		$sitepress                 = $sitepress ?: $this->getSitepress();

		return new Hooks( $multiCurrency, $currenciesPaymentGateways, $sitepress, $wcmlSettings );
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
	
	private function getCountriesList(){
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
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'PW' => 'Belau',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BQ' => 'Bonaire, Saint Eustatius and Saba',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo (Brazzaville)',
			'CD' => 'Congo (Kinshasa)',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CW' => 'Cura&ccedil;ao',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and McDonald Islands',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'CI' => 'Ivory Coast',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Laos',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'North Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'KP' => 'North Korea',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PS' => 'Palestinian Territory',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barth&eacute;lemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (French part)',
			'SX' => 'Saint Martin (Dutch part)',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'SM' => 'San Marino',
			'ST' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia/Sandwich Islands',
			'KR' => 'South Korea',
			'SS' => 'South Sudan',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syria',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom (UK)',
			'US' => 'United States (US)',
			'UM' => 'United States (US) Minor Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'VG' => 'Virgin Islands (British)',
			'VI' => 'Virgin Islands (US)',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'WS' => 'Samoa',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		];
	}
}