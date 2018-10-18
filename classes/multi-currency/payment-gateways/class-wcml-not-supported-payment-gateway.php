<?php

/**
 * Class WCML_Not_Supported_Payment_Gateway
 */
class WCML_Not_Supported_Payment_Gateway {

	/**
	 * @var WC_Payment_Gateway
	 */
	private $gateway;

	/**
	 * WCML_Not_Supported_Payment_Gateway constructor.
	 *
	 * @param WC_Payment_Gateway  $gateway
	 */
	public function __construct( WC_Payment_Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->gateway->title;
	}

	/**
	 * @return bool
	 */
	public function is_supported() {
		return false;
	}

}