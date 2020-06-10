<?php

namespace WCML\MultiCurrency;

class Geolocation {

	const DEFAULT_COUNTRY_CURRENCY_CONFIG = 'country-currency.json';
	const MODE_BY_LANGUAGE = 'by_language';
	const MODE_BY_LOCATION = 'by_location';

	/**
	 * Get country code by user IP
	 *
	 * @return string|bool
	 */
	private static function getCountryByUserIp() {

		$ip = \WC_Geolocation::get_ip_address();

		$country_info = \WC_Geolocation::geolocate_ip( $ip, true );

		return isset( $country_info['country'] ) ? $country_info['country'] : false;
	}

	/**
	 * Get country currency config file
	 *
	 * @return array
	 */
	private static function parseConfigFile() {
		$config             = [];
		$configuration_file = WCML_PLUGIN_PATH . '/res/geolocation/' . self::DEFAULT_COUNTRY_CURRENCY_CONFIG;

		if ( file_exists( $configuration_file ) ) {
			$json_content = file_get_contents( $configuration_file );
			$config       = json_decode( $json_content, true );
		}

		return $config;
	}

	/**
	 * Get currency code by user country
	 *
	 * @return string|bool
	 */
	public static function getCurrencyCodeByUserCountry() {

		$country = self::getUserCountry();

		if ( $country ) {
			$config = self::parseConfigFile();

			return isset( $config[ $country ] ) ? $config[ $country ] : false;
		}

		return false;
	}

	/**
	 * @return bool|string
	 */
	private static function getUserCountry(){
		$billing_country = self::getUserBillingCountry();
		return $billing_country ?: self::getCountryByUserIp();
	}

	/**
	 * Get country code from billing if user logged-in
	 *
	 * @return null|string
	 */
	private static function getUserBillingCountry() {
		$current_user_id = get_current_user_id();

		if ( $current_user_id ) {
			$customer = new \WC_Customer( $current_user_id );

			if ( $customer ) {
				return $customer->get_billing_country();
			}
		}

		return null;
	}

	/**
	 * @param array $currencySettings
	 *
	 * @return bool
	 */
	public static function isCurrencyAvailableForCountry( $currencySettings ) {

		if ( 'all' === $currencySettings['location_mode'] ) {
			return true;
		}

		if ( 'include' === $currencySettings['location_mode'] && in_array( self::getUserCountry(), $currencySettings['countries'] ) ) {
			return true;
		}

		if ( 'exclude' === $currencySettings['location_mode'] && ! in_array( self::getUserCountry(), $currencySettings['countries'] ) ) {
			return true;
		}

		return false;
	}
}