<?php

/**
 * @author OnTheGo Systems
 */
class WCML_REST_API_Factory {
	private $sitepress;
	private $wc_version;
	private $wcml;

	/**
	 * WCmL_REST_API_Factory constructor.
	 *
	 * @param string           $wc_version
	 * @param WooCommerce_WPML $wcml
	 * @param SitePress        $sitepress
	 */
	public function __construct( $wc_version, WooCommerce_WPML $wcml, SitePress $sitepress = null ) {
		$this->wc_version = $wc_version;
		$this->wcml       = $wcml;
		$this->sitepress  = $sitepress;
	}

	/**
	 * @return null|WCML_REST_API_Support|WCML_REST_API_Support_V1|WCML_WooCommerce_Rest_API_Support
	 */
	function create() {
		$wcml_rest_api_support = null;

		if ( $this->sitepress && $this->is_woocommerce_installed() && $this->has_woocommerce_api_enabled() ) {
			if ( $this->has_rest_api_support() && WCML_REST_API_Support::is_rest_api_request() ) {
				if ( WCML_REST_API_Support::get_api_request_version() === 1 ) {
					$wcml_rest_api_support = new WCML_REST_API_Support_V1( $this->wcml, $this->sitepress );
				} else {
					$wcml_rest_api_support = new WCML_REST_API_Support( $this->wcml, $this->sitepress );
				}
				$wcml_rest_api_support->initialize();
			} else {
				$wcml_rest_api_support = new WCML_WooCommerce_Rest_API_Support( $this->wcml, $this->sitepress );
			}
		}

		return $wcml_rest_api_support;
	}

	/**
	 * @return bool
	 */
	private function is_woocommerce_installed() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * @return mixed
	 */
	private function has_rest_api_support() {
		return $this->wc_version && version_compare( $this->wc_version, '2.6', '>=' );
	}

	/**
	 * @return bool
	 */
	private function has_woocommerce_api_enabled() {
		return 'yes' === get_option( 'woocommerce_api_enabled' );
	}
}