<?php
define( 'WPML_TESTS_SITE_DIR', __DIR__ . '/site' );
define( 'WPML_TESTS_SITE_URL', 'http://domain.tld' );

define( 'WPML_TESTS_MAIN_FILE', __DIR__ . '/../../sitepress.php' );
define( 'WPML_PATH', dirname( WPML_TESTS_MAIN_FILE ) );

/** WP Constants */
define( 'WP_CONTENT_URL', WPML_TESTS_SITE_URL . '/wp-content' );
define( 'WP_CONTENT_DIR', WPML_TESTS_SITE_DIR . '/wp-content' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/** WPML-Core constants */
//define( 'ICL_PLUGIN_PATH', dirname( WPML_TESTS_MAIN_FILE ) );
//define( 'ICL_PLUGIN_FILE', basename( WPML_TESTS_MAIN_FILE ) );
//define( 'ICL_PLUGIN_FULL_PATH', basename( ICL_PLUGIN_PATH ) . '/' . ICL_PLUGIN_FILE );
//define( 'ICL_PLUGIN_FOLDER', basename( ICL_PLUGIN_PATH ) );
//define( 'ICL_PLUGIN_URL', '' );
//
//require_once __DIR__ . '/../../inc/constants.php';

define( 'WCML_PLUGIN_PATH', dirname( WPML_TESTS_MAIN_FILE ) );

$autoloader_dir = WPML_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

require_once WPML_PATH . '/vendor/otgs/unit-tests-framework/phpunit/bootstrap.php';
