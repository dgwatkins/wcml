<?php

class WCML_Admin_Restriction_Factory {
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	/**
	 * @return WCML_Admin_Restriction
	 */
	public function create() {
		global $wpdb, $sitepress, $pagenow;

		return new WCML_Admin_Restriction($this->woocommerce_wpml, $wpdb->prefix, $sitepress, $pagenow);
	}
}
