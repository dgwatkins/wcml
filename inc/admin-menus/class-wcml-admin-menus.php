<?php

class WCML_Admin_Menus{

    private static $woocommerce_wpml;
    private static $sitepress;

    public static function set_up_menus( &$woocommerce_wpml, &$sitepress ){
        self::$woocommerce_wpml =& $woocommerce_wpml;
        self::$sitepress =& $sitepress;

        add_action('admin_menu', array(__CLASS__, 'register_menus'));

        if( self::is_page_without_admin_language_switcher() ){
            self::remove_wpml_admin_language_switcher();
        }
    }

    public static function register_menus(){
        if( self::$woocommerce_wpml->check_dependencies && self::$woocommerce_wpml->check_design_update){
            $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');

            if(current_user_can('wpml_manage_woocommerce_multilingual')){
                add_submenu_page($top_page, __('WooCommerce Multilingual', 'woocommerce-multilingual'),
                    __('WooCommerce Multilingual', 'woocommerce-multilingual'), 'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array(__CLASS__, 'render_menus'));

            }else{
                global $wpdb, $sitepress;
                $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);
                if( !empty( $user_lang_pairs[$sitepress->get_default_language()] ) ){
                    add_menu_page(__('WooCommerce Multilingual', 'woocommerce-multilingual'),
                        __('WooCommerce Multilingual', 'woocommerce-multilingual'), 'translate',
                        'wpml-wcml', array(__CLASS__, 'render_menus'), ICL_PLUGIN_URL . '/res/img/icon16.png');
                }
            }

        }elseif( current_user_can('wpml_manage_woocommerce_multilingual') ){
            if(!defined('ICL_SITEPRESS_VERSION')){
                add_menu_page( __( 'WooCommerce Multilingual', 'woocommerce-multilingual' ), __( 'WooCommerce Multilingual', 'woocommerce-multilingual' ),
                    'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array(__CLASS__, 'render_menus'), WCML_PLUGIN_URL . '/res/images/icon16.png' );
            }else{
                $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');
                add_submenu_page($top_page, __('WooCommerce Multilingual', 'woocommerce-multilingual'),
                    __('WooCommerce Multilingual', 'woocommerce-multilingual'), 'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array(__CLASS__, 'render_menus'));
            }

        }
    }

    public static function render_menus(){

        if( self::$woocommerce_wpml->check_dependencies && self::$woocommerce_wpml->check_design_update ){
            $menus_wrap = new WCML_Menus_Wrap( self::$woocommerce_wpml );
            $menus_wrap->show();
        }else{
            global $sitepress;
            $plugins_wrap = new WCML_Plugins_Wrap( self::$woocommerce_wpml, $sitepress );
            $plugins_wrap->show();
        }

    }

    private static function is_page_without_admin_language_switcher(){
        global $pagenow;

        $get_post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : false;
        $get_post      = isset( $_GET['post'] ) ? $_GET['post'] : false;
        $get_page      = isset( $_GET['page'] ) ? $_GET['page'] : false;

        $is_page_wpml_wcml          = isset($_GET['page']) && $_GET['page'] == 'wpml-wcml';
        $is_new_order_or_coupon     = in_array( $pagenow, array( 'edit.php', 'post-new.php' ) ) &&
                                        $get_post_type &&
                                        in_array( $get_post_type, array( 'shop_coupon', 'shop_order' ) );
        $is_edit_order_or_coupon    = $pagenow == 'post.php' && $get_post &&
                                        in_array( get_post_type( $get_post ), array( 'shop_coupon', 'shop_order' ) );
        $is_shipping_zones          = $get_page == 'shipping_zones';
        $is_attributes_page          = $get_page == 'product_attributes';


        return is_admin() && (
                $is_page_wpml_wcml ||
                $is_new_order_or_coupon ||
                $is_edit_order_or_coupon ||
                $is_shipping_zones ||
                $is_attributes_page
              );

    }

    public static function remove_wpml_admin_language_switcher(){

        remove_action( 'wp_before_admin_bar_render', array(self::$sitepress, 'admin_language_switcher') );

    }

}