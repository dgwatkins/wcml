<?php

/**
 * @author OnTheGo Systems
 */
class WCML_Factory {
	private $sitepress;

	/**
	 * WCML_Factory constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function create() {
		$wcml = new WooCommerce_WPML( $this->sitepress );

		$widgets = new WCML_Widgets( $wcml->settings );
		$widgets->init_hooks();

		$rest_api_factory = new WCML_REST_API_Factory( $this->sitepress->get_wp_api()->constant( 'WC_VERSION' ), $wcml, $this->sitepress );
		$rest_api_factory->create();

		add_action( 'init', array( $wcml, 'init' ), 2 );

		return $wcml;
	}
}
