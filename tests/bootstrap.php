<?php
define( 'WP_PLUGIN_DIR', realpath( __DIR__ . '/../..' ) );

if ( ! defined( 'WPML_CORE_PATH' ) ) {
	define( 'WPML_CORE_PATH', WP_PLUGIN_DIR . '/sitepress-multilingual-cms' );
}
if ( ! defined( 'WPML_CORE_ST_PATH' ) ) {
	define( 'WPML_CORE_ST_PATH', WP_PLUGIN_DIR . '/wpml-string-translation' );
}
if ( ! defined( 'WPML_CORE_TM_PATH' ) ) {
	define( 'WPML_CORE_TM_PATH', WP_PLUGIN_DIR . '/wpml-translation-management' );
}
if ( ! defined( 'WCML_CORE_PATH' ) ) {
	define( 'WCML_CORE_PATH', WP_PLUGIN_DIR . '/woocommerce-multilingual' );
}
if ( ! defined( 'WC_PATH' ) ) {
	define( 'WC_PATH', WP_PLUGIN_DIR . '/woocommerce' );
}

if ( ! defined( 'WC_BOOKING_PATH' ) ) {
	define( 'WC_BOOKING_PATH', WP_PLUGIN_DIR . '/woocommerce-bookings' );
}

$_tests_dir = isset( $_ENV['WP_TEST_DIR'] ) ? $_ENV['WP_TEST_DIR'] : 'wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_wcml() {
	wp_styles();
	require_once WPML_CORE_PATH . '/tests/util/functions.php';
	require_once WPML_CORE_PATH . '/sitepress.php';
	require_once WPML_CORE_ST_PATH . '/plugin.php';

	require_once WPML_CORE_TM_PATH . '/plugin.php';
	require_once WC_PATH. '/woocommerce.php';
	require_once WC_BOOKING_PATH . '/woocommerce-bookings.php';
	require_once __DIR__ . '/../wpml-woocommerce.php';
}

tests_add_filter( 'plugins_loaded', '_manually_load_wcml', - PHP_INT_MAX );

function is_woocommerce_active() {
	return true;
}

function _install_wc() {
	global $wpdb;

	$tables_to_empty = array(
		"{$wpdb->prefix}woocommerce_api_keys",
		"{$wpdb->prefix}woocommerce_attribute_taxonomies",
		"{$wpdb->prefix}woocommerce_downloadable_product_permissions",
		"{$wpdb->prefix}woocommerce_order_itemmeta",
		"{$wpdb->prefix}woocommerce_order_items",
		"{$wpdb->prefix}woocommerce_payment_tokenmeta",
		"{$wpdb->prefix}woocommerce_payment_tokens",
		"{$wpdb->prefix}woocommerce_sessions",
		"{$wpdb->prefix}woocommerce_shipping_zone_locations",
		"{$wpdb->prefix}woocommerce_shipping_zone_methods",
		"{$wpdb->prefix}woocommerce_shipping_zones",
		"{$wpdb->prefix}woocommerce_tax_rate_locations",
		"{$wpdb->prefix}woocommerce_tax_rates",
	);
	foreach ( $tables_to_empty as $table_name ) {
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$wpdb->query( "DROP TABLE {$table_name}" );
		}

	}

	WC_Install::install();
	update_option( 'woocommerce_calc_shipping', 'yes' ); // Needed for tests cart and shipping methods
}

require_once __DIR__ . '/wordpress/wp-includes/class-wp-locale.php';

if ( ! function_exists( 'get_plugins' ) ) {
	require_once __DIR__ . '/wordpress/wp-admin/includes/plugin.php';
}
// Install WPML
tests_add_filter( 'wpml_loaded', 'wpml_test_install_setup' );
// install WC
tests_add_filter( 'init', '_install_wc', -1 );
// Launch WCML
tests_add_filter( 'wpml_loaded', 'load_wcml' );
function load_wcml(){
	global $woocommerce_wpml;
	$woocommerce_wpml = new woocommerce_wpml();
	$woocommerce_wpml->add_hooks();
}

// Temporary workaround for missing WP_REST_Server class missing
tests_add_filter( 'init', 'WP_REST_Server_placeholder' );
function WP_REST_Server_placeholder(){
	if( !class_exists( 'WP_REST_Server' ) ) {
		class WP_REST_Server{}
	}
}

require_once $_tests_dir . '/includes/bootstrap.php';
require_once WPML_CORE_PATH . '/tests/util/wpml-unittestcase.class.php';
require_once WC_PATH . '/tests/includes/wp-http-testcase.php';
require_once WC_PATH . '/tests/framework/class-wc-unit-test-case.php';
require_once WC_PATH . '/tests/framework/class-wc-unit-test-factory.php';

require_once WCML_CORE_PATH . '/tests/util/wcml-unittestcase.class.php';
require_once WCML_CORE_PATH . '/tests/util/class-wcml-helper.php';
require_once WCML_CORE_PATH . '/tests/util/class-wcml-helper-coupon.php';
require_once WCML_CORE_PATH . '/tests/util/class-wcml-helper-multi-currency.php';
require_once WCML_CORE_PATH . '/tests/util/class-wcml-helper-orders.php';
require_once WCML_CORE_PATH . '/tests/util/class-wcml-helper-shipping.php';
