<?php
/**
 * Plugin Name: WooCommerce Multilingual
 * Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
 * Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Text Domain: woocommerce-multilingual
 * Requires at least: 4.7
 * Tested up to: 5.2
 * Version: 4.6.6
 * WC requires at least: 3.3.0
 * WC tested up to: 3.6.4
 *
 * @package WCML
 * @author  OnTheGoSystems
 */

if ( defined( 'WCML_VERSION' ) ) {
	return;
}

/**
 * As long as WCML can be installed and activated without WPML-Core, we have to keep this code here.
 */
require_once 'vendor/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-php-version-check.php'; // We cannot use composer here.

$wpml_php_version_check = new WPML_PHP_Version_Check(
	'5.6',
	'WooCommerce Multilingual',
	__FILE__,
	'woocommerce-multilingual'
);
if ( ! $wpml_php_version_check->is_ok() ) {
	return;
}

define( 'WCML_VERSION', '4.6.6' );
define( 'WCML_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'WCML_PLUGIN_FOLDER', basename( WCML_PLUGIN_PATH ) );
define( 'WCML_LOCALE_PATH', WCML_PLUGIN_PATH . '/locale' );
define( 'WPML_LOAD_API_SUPPORT', true );
define( 'WCML_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

define( 'WCML_AUTOLOADER_PATH', WCML_PLUGIN_PATH . '/vendor' );
define( 'WCML_AUTOLOADER', WCML_AUTOLOADER_PATH . '/autoload.php' );


/**
 * @param \WPML_Core $wpml_core
 *
 * @throws \Auryn\InjectionException
 */
function wcml_bootstrap( WPML_Core $wpml_core ) {
	require WCML_PLUGIN_PATH . '/inc/constants.php';
	require WCML_PLUGIN_PATH . '/inc/missing-php-functions.php';
	require WCML_PLUGIN_PATH . '/inc/installer-loader.php';
	require WCML_PLUGIN_PATH . '/inc/wcml-core-functions.php';
	require WCML_PLUGIN_PATH . '/inc/wcml-switch-lang-request.php';
	require_once WCML_AUTOLOADER;

	// Detecting language switching.
	$wcml_switch_lang_request = new WCML_Switch_Lang_Request( new WPML_Cookie(), new WPML_WP_API(), $wpml_core->get_sitepress() );
	$wcml_switch_lang_request->add_hooks();

	// Cart related language switching functions.
	$wcml_cart_switch_lang_functions = new WCML_Cart_Switch_Lang_Functions();
	$wcml_cart_switch_lang_functions->add_actions();

	if ( ! WPML_Core_Version_Check::is_ok( WCML_PLUGIN_PATH . '/wpml-dependencies.json' ) ) {
		return;
	}

	global $woocommerce_wpml;
	$woocommerce_wpml = new woocommerce_wpml();
	$woocommerce_wpml->add_hooks();

	$xdomain_data = new WCML_xDomain_Data( new WPML_Cookie() );
	$xdomain_data->add_hooks();

	$loaders = array(
		'WCML_Privacy_Content_Factory',
		'WCML_ATE_Activate_Synchronization',
	);

	if (
		( defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'WPML_MEDIA_VERSION' ) ) ||
		(
			defined( 'ICL_SITEPRESS_VERSION' )
			&& version_compare( ICL_SITEPRESS_VERSION, '4.0.0', '>=' )
			&& version_compare( ICL_SITEPRESS_VERSION, '4.0.4', '<' )
			&& ! defined( 'WPML_MEDIA_VERSION' )
		)
	) {
		$loaders[] = 'WCML_Product_Image_Filter_Factory';
		$loaders[] = 'WCML_Product_Gallery_Filter_Factory';
		$loaders[] = 'WCML_Update_Product_Gallery_Translation_Factory';
		$loaders[] = 'WCML_Append_Gallery_To_Post_Media_Ids_Factory';
	}

	$action_filter_loader = new WPML_Action_Filter_Loader();
	$action_filter_loader->load( $loaders );

	$rest_api = new WCML_REST_API();
	if ( $rest_api->is_rest_api_request() ) {
		add_action( 'wpml_before_init', array( $rest_api, 'remove_wpml_global_url_filters' ), 0 );
	}
}

/**
 * Load WooCommerce Multilingual when WPML is NOT active.
 */
function load_wcml_without_wpml() {
	if ( ! did_action( 'wpml_core_loaded' ) && ! did_action( 'wpml_core_checks_started' ) ) {
		global $woocommerce_wpml;
		$woocommerce_wpml = new woocommerce_wpml();
	}
}

add_action( 'wpml_core_loaded', 'wcml_bootstrap' );
add_action( 'init', 'load_wcml_without_wpml', 10000 );
