<?php
if ( ! defined( 'WPML_CORE_PATH' ) ) {
	define( 'WPML_CORE_PATH', dirname( __FILE__ ) . '/../../sitepress-multilingual-cms' );
}
if ( ! defined( 'WPML_CORE_MT_PATH' ) ) {
	define( 'WPML_CORE_MT_PATH', dirname( __FILE__ ) . '/../../wpml-media-translation' );
}
if ( ! defined( 'WPML_CORE_ST_PATH' ) ) {
	define( 'WPML_CORE_ST_PATH', dirname( __FILE__ ) . '/../../wpml-string-translation' );
}
if ( ! defined( 'WPML_CORE_TM_PATH' ) ) {
	define( 'WPML_CORE_TM_PATH', dirname( __FILE__ ) . '/../../wpml-translation-management' );
}
if ( ! defined( 'WC_PATH' ) ) {
	define( 'WC_PATH', dirname( __FILE__ ) . '/../../woocommerce' );
}

$_tests_dir = isset( $_ENV['WP_TEST_DIR'] ) ? $_ENV['WP_TEST_DIR'] : 'wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require WPML_CORE_PATH . '/tests/util/functions.php';
	require WPML_CORE_PATH . '/sitepress.php';
	require WPML_CORE_ST_PATH . '/plugin.php';
	require WPML_CORE_TM_PATH . '/plugin.php';
	require WPML_CORE_MT_PATH . '/plugin.php';
	require dirname( __FILE__ ) . '/../wpml-woocommerce.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require WPML_CORE_PATH . '/tests/util/wpml-unittestcase.class.php';
require dirname( __FILE__ ) . '/util/wcml-unittestcase.class.php';
