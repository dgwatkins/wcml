<?php

/**
 * Class WCML_Payment_Gateway
 */
abstract class WCML_Payment_Gateway {

	const OPTION_KEY = 'wcml_payment_gateway_';

	/**
	 * @var string
	 */
	protected $current_currency;
	/**
	 * @var array
	 */
	protected $active_currencies;
	/**
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	private $settings = array();

	/**
	 * @var \IWPML_Template_Service
	 */
	private $template_service;

	/**
	 * @param WC_Payment_Gateway $gateway
	 * @param \IWPML_Template_Service $template_service
	 */
	public function __construct( WC_Payment_Gateway $gateway, IWPML_Template_Service $template_service ) {
		$this->gateway          = $gateway;
		$this->template_service = $template_service;
		$this->settings         = get_option( self::OPTION_KEY . $this->get_id(), array() );
	}

	public function get_settings_output( $current_currency, $active_currencies ) {
		$this->current_currency  = $current_currency;
		$this->active_currencies = $active_currencies;

		return $this->template_service->show( $this->get_output_model(), $this->get_output_template() );
	}

	public function show() {
		return $this->get_settings_output();
	}

	abstract protected function get_output_model();

	abstract protected function get_output_template();

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