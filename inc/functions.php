<?php

namespace WCML\functions;

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
		return ! ( class_exists( \SitePress::class )
				|| defined( 'ICL_SITEPRESS_VERSION' ) );
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
