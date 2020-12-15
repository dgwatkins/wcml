<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

/**
 * Class ExchangeRatesApi
 */
class ExchangeRatesApi extends Service {

	/**
	 * @return string
	 */
	public function getId() {
		return 'exchangeratesapi';
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Exchange rates API';
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return 'https://exchangeratesapi.io/';
	}

	/**
	 * @return string
	 */
	public function getApiUrl() {
		return 'https://api.exchangeratesapi.io/latest?base=%1$s&symbols=%2$s';
	}

	/**
	 * @return bool
	 */
	public function isKeyRequired() {
		return false;
	}

}
