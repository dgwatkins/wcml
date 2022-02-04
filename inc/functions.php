<?php

namespace WCML\functions;

use function WPML\Container\make;

if ( ! function_exists( 'WCML\functions\getSitePress' ) ) {
	/**
	 * @global \SitePress|null $sitepress
	 * @return \SitePress|\WCML\StandAlone\NullSitePress
	 */
	function getSitePress() {
		global $sitepress;

		if ( null === $sitepress ) {
			return new \WCML\StandAlone\NullSitePress();
		}
		return $sitepress;
	}
}

if ( ! function_exists( 'WCML\functions\isStandAlone' ) ) {
	/**
	 * Test whether we are running in standalone mode.
	 *
	 * @return bool
	 */
	function isStandAlone() {
		return ! defined( 'ICL_SITEPRESS_VERSION' );
	}
}

if ( ! function_exists( 'WCML\functions\assetLink' ) ) {
	/**
	 * Return correct link to asset
	 *
	 * @param  string $asset
	 * @return string
	 */
	function assetLink( $asset ) {
		if ( isStandAlone() ) {
			return WCML_PLUGIN_URL . '/addons/vendor/wpml/wpml-dependencies/lib' . $asset;
		}
		return ICL_PLUGIN_URL . $asset;
	}
}

if ( ! function_exists( '\WCML\functions\getSetting' ) ) {
	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	function getSetting( $key, $default = null ) {
		return make( \woocommerce_wpml::class )->get_setting( $key, $default );
	}
}

if ( ! function_exists( '\WCML\functions\updateSetting' ) ) {
	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $autoload
	 *
	 * @return void
	 */
	function updateSetting( $key, $value, $autoload = false ) {
		make( \woocommerce_wpml::class )->update_setting( $key, $value, $autoload );
	}
}
