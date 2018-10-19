<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Not_Supported_Gateway_UI extends WPML_Twig_Template_Loader {

	const TEMPLATE = 'not-supported.twig';
	const TEMPLATE_FOLDER = '/templates/multi-currency/payment-gateways/';

	/**
	 * @var string
	 */
	private $gateway_title;

	function __construct( $gateway_title ) {
		parent::__construct( array( WCML_PLUGIN_PATH . self::TEMPLATE_FOLDER ) );

		$this->gateway_title = $gateway_title;
	}

	public function render() {

		$model = array(
			'strings'       => array(
				'not_supported' => __( 'Not yet supported', 'woocommerce-multilingual' )
			),
			'gateway_title' => $this->gateway_title
		);

		return $this->get_template()->show( $model, self::TEMPLATE );
	}

}