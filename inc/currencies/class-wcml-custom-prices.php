<?php

class WCML_Custom_Prices{

    private $woocommerce_wpml;

    public function __construct(){
        add_filter('init', array($this, 'custom_prices_init') );
    }

    public function custom_prices_init(){
        global $woocommerce_wpml;

        $this->woocommerce_wpml =& $woocommerce_wpml;

        if ( is_admin() ) {
            add_action( 'woocommerce_variation_options', array($this, 'add_individual_variation_nonce'), 10, 3 );

            //custom prices for different currencies for products/variations [BACKEND]
            add_action( 'woocommerce_product_options_pricing', array($this, 'woocommerce_product_options_custom_pricing') );
            add_action( 'woocommerce_product_after_variable_attributes', array($this, 'woocommerce_product_after_variable_attributes_custom_pricing'), 10, 3 );

        }

    }

    public function add_individual_variation_nonce($loop, $variation_data, $variation){

        wp_nonce_field('wcml_save_custom_prices_variation_' . $variation->ID, '_wcml_custom_prices_variation_' . $variation->ID . '_nonce');

    }

    public function get_product_custom_prices($product_id, $currency = false){
        global $wpdb, $sitepress, $woocommerce_wpml;

        $distinct_prices = false;

        if(empty($currency)){
            $currency = $this->get_client_currency();
        }

        $original_product_id = $product_id;
        $post_type = get_post_type($product_id);
        $product_translations = $sitepress->get_element_translations($sitepress->get_element_trid($product_id, 'post_'.$post_type), 'post_'.$post_type);
        foreach($product_translations as $translation){
            if( $translation->original ){
                $original_product_id = $translation->element_id;
                break;
            }
        }

        $product_meta = get_post_custom($original_product_id);

        $custom_prices = false;

        if(!empty($product_meta['_wcml_custom_prices_status'][0])){

            $prices_keys = array(
                '_price', '_regular_price', '_sale_price',
                '_min_variation_price', '_max_variation_price',
                '_min_variation_regular_price', '_max_variation_regular_price',
                '_min_variation_sale_price', '_max_variation_sale_price');

            foreach($prices_keys as $key){

                if(!empty($product_meta[$key . '_' . $currency][0])){
                    $custom_prices[$key] = $product_meta[$key . '_' . $currency][0];
                }

            }

        }

        if(!isset($custom_prices['_price'])) return false;

        $current__price_value = $custom_prices['_price'];

        // update sale price
        if(!empty($custom_prices['_sale_price'])){

            if(!empty($product_meta['_wcml_schedule_' . $currency][0])){
                // custom dates
                if(!empty($product_meta['_sale_price_dates_from_' . $currency][0]) && !empty($product_meta['_sale_price_dates_to_' . $currency][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from_' . $currency][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to_' . $currency][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }

            }else{
                // inherit
                if(!empty($product_meta['_sale_price_dates_from'][0]) && !empty($product_meta['_sale_price_dates_to'][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from'][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to'][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }
            }

        }

        if($custom_prices['_price'] != $current__price_value){
            update_post_meta($product_id, '_price_' . $currency, $custom_prices['_price']);
        }

        // detemine min/max variation prices
        if(!empty($product_meta['_min_variation_price'])){

            static $product_min_max_prices = array();

            if(empty($product_min_max_prices[$product_id])){

                // get variation ids
                $variation_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d", $product_id));

                // variations with custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_wcml_custom_prices_status'",join(',', $variation_ids)));
                foreach($res as $row){
                    $custom_prices_enabled[$row->post_id] = $row->meta_value;
                }

                // REGULAR PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_regular_price_" . $currency . "'",join(',', $variation_ids)));
                foreach($res as $row){
                    $regular_prices[$row->post_id] = $row->meta_value;
                }

                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_regular_price'",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_regular_prices[$row->post_id] = $row->meta_value;
                }

                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($regular_prices[$vid]) && isset($default_regular_prices[$vid])){
                        $regular_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_regular_prices[$vid] );
                    }
                }

                // SALE PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key=%s",join(',', $variation_ids),'_sale_price_'.$currency));
                foreach($res as $row){
                    $custom_sale_prices[$row->post_id] = $row->meta_value;
                }

                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_sale_price' AND meta_value <> ''",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_sale_prices[$row->post_id] = $row->meta_value;
                }

                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($sale_prices[$vid]) && isset($default_sale_prices[$vid])){
                        $sale_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_sale_prices[$vid]);
                    }
                }


                // PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key=%s",join(',', $variation_ids),'_price_'.$currency));
                foreach($res as $row){
                    $custom_prices_prices[$row->post_id] = $row->meta_value;
                }

                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_price'",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_prices[$row->post_id] = $row->meta_value;
                }

                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($custom_prices_prices[$vid]) && isset($default_prices[$vid])){
                        $prices[$vid] = apply_filters('wcml_raw_price_amount', $default_prices[$vid]);
                    }
                }

                if(!empty($regular_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_regular_price'] = min($regular_prices);
                    $product_min_max_prices[$product_id]['_max_variation_regular_price'] = max($regular_prices);
                }

                if(!empty($sale_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_sale_price'] = min($sale_prices);
                    $product_min_max_prices[$product_id]['_max_variation_sale_price'] = max($sale_prices);
                }

                if(!empty($prices)){
                    $product_min_max_prices[$product_id]['_min_variation_price'] = min($prices);
                    $product_min_max_prices[$product_id]['_max_variation_price'] = max($prices);
                }


            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_regular_price'])){
                $custom_prices['_min_variation_regular_price'] = $product_min_max_prices[$product_id]['_min_variation_regular_price'];
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_regular_price'])){
                $custom_prices['_max_variation_regular_price'] = $product_min_max_prices[$product_id]['_max_variation_regular_price'];
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_sale_price'])){
                $custom_prices['_min_variation_sale_price'] = $product_min_max_prices[$product_id]['_min_variation_sale_price'];
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_sale_price'])){
                $custom_prices['_max_variation_sale_price'] = $product_min_max_prices[$product_id]['_max_variation_sale_price'];
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_price'])){
                $custom_prices['_min_variation_price'] = $product_min_max_prices[$product_id]['_min_variation_price'];
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_price'])){
                $custom_prices['_max_variation_price'] = $product_min_max_prices[$product_id]['_max_variation_price'];
            }

        }

        return $custom_prices;
    }

    public function woocommerce_product_options_custom_pricing(){
        global $pagenow;

        $this->load_custom_prices_js_css();

        if( ( isset($_GET['post'] ) && ( get_post_type($_GET['post']) != 'product' || !$this->woocommerce_wpml->products->is_original_product( $_GET['post'] ) ) ) ||
            ( isset($_GET['post_type'] ) && $_GET['post_type'] == 'product' && isset( $_GET['source_lang'] ) ) ){
            return;
        }

        $product_id = false;

        if($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product'){
            $product_id = $_GET['post'];
        }

        $this->custom_pricing_output($product_id);

        wp_nonce_field('wcml_save_custom_prices','_wcml_custom_prices_nonce');

    }

    public function woocommerce_product_after_variable_attributes_custom_pricing($loop, $variation_data, $variation){

        if( $this->woocommerce_wpml->products->is_original_product( $variation->post_parent ) ) {

            echo '<tr><td>';
            $this->custom_pricing_output( $variation->ID );
            echo '</td></tr>';

        }

    }

    private function load_custom_prices_js_css(){
        wp_register_style( 'wpml-wcml-prices', WCML_PLUGIN_URL . '/res/css/wcml-prices.css', null, WCML_VERSION );
        wp_register_script( 'wcml-tm-scripts-prices', WCML_PLUGIN_URL . '/res/js/prices.js', array( 'jquery' ), WCML_VERSION );

        wp_enqueue_style('wpml-wcml-prices');
        wp_enqueue_script('wcml-tm-scripts-prices');
    }

    function custom_pricing_output( $post_id = false){

        $custom_prices_ui = new WCML_Custom_Prices_UI( $this->woocommerce_wpml, $post_id );
        $custom_prices_ui->show();

    }



}