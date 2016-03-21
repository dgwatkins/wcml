<?php

class WCML_WooCommerce_Rest_API_Support{


    function __construct(){
        add_action( 'parse_request', array( $this, 'use_canonical_home_url' ), -10 );
        add_action( 'init', array( $this, 'init' ) );

        add_filter( 'woocommerce_api_query_args', array($this, 'add_lang_parameter'), 10, 2 );
        add_filter( 'woocommerce_api_dispatch_args', array($this, 'dispatch_args_filter'), 10, 2 );

        add_filter( 'woocommerce_api_order_response' , array( $this, 'filter_order_items_by_language' ), 10, 4 );
        add_action( 'woocommerce_api_create_order' , array( $this, 'set_order_language' ), 10, 2 );


        add_action( 'woocommerce_api_create_product', array( $this, 'set_product_language' ), 10 , 2 );


    }

    function init(){
        global $sitepress,$sitepress_settings;

        //remove rewrite rules filtering for PayPal IPN url
        if( strstr($_SERVER['REQUEST_URI'],'WC_Gateway_Paypal') && $sitepress_settings[ 'urls' ][ 'directory_for_default_language' ] ) {
            remove_filter('option_rewrite_rules', array($sitepress, 'rewrite_rules_filter'));
        }

    }

    // Use url without the language parameter. Needed for the signature match.
    public function use_canonical_home_url(){
        global $wp;

        if(!empty($wp->query_vars['wc-api-version'])) {
            global $wpml_url_filters;
            remove_filter('home_url', array($wpml_url_filters, 'home_url_filter'), -10, 2);

        }

    }

    public function add_lang_parameter( $args, $request_args ){

        if( isset( $request_args['lang'] ) ) {
            $args['lang'] = $request_args['lang'];
        }

        return $args;
    }

    public function dispatch_args_filter( $args, $callback ){
        global $sitepress, $wp;

        $route = $wp->query_vars['wc-api-route'];


        if( isset( $args['filter']['lang'] ) ){

            $lang = $args['filter']['lang'];

            $active_languages = $sitepress->get_active_languages();

            if ( !isset($active_languages[$lang]) && $lang != 'all' ) {
                throw new WC_API_Exception( '404', sprintf( __( 'Invalid language parameter: %s' ), $lang ), '404' );
            }

            if ( $lang != $sitepress->get_default_language() ) {
                if ( $lang != 'all' ) {

                    $sitepress->switch_lang( $lang  );

                }else{

                    switch($route){
                        case '/products':
                            // Remove filters for the post query
                            remove_action( 'query_vars', array( $sitepress, 'query_vars' ) );
                            global $wpml_query_filter;
                            remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10 );
                            remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10 );
                            break;

                        case '/products/categories':
                            // Remove WPML language filters for the terms query
                            remove_filter('terms_clauses', array($sitepress,'terms_clauses'));
                            remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
                            break;

                    }

                }
            }

            if( $route == '/orders'){
                add_filter( 'woocommerce_order_get_items', array( $this, 'get_order_items_in_the_current_language' ) );
            }


        }


        return $args;

    }

    /**
     * Filter orders content in the current language
     */
    public function get_order_items_in_the_current_language( $items ){

        $lang = get_query_var('lang');
        $wc_taxonomies = wc_get_attribute_taxonomies();
        $attributes = array();
        foreach( $wc_taxonomies as $taxonomy ){
            $attributes[] = 'pa_' . $taxonomy->attribute_name;
        }

        foreach( $items as $key => $item ){

            if( isset( $item['product_id'] ) ) {
                $translated_product_id = apply_filters( 'translate_object_id', $item['product_id'], 'product', true, $lang );
                $items[$key]['product_id'] = $translated_product_id;
                $items[$key]['item_meta']['_product_id'] = $translated_product_id;
                $items[$key]['name'] = get_post_field( 'post_title', $translated_product_id );
                foreach ( $item['item_meta_array'] as $k => $m ) {
                    if ( $m->key == '_product_id' ) {
                        $items[$key]['item_meta_array'][$k]->value = $translated_product_id;
                    }
                }
            }

            // Variations included
            if( !empty( $item['variation_id'] ) ){
                $translated_variation_id = apply_filters('translate_object_id', $item['variation_id'], 'product_variation', true, $lang);
                $items[$key]['variation_id'] = $translated_variation_id;
                $items[$key]['item_meta']['_variation_id'] = $translated_variation_id;
                foreach( $attributes as $attribute_name ){
                    if( isset( $item['item_meta'][$attribute_name] ) ){

                        foreach( $item['item_meta'][$attribute_name] as $idx => $attr ){
                            $term = get_term_by('slug',  $attr, $attribute_name);
                            $translated_term_id = apply_filters('translate_object_id', $term->term_id, $attribute_name, true, $lang);
                            $translated_term = get_term_by('id',  $translated_term_id, $attribute_name);
                            $items[$key]['item_meta'][$attribute_name][$idx] = $translated_term->slug;
                        }

                    }

                    if( isset( $item[$attribute_name] ) ){
                        $term = get_term_by('slug',  $item[$attribute_name], $attribute_name);
                        $translated_term_id = apply_filters('translate_object_id', $term->term_id, $attribute_name, true, $lang);
                        $translated_term = get_term_by('id',  $translated_term_id, $attribute_name);
                        $items[$key][$attribute_name] = $translated_term->slug;
                    }
                }

                foreach( $item['item_meta_array'] as $k => $m){
                    if($m->key == '_variation_id'){

                        $items[$key]['item_meta_array'][$k]->value = $translated_variation_id;

                    } elseif( in_array( $m->key, $attributes ) ){

                        $term = get_term_by('slug',  $m->value, $m->key);
                        $translated_term_id = apply_filters('translate_object_id', $term->term_id, $m->key, true, $lang);
                        $translated_term = get_term_by('id',  $translated_term_id, $m->key);
                        $items[$key]['item_meta_array'][$k]->value = $translated_term->slug;

                    }
                }


            }

        }

        return $items;

    }

    /**
     * Filters the items of an order according to a given languages
     *
     * @param $order_data
     * @param $order
     * @param $fields
     * @param $server
     * @return mixed
     */
    public function filter_order_items_by_language( $order_data, $order, $fields, $server ){


        $lang = get_query_var('lang');

        $order_lang = get_post_meta($order->ID, 'wpml_language');

        if( $order_lang != $lang ){

            foreach( $order_data['line_items'] as $k => $item ){

                if( isset( $item['product_id'] ) ){

                    $translated_product_id = apply_filters( 'translate_object_id', $item['product_id'], 'product', true, $lang );
                    if( $translated_product_id ){
                        $translated_product = new WC_Product( $translated_product_id );
                        $order_data['line_items'][$k]['product_id'] = $translated_product_id;
                        if( $translated_product->post->post_type == 'product_variation' ){
                            $post_parent = get_post( $translated_product->post->post_parent );
                            $post_name = $post_parent->post_title;
                        } else {
                            $post_name = $translated_product->post->post_title;
                        }
                        $order_data['line_items'][$k]['name'] = $post_name;
                    }

                }

            }

        }

        return $order_data;
    }


    /**
     * Sets the language for a new order
     *
     * @param $order_id
     * @param $data
     */
    public function set_order_language( $order_id, $data ){
        global $sitepress;

        if( isset( $data['lang'] ) ){

            $active_languages = $sitepress->get_active_languages();
            if( !isset( $active_languages[$data['lang']] ) ){
                throw new WC_API_Exception( '404', sprintf( __( 'Invalid language parameter: %s' ), $data['lang'] ), '404' );
            }

            update_post_meta( $order_id, 'wpml_language', $data['lang']);

        }

    }

    /**
     * Sets the product information according to the provided language
     *
     * @param $id
     * @param $data
     *
     * @throws WC_API_Exception
     *
     */
    public function set_product_language( $id, $data ){
        global $sitepress;

        if( isset( $data['lang'] )){
            $active_languages = $sitepress->get_active_languages();
            if( !isset( $active_languages[$data['lang']] ) ){
                throw new WC_API_Exception( '404', sprintf( __( 'Invalid language parameter: %s' ), $data['lang'] ), '404' );
            }
            if( isset( $data['translation_of'] ) ){
                $trid = $sitepress->get_element_trid( $data['translation_of'], 'post_product' );
                if( empty($trid) ){
                    throw new WC_API_Exception( '404', sprintf( __( 'Source product id not found: %s' ), $data['translation_of'] ), '404' );
                }
            }else{
                $trid = null;
            }
            $sitepress->set_element_language_details( $id, 'post_product', $trid, $data['lang'] );
        }

    }

}

?>