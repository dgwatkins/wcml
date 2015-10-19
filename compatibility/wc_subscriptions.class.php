<?php

class WCML_WC_Subscriptions{

    function __construct(){

        add_action('init', array($this, 'init'),9);
        add_filter('wcml_variation_term_taxonomy_ids',array($this,'wcml_variation_term_taxonomy_ids'));

        add_filter( 'wcs_get_users_subscriptions', array( $this, 'woocommerce_users_subscriptions' ), 10, 2 );

    }

    function init(){
        if(!is_admin() && version_compare( WOOCOMMERCE_VERSION, '2.1', '<' )){
            add_filter('woocommerce_subscriptions_product_sign_up_fee', array($this, 'product_price_filter'), 10, 2);                
        }
    }
    
    function product_price_filter($subscription_sign_up_fee, $product){
        
        $subscription_sign_up_fee = apply_filters('wcml_raw_price_amount', $subscription_sign_up_fee );
        
        return $subscription_sign_up_fee;
    }

    function wcml_variation_term_taxonomy_ids($get_variation_term_taxonomy_ids){
        global $wpdb;
        $get_variation_term_taxonomy_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.slug = 'variable-subscription'");
        
        if(!empty($get_variation_term_taxonomy_id)){
            $get_variation_term_taxonomy_ids[] = $get_variation_term_taxonomy_id;    
        }
        
        return $get_variation_term_taxonomy_ids;
    }


    function woocommerce_users_subscriptions($subscriptions, $user_id){
        global $sitepress;

        foreach($subscriptions as $key=>$subscription){
            $subscriptions[$key]['product_id'] = apply_filters( 'translate_object_id',$subscriptions[$key]['product_id'],get_post_type($subscriptions[$key]['product_id']),true,$sitepress->get_current_language());
        }

        return $subscriptions;
    }

}
