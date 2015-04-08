<?php

class WCML_WooCommerce_Rest_API_Support{


    function __construct(){
        add_action('parse_request', array($this, 'use_canonical_home_url'), -10);
    }

    // Use url without the language parameter. Needed for the signature match.
    public function use_canonical_home_url(){
        global $wp;

        if(!empty($wp->query_vars['wc-api-version'])){
            global $wpml_url_filters;
            remove_filter( 'home_url', array( $wpml_url_filters, 'home_url_filter' ), -10, 2 );
        }

    }

}

?>