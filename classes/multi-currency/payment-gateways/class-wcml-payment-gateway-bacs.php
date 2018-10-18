<?php

/**
 * Class WCML_Payment_Gateway_Bacs
 */
class WCML_Payment_Gateway_Bacs extends WCML_Payment_Gateway {

	const TEMPLATE = 'bacs.twig';

	protected function get_output_model() {
		return array(
			'strings'           => array(
				'currency_label' => __( 'Currency', 'woocommerce-multilingual' ),
				'setting_label'  => __( 'Bank Account', 'woocommerce-multilingual' ),
				'all_label'      => __( 'All Accounts', 'woocommerce-multilingual' ),
				'tooltip'        => __( 'Set the currency in which your customer will see the final price when they checkout. Choose which accounts they will see in their payment message.', 'woocommerce-multilingual' )
			),
			'gateway_id'        => $this->get_id(),
			'gateway_title'     => $this->get_title(),
			'current_currency'  => $this->current_currency,
			'gateway_settings'  => $this->get_setting( $this->current_currency ),
			'active_currencies' => $this->active_currencies,
			'account_details'   => $this->get_gateway()->account_details,
		);
	}

	protected function get_output_template() {
		return self::TEMPLATE;
	}

}