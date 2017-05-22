<?php
define( 'WPML_TESTS_SITE_DIR', __DIR__ . '/site' );
define( 'WPML_TESTS_SITE_URL', 'http://domain.tld' );

define( 'WCML_TESTS_MAIN_FILE', __DIR__ . '/../../wpml-woocommerce.php' );
define( 'WCML_PATH', dirname( WCML_TESTS_MAIN_FILE ) );

define( 'WPML_TESTS_MAIN_FILE', __DIR__ . '/../../../sitepress-multilingual-cms/sitepress.php' );
define( 'WPML_PATH', dirname( WPML_TESTS_MAIN_FILE ) );

define( 'WPML_TM_TESTS_MAIN_FILE', __DIR__ . '/../../../wpml-translation-management/plugin.php' );
define( 'WPML_TM_PATH', dirname( WPML_TM_TESTS_MAIN_FILE ) );

define('WCML_MULTI_CURRENCIES_DISABLED', 0);
define('WCML_MULTI_CURRENCIES_PER_LANGUAGE', 1); //obsolete - migrate to 2
define('WCML_MULTI_CURRENCIES_INDEPENDENT', 2);

define( 'WCML_CART_CLEAR', 0);
define( 'WCML_CART_SYNC', 1);


/** WP Constants */
define( 'WP_CONTENT_URL', WPML_TESTS_SITE_URL . '/wp-content' );
define( 'WP_CONTENT_DIR', WPML_TESTS_SITE_DIR . '/wp-content' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

$autoloader_dir = WCML_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

require_once WCML_PATH . '/vendor/otgs/unit-tests-framework/phpunit/bootstrap.php';
