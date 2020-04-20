<?php

namespace WCML\Multicurrency\UI;

use WPML\Collect\Support\Collection;

class Hooks implements \IWPML_Backend_Action {

	const HANDLE = 'wcml-multicurrency-options';

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
			return $gateways->mapWithKeys( function( \stdClass $gateway ) use ( $code ) {
				return [
					$gateway->id => isset( $gateway->settings[ $code ] ) ? $gateway->settings[ $code ] : [],
				];
			} )->toArray();
		};

		$addGatewaysSettings = function( $currency, $code ) use ( $getGatewaySettingsForCurrency ) {
			return array_merge(
				$currency,
				[ 'gatewaySettings' => $getGatewaySettingsForCurrency( $code ) ]
			);
		};

		return wpml_collect( $woocommerce_wpml->multi_currency->get_currencies( true ) )
			->map( $buildActiveCurrency )
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

		$buildGateway = function( \WCML_Payment_Gateway $gateway ) use ( $isSupported ) {
			return (object) [
				'id'          => $gateway->get_id(),
				'title'       => $gateway->get_title(),
				'isSupported' => $isSupported( $gateway ),
				'settings'    => $gateway->get_settings(),
			];
		};

		return wpml_collect( $this->getPaymentGateways()->get_gateways() )
			->prioritize( $isSupported )
			->map( $buildGateway )
			->values();
	}

	/**
	 * @todo: Move it as a dependency
	 *
	 * @return \WCML_Currencies_Payment_Gateways
	 */
	private function getPaymentGateways() {
		/** @var \woocommerce_wpml */
		global $woocommerce_wpml;

		return $woocommerce_wpml->multi_currency->currencies_payment_gateways;
	}
}