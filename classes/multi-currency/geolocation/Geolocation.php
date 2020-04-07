<?php

namespace WCML\MultiCurrency;

class Geolocation {

	const DEFAULT_COUNTRY_CURRENCY_CONFIG = 'country-currency.json';

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

		$country = self::getCountryByUserIp();

		if ( $country ) {
			$config = self::parseConfigFile();

			return isset( $config[ $country ] ) ? $config[ $country ] : false;
		}

		return false;
	}
}
