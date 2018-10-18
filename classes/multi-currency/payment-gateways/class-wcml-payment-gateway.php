<?php

/**
 * Class WCML_Payment_Gateway
 */
abstract class WCML_Payment_Gateway {

	/**
	 * @var WC_Payment_Gateway
	 */
	private $gateway;

	private $settings = array();

	const OPTION_KEY = 'wcml_payment_gateway_';

	/**
	 * @param WC_Payment_Gateway $gateway
	 */
	public function __construct( WC_Payment_Gateway $gateway ) {
		$this->gateway  = $gateway;
		$this->settings = get_option( self::OPTION_KEY . $this->get_id(), array() );
	}

	/**
	 * @return WC_Payment_Gateway
	 */
	public function get_gateway(){
		return $this->gateway;
	}

	/**
	 * @return string
	 */
	public function get_id(){
		return $this->gateway->id;
	}

	/**
	 * @return string
	 */
	public function get_title(){
		return $this->gateway->title;
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	private function save_settings() {
		update_option( self::OPTION_KEY . $this->get_id(), $this->settings );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get_setting( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : null;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function save_setting( $key, $value ) {
		$this->settings[ $key ] = $value;
		$this->save_settings();
	}

}