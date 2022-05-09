<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

/**
 * Class Fixerio
 */
class Fixerio extends ApiLayerService {

	/**
	 * @return string
	 */
	public function getId() {
		return 'fixerio';
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Fixer.io';
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return 'http://fixer.io/';
	}

	/**
	 * @return string
	 */
	protected function getApiLayerUrl() {
		return 'https://api.apilayer.com/fixer/latest?base=%1$s&symbols=%2$s';
	}

	/**
	 * @return string
	 */
	public function getApiUrl() {
		return 'http://data.fixer.io/api/latest?access_key=%1$s&base=%2$s&symbols=%3$s';
	}

	/**
	 * @return bool
	 */
	public function isKeyRequired() {
		return true;
	}

}
