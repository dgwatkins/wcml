<?php

namespace WCML\Reports\Orders;

use WCML\StandAlone\IStandAloneAction;

class Factory implements \IWPML_Backend_Action_Loader, \IWPML_REST_Action_Loader, \IWPML_Deferred_Action_Loader, IStandAloneAction {

	/**
	 * @return string
	 */
	public function get_load_action() {
		return 'init';
	}

	/**
	 * @return \IWPML_Action
	 */
	public function create() {
		/**
		 * @global \wpdb $GLOBALS['wpdb']
		 * @name $wpdb
		 */
		global $wpdb;

		return new Hooks( $wpdb );
	}
}
