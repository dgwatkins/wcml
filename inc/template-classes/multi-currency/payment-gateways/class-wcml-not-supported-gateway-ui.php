<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Not_Supported_Gateway_UI extends WPML_Templates_Factory {

	/**
	 * @var string
	 */
	private $gateway_title;

	function __construct( $gateway_title ) {
		parent::__construct();

		$this->gateway_title = $gateway_title;
	}

	public function get_model() {

		$model = array(
			'strings'       => array(
				'not_supported' => __( 'Not yet supported', 'woocommerce-multilingual' )
			),
			'gateway_title' => $this->gateway_title
		);

		return $model;
	}

	public function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/multi-currency/payment-gateways/',
		);
	}

	public function get_template() {
		return 'not-supported.twig';
	}
}