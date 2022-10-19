<?php

namespace WCML\AdminTexts;

class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader, \IWPML_Deferred_Action_Loader {

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
		return new Hooks();
	}

}
