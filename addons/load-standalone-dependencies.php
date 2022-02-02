<?php
/**
 * This bootstrap file is loaded only when WCML is running in the Standalone mode.
 * It's executed on `plugins_loaded` priority 10000.
 *
 * @see load_wcml_without_wpml
 */

/**
 * This constant `WCML_WPML_DEPENDENCY_URL` is used in several places inside
 * the dependency files (it replaces hard-coded URL base).
 */
define( 'WCML_WPML_DEPENDENCY_URL', WCML_PLUGIN_URL . '/addons/vendor/wpml/wpml-dependencies/lib' );

require_once WCML_PLUGIN_PATH . '/addons/vendor/autoload.php';

if ( is_admin() ) {
	require_once WCML_PLUGIN_PATH . '/addons/vendor/wpml/wpml-dependencies/lib/inc/icl-admin-notifier.php';

	$vendor_root_url = WCML_PLUGIN_URL . '/addons/vendor';
	require_once WCML_PLUGIN_PATH . '/addons/vendor/otgs/icons/loader.php';

	( new \WCML\StandAlone\DependencyAssets( WCML_WPML_DEPENDENCY_URL ) )->add_hooks();
}
