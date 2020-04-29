<?php

/**
 * Class WCML_Not_Supported_Payment_Gateway
 */
class WCML_Not_Supported_Payment_Gateway extends WCML_Payment_Gateway{

	const TEMPLATE = 'not-supported.twig';

	public function get_output_model() {

		return (object) [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => false,
			'settings'    => [],
		];

		if( $this->is_current_currency_default() ){
			return array();
		}

		return array(
			'strings'       => array(
				'not_supported' => __( 'Not yet supported', 'woocommerce-multilingual' )
			),
			'gateway_title' => $this->get_title()
		);
	}

	protected function get_output_template() {
		return self::TEMPLATE;
	}

}