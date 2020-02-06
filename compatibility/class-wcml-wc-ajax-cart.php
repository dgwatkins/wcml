<?php

class WCML_WC_Ajax_Cart {

	public function __construct() {
		add_filter( 'wcml_calculate_totals_exception', [ $this, 'wac_update_ajax' ], 9 );
	}

	public function wac_update_ajax( $exc ) {
		if ( ! empty( $_POST['is_wac_ajax'] ) ) {
			return false;
		}
		return $exc;
	}

}
