<?php

/**
 * Class WCML_Not_Supported_Payment_Gateway
 */
class WCML_Not_Supported_Payment_Gateway extends WCML_Payment_Gateway{

	public function get_settings_output(){
		$ui_settings = new WCML_Not_Supported_Gateway_UI( $this->get_title() );

		return $ui_settings->render();
	}

}