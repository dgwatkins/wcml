<?php

namespace WCML\functions;

use WPML\FP\Obj;

use function WPML\FP\curryN;

if ( ! function_exists( 'WCML\functions\getId' ) ) {
	/**
	 * Returns the object id.
	 *
	 * @param \stdClass|\WP_Post|\WC_Order|\WC_Data|array|null $object The object to get id.
	 * @return int|callable|null
	 */
	function getId( $object = null ) {
		$getId = function ( $object ) {
			return is_object( $object ) && method_exists( $object, 'get_id' )
				? $object->get_id()
				: Obj::prop( 'ID', $object );
		};
		return call_user_func_array( curryN( 1, $getId ), func_get_args() );
	}
}

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
