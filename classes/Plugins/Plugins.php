<?php

namespace WCML;

class Plugins {

	public static function maybeLoadCoreFirst() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}

		$plugins = get_option( 'active_plugins' );

		if ( in_array( 'sitepress-multilingual-cms/sitepress.php', $plugins, true ) ) {
			add_action( 'plugins_loaded', [ __CLASS__, 'loadCoreFirst' ] );
		}
	}

	public static function loadCoreFirst() {
		if ( class_exists( '\WPML\Plugins' ) ) {
			\WPML\Plugins::loadCoreFirst();
		}
	}
}
