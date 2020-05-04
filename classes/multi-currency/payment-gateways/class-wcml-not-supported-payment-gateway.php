<?php

/**
 * Class WCML_Not_Supported_Payment_Gateway
 */
class WCML_Not_Supported_Payment_Gateway extends WCML_Payment_Gateway{

	const TEMPLATE = 'not-supported.twig';

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => false,
			'settings'    => [],
			'tooltip'     => '',
			'strings'     => [
				'labelNotYetSupported' => __( 'Not yet supported', 'woocommerce-multilingual' ),
			],
		];
	}

	protected function get_output_template() {
		return self::TEMPLATE;
	}

}