<?php

/**
 * Class WCML_Payment_Gateway_Bacs
 */
class WCML_Payment_Gateway_Bacs extends WCML_Payment_Gateway {

	public function get_settings_output( $current_currency, $active_currencies ){

		$ui_settings = new WCML_Bacs_Gateway_UI( $current_currency, $active_currencies, $this );

		return $ui_settings->render();
	}

}