<?php
/**
 * PHP Unit tests bootstrap file.
 *
 * @package WPML\Core
 */

use tad\FunctionMocker\FunctionMocker;

define( 'WPML_TESTS_SITE_DIR', __DIR__ . '/site' );
define( 'WPML_TESTS_SITE_URL', 'http://domain.tld' );

define( 'WCML_TESTS_MAIN_FILE', __DIR__ . '/../../wpml-woocommerce.php' );
define( 'WCML_PLUGIN_PATH', dirname( WCML_TESTS_MAIN_FILE ) );
define( 'WCML_PATH', WCML_PLUGIN_PATH );

define( 'WPML_TESTS_MAIN_FILE', __DIR__ . '/../../../sitepress-multilingual-cms/sitepress.php' );
define( 'WPML_PATH', dirname( WPML_TESTS_MAIN_FILE ) );

define( 'WPML_TM_TESTS_MAIN_FILE', __DIR__ . '/../../../wpml-translation-management/plugin.php' );
define( 'WPML_TM_PATH', dirname( WPML_TM_TESTS_MAIN_FILE ) );


/** WP Constants */
define( 'WP_CONTENT_URL', WPML_TESTS_SITE_URL . '/wp-content' );
define( 'WP_CONTENT_DIR', WPML_TESTS_SITE_DIR . '/wp-content' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define( 'WPML_TM_FOLDER',  WP_CONTENT_DIR . '/plugins/wpml-translation-management/' );
define( 'WCML_PLUGIN_URL', WP_CONTENT_DIR . '/plugins/woocommerce-multilingual/' );


if( ! defined( 'COOKIEPATH' ) ){
	define( 'COOKIEPATH', '/' );
}
if( ! defined( 'COOKIE_DOMAIN' ) ){
	define( 'COOKIE_DOMAIN', 'example.com' );
}

if( ! defined( 'WOOCOMMERCE_VERSION' ) ){
	define( 'WOOCOMMERCE_VERSION', '3.1' );
}


require_once __DIR__ . '/includes/missing-php-functions.php';

if ( !defined( 'COOKIEHASH' ) ) {
	define( 'COOKIEHASH', md5( WPML_TESTS_SITE_URL ) );
}
if ( !defined( 'COOKIEPATH' ) ) {
	define('COOKIEPATH', preg_replace('|https?://[^/]+|i', '', WPML_TESTS_SITE_URL . '/' ) );
}
if ( !defined( 'COOKIE_DOMAIN' ) ) {
	define( 'COOKIE_DOMAIN', false );
}


require_once __DIR__ . '/includes/missing-php-functions.php';

require_once WCML_PATH . '/vendor/autoload.php';

try {
	if ( ! spl_autoload_register( 'autoload_tests_classes' ) ) {
		echo 'Test classes cannot be loaded!';
		exit( 1 );
	}
} catch ( Exception $e ) {
	echo $e->getMessage();
	exit( 1 );
}

function autoload_tests_classes( $class ) {
	static $maps;

	if ( ! $maps ) {
		$dirs = [
			WCML_PATH . "/tests/phpunit/stubs",
		];

		$maps = array();
		foreach ( $dirs as $dir ) {
			$maps = array_merge( $maps, \Composer\Autoload\ClassMapGenerator::createMap( $dir ) );
		}
	}

	if ( $maps && array_key_exists( $class, $maps ) ) {
		/** @noinspection PhpIncludeInspection */
		require_once $maps[ $class ];
	}
}

FunctionMocker::init(
	[
		'blacklist' => [
			realpath( WCML_PATH ),
		],
		'whitelist' => [
			realpath( WCML_PATH . '/classes' ),
			realpath( WCML_PATH . '/compatibility' ),
			realpath( WCML_PATH . '/inc' ),
			realpath( WCML_PATH . '/tests/phpunit/includes' ),
			realpath( WCML_PATH . '/tests/phpunit/stubs' ),
		],
		'redefinable-internals' => [],
	]
);

require_once WCML_PATH . '/vendor/otgs/unit-tests-framework/phpunit/bootstrap.php';
