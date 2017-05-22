<?php

/**
 * @author OnTheGo Systems
 */
class WCML_Factory {

	public function create() {
		$wcml = new WooCommerce_WPML();

		$widgets = new WCML_Widgets( $wcml->settings );
		$widgets->init_hooks();

		return $wcml;
	}
}