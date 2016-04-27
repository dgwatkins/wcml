<?php
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  Text Domain: woocommerce-multilingual
  Version: 3.8-beta1
*/


if(defined('WCML_VERSION')) return;
define('WCML_VERSION', '3.8');
define('WCML_PLUGIN_PATH', dirname(__FILE__));
define('WCML_PLUGIN_FOLDER', basename(WCML_PLUGIN_PATH));
define('WCML_LOCALE_PATH', WCML_PLUGIN_PATH.'/locale' );
define('WPML_LOAD_API_SUPPORT', true);

require WCML_PLUGIN_PATH . '/inc/class-wcml-autoloader.php';

include WCML_PLUGIN_PATH . '/inc/constants.php';

require WCML_PLUGIN_PATH . '/inc/missing-php-functions.php';
include WCML_PLUGIN_PATH . '/inc/installer-loader.php';

define('WCML_PLUGIN_URL', wpml_filter_include_url( untrailingslashit( plugin_dir_url( __FILE__ ) ) ));

function wpml_wcml_startup() {
    global $woocommerce_wpml;
    $woocommerce_wpml = new woocommerce_wpml();
}

if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
    //@since WPML 3.2 using dependencies hook
    add_action( 'wpml_loaded', 'wpml_wcml_startup' );
} else {
    add_action('plugins_loaded', 'wpml_wcml_startup', 10000);
}
