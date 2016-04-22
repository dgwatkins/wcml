<?php

class WCML_Table_Rate_Shipping{

    function __construct(){

        add_action('init', array($this, 'init'),9);
        add_filter('woocommerce_table_rate_query_rates_args', array($this, 'default_shipping_class_id'));
    }

    function init(){
        global $pagenow;

        //register shipping label
        if($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page']=='shipping_zones' && isset( $_POST['shipping_label'] ) && isset( $_POST['woocommerce_table_rate_title'] )){
            do_action('wpml_register_single_string', 'woocommerce', $_POST['woocommerce_table_rate_title'] .'_shipping_method_title', $_POST['woocommerce_table_rate_title']);
            $shipping_labels = array_map( 'woocommerce_clean', $_POST['shipping_label'] );
            foreach($shipping_labels as $shipping_label){
                do_action('wpml_register_single_string', 'woocommerce', $shipping_label .'_shipping_method_title', $shipping_label);
            }
        }
        
    }

    function default_shipping_class_id($args){
        global $sitepress, $woocommerce_wpml;
        if($sitepress->get_current_language() != $sitepress->get_default_language() && !empty($args['shipping_class_id'])){

            $args['shipping_class_id'] = apply_filters( 'translate_object_id',$args['shipping_class_id'], 'product_shipping_class', false, $sitepress->get_default_language());

            if($woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
                // use unfiltred cart price to compare against limits of different shipping methods
                $args['price'] = $woocommerce_wpml->multi_currency->prices->unconvert_price_amount($args['price']);
            }

        }

        return $args;
    }

}
