<?php
/**
 * This bootstrap file is loaded only when WCML is running in the Standalone mode.
 */

define( 'WCML_WPML_DEPENDENCY_URL', WCML_PLUGIN_URL . '/addons/vendor/wpml/wpml-dependencies/lib' );

require_once WCML_PLUGIN_PATH . '/addons/vendor/autoload.php';

require_once WCML_PLUGIN_PATH . '/addons/vendor/wpml/wpml-dependencies/lib/inc/icl-admin-notifier.php';
