<?php

namespace WCML\Multicurrency\UI;

use WPML\Collect\Support\Collection;

class Hooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const HANDLE = 'wcml-multicurrency-options';

	/** @var \WCML_Currencies_Payment_Gateways $currenciesPaymentGateways */
	private $currenciesPaymentGateways;

	public function __construct( \WCML_Currencies_Payment_Gateways $currenciesPaymentGateways ) {
		$this->currenciesPaymentGateways = $currenciesPaymentGateways;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'loadJs' ] );
	}

	/**
	 * @param string $hook
	 */
	public function loadJs( $hook ) {
		if ( 'woocommerce_page_wpml-wcml' === $hook ) {
			wp_enqueue_script(
				self::HANDLE,
				WCML_PLUGIN_URL . '/dist/js/multicurrencyOptions/app.js',
				[],
				WCML_VERSION
			);

			$gateways = $this->getGateways();

			wp_localize_script(
				self::HANDLE,
				'wcmlMultiCurrency',
				[
					'nonce'             => wp_create_nonce( self::HANDLE ),
					'activeCurrencies'  => $this->getActiveCurrencies( $gateways ),
					'allCurrencies'     => $this->getAllCurrencies(),
					'languages'         => $this->getLanguages(),
					'gateways'          => $gateways->toArray(),
					'strings'           => $this->getStrings(),
					'mode'              => get_option( 'wcml_currency_mode', 'by_language' ),
				]
			);

			wp_enqueue_style(
				self::HANDLE,
				WCML_PLUGIN_URL . '/dist/css/multicurrencyOptions/styles.css',
				[],
				WCML_VERSION
			);
		}
	}

	public function getActiveCurrencies( Collection $gateways ) {
		global $woocommerce_wpml;

		$defaultCurrency = wcml_get_woocommerce_currency_option();

		$buildActiveCurrency = function( $currency, $code ) use ( $defaultCurrency ) {
			return array_merge(
				$currency,
				[
					'code'            => $code,
					'isDefault'       => $code === $defaultCurrency,
					'languages'       => array_map( 'intval', $currency['languages'] ),
					'gatewaysEnabled' => $this->getPaymentGateways()->is_enabled( $code ),
				]
			);
		};

		$getGatewaySettingsForCurrency = function( $code ) use ( $gateways ) {
			return $gateways->mapWithKeys( function( array $gateway ) use ( $code ) {
				return [
					$gateway['id'] => isset( $gateway['settings'][ $code ] ) ? $gateway['settings'][ $code ] : [],
				];
			} )->toArray();
		};

		$addGatewaysSettings = function( $currency, $code ) use ( $getGatewaySettingsForCurrency ) {
			return array_merge(
				$currency,
				[ 'gatewaysSettings' => $getGatewaySettingsForCurrency( $code ) ]
			);
		};

		$addFormattedLastRateUpdate = function( $currency ) {
			return array_merge(
				$currency,
				[
					'formattedLastRateUpdate' => isset( $currency['updated'] )
						? self::formatLastRateUpdate( $currency['updated'] )
						: null
				]
			);
		};

		return wpml_collect( $woocommerce_wpml->multi_currency->get_currencies( true ) )
			->map( $buildActiveCurrency )
			->map( $addFormattedLastRateUpdate )
			->map( $addGatewaysSettings )
			->values()
			->toArray();
	}

	public function getAllCurrencies() {
		$buildCurrency = function( $label, $code ) {
			return (object) [
				'code'   => $code,
				'label'  => $label,
				'symbol' => html_entity_decode( get_woocommerce_currency_symbol( $code ) ),
			];
		};

		return wpml_collect( get_woocommerce_currencies() )->map( $buildCurrency )->values()->toArray();
	}

	public function getLanguages() {
		global $sitepress, $woocommerce_wpml;

		$defaultCurrenciesByLang = $woocommerce_wpml->settings['default_currencies'];

		$buildLanguage = function( $data ) use ( $sitepress, $defaultCurrenciesByLang ) {
			return (object) [
				'code'            => $data['code'],
				'displayName'     => $data['display_name'],
				'flagUrl'         => $sitepress->get_flag_url( $data['code'] ),
				'defaultCurrency' => isset( $defaultCurrenciesByLang[ $data['code'] ] )
					? $defaultCurrenciesByLang[ $data['code'] ]
					: false,
			];
		};

		return wpml_collect( $sitepress->get_active_languages() )
			->map( $buildLanguage )
			->values()
			->toArray();
	}

	private function getGateways() {
		$isSupported = function( \WCML_Payment_Gateway $gateway ) {
			return ! $gateway instanceof \WCML_Not_Supported_Payment_Gateway;
		};

		$buildGateway = function( \WCML_Payment_Gateway $gateway ) {
			return $gateway->get_output_model();
		};

		return wpml_collect( $this->getPaymentGateways()->get_gateways() )
			->prioritize( $isSupported )
			->map( $buildGateway )
			->values();
	}

	private function getStrings() {
		$trackingLink = new \WCML_Tracking_Link();

		return [
			'labelCurrencies'     => __( 'Currencies', 'woocommerce-multilingual' ),
			'labelCurrency'    => __( 'Currency', 'woocommerce-multilingual' ),
			'labelAddCurrency' => __( 'Add currency', 'woocommerce-multilingual' ),
			'labelRate'        => __( 'Rate', 'woocommerce-multilingual' ),
			'labelDefault'          => __( 'default', 'woocommerce-multilingual' ),
			'labelEdit'             => __( 'Edit', 'woocommerce-multilingual' ),
			'labelDefaultCurrency' => __( 'Default currency', 'woocommerce-multilingual' ),
			'tooltipDefaultCurrency'  => __( 'Switch to this currency when switching language in the front-end', 'woocommerce-multilingual' ),
			'labelKeep'    => __( 'Keep', 'woocommerce-multilingual' ),
			'labelDelete'           => __( 'Delete', 'woocommerce-multilingual' ),
			'labelCurrenciesToDisplay'       => __( 'Currencies to display for each language', 'woocommerce-multilingual' ),
			'placeholderEnableFor'       => __( 'Enable %1$s for %2$s', 'woocommerce-multilingual' ),
			'placeholderDisableFor'      => __( 'Disable %1$s for %2$s', 'woocommerce-multilingual' ),
			'labelSettings'            => __( 'Settings', 'woocommerce-multilingual' ),
			'labelAddNewCurrency'      => __( 'Add new currency', 'woocommerce-multilingual' ),
			'placeholderCurrencySettingsFor' => __( 'Currency settings for %s', 'woocommerce-multilingual' ),

			'labelSelectCurrency' => __( 'Select currency', 'woocommerce-multilingual' ),
			'labelExchangeRate'           => __( 'Exchange Rate', 'woocommerce-multilingual' ),
			'labelOnlyNumeric'    => __( 'Only numeric', 'woocommerce-multilingual' ),
			'placeholderPreviousRate'   => __( '(previous value: %s)', 'woocommerce-multilingual' ),
			'labelCurrencyPreview' => __( 'Currency Preview', 'woocommerce-multilingual' ),
			'labelPosition'       => __( 'Currency Position', 'woocommerce-multilingual' ),
			'optionLeft'        => __( 'Left', 'woocommerce-multilingual' ),
			'optionRight'       => __( 'Right', 'woocommerce-multilingual' ),
			'optionLeftSpace'  => __( 'Left with space', 'woocommerce-multilingual' ),
			'optionRightSpace' => __( 'Right with space', 'woocommerce-multilingual' ),
			'labelThousandSep' => __( 'Thousand Separator', 'woocommerce-multilingual' ),
			'labelDecimalSep' => __( 'Decimal Separator', 'woocommerce-multilingual' ),
			'labelNumDecimals'  => __( 'Number of Decimals', 'woocommerce-multilingual' ),
			'labelRounding'                => __( 'Rounding to the nearest integer', 'woocommerce-multilingual' ),
			'optionDisabled'             => __( 'Disabled', 'woocommerce-multilingual' ),
			'optionUp'                   => __( 'Up', 'woocommerce-multilingual' ),
			'optionDown'                 => __( 'Down', 'woocommerce-multilingual' ),
			'optionNearest'              => __( 'Nearest', 'woocommerce-multilingual' ),
			'labelIncrement'            => __( 'Increment for nearest integer', 'woocommerce-multilingual' ),
			'tooltipIncrement'    => sprintf( __( 'The resulting price will be an increment of this value after initial rounding.%se.g.:', 'woocommerce-multilingual' ), '<br>' ) . '<br />' .
				__( '1454.07 &raquo; 1454 when set to 1', 'woocommerce-multilingual' ) . '<br />' .
				__( '1454.07 &raquo; 1450 when set to 10', 'woocommerce-multilingual' ) . '<br />' .
				__( '1454.07 &raquo; 1500 when set to 100', 'woocommerce-multilingual' ) . '<br />',
			'tooltipRounding'     => sprintf( __( 'Round the converted price to the closest integer. %se.g. 15.78 becomes 16.00', 'woocommerce-multilingual' ), '<br />' ),
			'tooltipAutosubtract' => __( 'The value to be subtracted from the amount obtained previously.', 'woocommerce-multilingual' ) . '<br /><br />' .
				__( 'For 1454.07, when the increment for the nearest integer is 100 and the auto-subtract amount is 1, the resulting amount is 1499.', 'woocommerce-multilingual' ),
			'labelAutosubtract'        => __( 'Autosubtract amount', 'woocommerce-multilingual' ),
			'labelPaymentGateways'          => __( 'Payment Gateways', 'woocommerce-multilingual' ),
			'placeholderCustomSettings' => __( 'Custom settings for %s', 'woocommerce-multilingual' ),
			'linkUrlLearn'      => $trackingLink->generate( 'https://wpml.org/?page_id=290080#payment-gateways-settings', 'payment-gateways-settings', 'documentation' ),
			'linkLabelLearn'    => __( 'Learn more', 'woocommerce-multilingual' ),
			'errorInvalidNumber'     => __( 'Please enter a valid number', 'woocommerce-multilingual' ),
			'labelCancel'           => __( 'Cancel', 'woocommerce-multilingual' ),
			'labelSave'             => __( 'Save', 'woocommerce-multilingual' ),
		];
	}

	/**
	 * @return \WCML_Currencies_Payment_Gateways
	 */
	private function getPaymentGateways() {
		return $this->currenciesPaymentGateways;
	}

	/**
	 * @param string $lastRateUpdate
	 *
	 * @return string|null
	 */
	public static function formatLastRateUpdate( $lastRateUpdate ) {
		return $lastRateUpdate
			? sprintf(
				__( 'Set on %s', 'woocommerce-multilingual' ),
				date( 'F j, Y g:i a', strtotime( $lastRateUpdate ) )
			)
			: null;
	}
}