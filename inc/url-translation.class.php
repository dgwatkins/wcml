<?php

class WCML_Url_Translation{

    function __construct(){

        //set translate product by default
        $this->translate_product_slug();
        $this->force_product_slug_translation_on();

        add_filter('option_woocommerce_permalinks', array($this, 'filter_woocommerce_permalinks_option'));

        add_filter('pre_update_option_rewrite_rules', array($this, 'pre_update_rewrite_rules'), 1, 1); // high priority

        remove_filter('option_rewrite_rules', array('WPML_Slug_Translation', 'rewrite_rules_filter'), 1, 1); //remove filter from WPML and use WCML filter first
        add_filter('option_rewrite_rules', array($this, 'rewrite_rules_filter'), 3, 1); // high priority
        add_filter('term_link', array($this, 'translate_category_base'), 0, 3); // high priority

    }

    function translate_product_slug(){
        global $wpdb;

        if(!defined('WOOCOMMERCE_VERSION') || (!isset($GLOBALS['ICL_Pro_Translation']) || is_null($GLOBALS['ICL_Pro_Translation']))){
            return;
        }

        $slug = $this->get_woocommerce_product_slug();

        if ( apply_filters( 'wpml_slug_translation_available', false) ) {
            // Use new API for WPML >= 3.2.3
            do_action( 'wpml_activate_slug_translation', $slug );

        } else {
            // Pre WPML 3.2.3
            $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));
            if(!$string){
                do_action('wpml_register_single_string', 'WordPress', 'URL slug: ' . $slug, $slug);
            }

        }

    }

    function get_woocommerce_product_slug(){

        $woocommerce_permalinks = maybe_unserialize( get_option('woocommerce_permalinks') );

        if( isset( $woocommerce_permalinks['product_base'] ) && !empty( $woocommerce_permalinks['product_base'] ) ){
            return trim( $woocommerce_permalinks['product_base'], '/');
        }elseif(get_option('woocommerce_product_slug') != false ){
            return trim( get_option('woocommerce_product_slug'), '/');
        }else{
            return 'product';
        }

    }

    function force_product_slug_translation_on(){
        global $sitepress;

        $iclsettings = $sitepress->get_settings();
        if(empty($iclsettings['posts_slug_translation']['on']) || empty($iclsettings['posts_slug_translation']['types']['product'])){
            $iclsettings['posts_slug_translation']['on'] = 1;
            $iclsettings['posts_slug_translation']['types']['product'] = 1;
            $sitepress->save_settings($iclsettings);
        }

    }

    function filter_woocommerce_permalinks_option($value){

        if (WPML_SUPPORT_STRINGS_IN_DIFF_LANG && isset($value['product_base']) && $value['product_base']) {
            do_action('wpml_register_single_string', 'URL slugs', 'URL slug: ' . trim($value['product_base'], '/'), trim($value['product_base'], '/'));
            // only register. it'll have to be translated via the string translation
        }

        $category_base = !empty($value['category_base']) ? $value['category_base'] : 'product-category';
        do_action('wpml_register_single_string', 'WordPress', 'Url product_cat slug: ' . $category_base, $category_base);

        $tag_base = !empty($value['tag_base']) ? $value['tag_base'] : 'product-tag';
        do_action('wpml_register_single_string', 'WordPress', 'Url product_tag slug: ' . $tag_base, $tag_base);

        if (isset($value['attribute_base']) && $value['attribute_base']) {
            $attr_base = trim($value['attribute_base'], '/');
            do_action('wpml_register_single_string', 'WordPress', 'Url attribute slug: ' . $attr_base, $attr_base);
        }

        return $value;

    }

    function pre_update_rewrite_rules($value){
        global $sitepress, $sitepress_settings, $woocommerce, $woocommerce_wpml;

        // force saving in strings language
        $strings_language = $woocommerce_wpml->strings->get_wc_context_language();

        if($sitepress->get_current_language() != $strings_language  && is_array( $value ) ){

            $permalinks     = get_option( 'woocommerce_permalinks' );
            if(empty($permalinks['category_base']) && $value){
                remove_filter('gettext_with_context', array($woocommerce_wpml->strings, 'category_base_in_strings_language'), 99, 3);
                $base_translated = _x( 'product-category', 'slug', 'woocommerce' );
                add_filter('gettext_with_context', array($woocommerce_wpml->strings, 'category_base_in_strings_language'), 99, 3);
                $new_value = array();
                foreach($value as $k => $v){
                    $k = preg_replace("#$base_translated/#", _x( 'product-category', 'slug', 'woocommerce' ) . '/', $k);
                    $new_value[$k] = $v;
                }
                $value = $new_value;
                unset($new_value);
            }
            if(empty($permalinks['tag_base']) && $value){
                remove_filter('gettext_with_context', array($woocommerce_wpml->strings, 'category_base_in_strings_language'), 99, 3);
                $base_translated = _x( 'product-tag', 'slug', 'woocommerce' );
                add_filter('gettext_with_context', array($woocommerce_wpml->strings, 'category_base_in_strings_language'), 99, 3);
                $new_value = array();
                foreach($value as $k => $v){
                    $k = preg_replace("#$base_translated/#", _x( 'product-tag', 'slug', 'woocommerce' ) . '/', $k);
                    $new_value[$k] = $v;
                }
                $value = $new_value;
                unset($new_value);
            }

        }

        return $value;
    }

    function rewrite_rules_filter($value){
        global $sitepress, $sitepress_settings, $wpdb, $wp_taxonomies,$woocommerce,$woocommerce_wpml;

        if(!empty($sitepress_settings['posts_slug_translation']['on'])){
            add_filter('option_rewrite_rules', array('WPML_Slug_Translation', 'rewrite_rules_filter'), 1, 1);
        }

        $strings_language = $woocommerce_wpml->strings->get_wc_context_language();

        if($sitepress->get_current_language() != $strings_language){

            $cache_key = 'wcml_rewrite_filters_translate_taxonomies';

            if($val = wp_cache_get($cache_key)){

                $value = $val;

            }else{

                $taxonomies = array('product_cat', 'product_tag');

                foreach($taxonomies as $taxonomy ){
                    $slug_details = $this->get_translated_tax_slug($taxonomy);

                    if($slug_details) {
                        $buff_value = array();
                        foreach ((array)$value as $k => $v) {
                            if ( $slug_details['slug'] != $slug_details['translated_slug'] && preg_match('#^[^/]*/?' . $slug_details['slug'] . '/#', $k)) {
                                $k = preg_replace('#^([^/]*)(/?)' . $slug_details['slug'] . '/#', '$1$2' . $slug_details['translated_slug']  . '/', $k);
                            }
                            $buff_value[$k] = $v;
                        }
                        $value = $buff_value;
                        unset($buff_value);
                    }

                }

                // handle attributes
                $wc_taxonomies = wc_get_attribute_taxonomies();
                $wc_taxonomies_wc_format = array();
                foreach($wc_taxonomies as $k => $v){
                    $wc_taxonomies_wc_format[] = 'pa_' . $v->attribute_name;
                }

                foreach($wc_taxonomies_wc_format as $taxonomy ){
                    $taxonomy_obj  = get_taxonomy($taxonomy);

                    if( isset($taxonomy_obj->rewrite['slug'] ) ){
                        $exp = explode('/', trim($taxonomy_obj->rewrite['slug'],'/'));
                        $slug = join('/', array_slice($exp, 0, count($exp) - 1));
                    }

                    if( isset( $slug ) && $sitepress->get_current_language() != $strings_language){

                        $slug_translation = $wpdb->get_var($wpdb->prepare("
                                    SELECT t.value
                                    FROM {$wpdb->prefix}icl_string_translations t
                                        JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                                    WHERE t.language = %s AND t.status = %s AND s.name = %s AND s.value = %s
                                ", $sitepress->get_current_language(), ICL_STRING_TRANSLATION_COMPLETE, 'URL attribute slug: ' . $slug, $slug));

                        if($slug_translation){

                            $buff_value = array();
                            foreach((array)$value as $k=>$v){
                                if( $slug != $slug_translation && preg_match('#^' . $slug . '/(.*)#', $k) ){
                                    $k = preg_replace('#^' . $slug . '/(.*)#',   $slug_translation . '/$1' , $k);
                                }
                                $buff_value[$k] = $v;
                            }

                            $value = $buff_value;
                            unset($buff_value);

                        }

                    }

                }

                wp_cache_add($cache_key, $value);

            }

        }

        //filter shop page rewrite slug
        $cache_key = 'wcml_rewrite_shop_slug';

        if($val = wp_cache_get($cache_key)){

            $value = $val;

        }else{

            $current_shop_id = woocommerce_get_page_id( 'shop' );
            $default_shop_id = apply_filters( 'translate_object_id', $current_shop_id, 'page', true, $sitepress->get_default_language() );

            if ( is_null( get_post( $current_shop_id ) ) || is_null( get_post( $default_shop_id ) ) )
                return $value;

            $current_slug = get_post( $current_shop_id )->post_name;
            $default_slug = get_post( $default_shop_id )->post_name;


            if( $current_slug != $default_slug ){
                $buff_value = array();
                foreach( (array) $value as $k => $v ){
                    if( $current_slug != $default_slug && preg_match( '#^[^/]*/?' . $default_slug . '/page/#', $k ) ){
                        $k = preg_replace( '#^([^/]*)(/?)' . $default_slug . '/#',  '$1$2' . $current_slug . '/' , $k );
                    }
                    $buff_value[$k] = $v;
                }

                $value = $buff_value;
                unset( $buff_value );
            }

            wp_cache_add($cache_key, $value);
        }

        return $value;
    }

    function translate_category_base($termlink, $term, $taxonomy){
        global $wp_rewrite, $wpml_term_translations, $sitepress;
        static $no_recursion_flag;

        // handles product categories, product tags and attributes

        $wc_taxonomies = wc_get_attribute_taxonomies();
        foreach($wc_taxonomies as $k => $v){
            $wc_taxonomies_wc_format[] = 'pa_' . $v->attribute_name;
        }

        if(($taxonomy == 'product_cat' || $taxonomy == 'product_tag' || (!empty($wc_taxonomies_wc_format) && in_array($taxonomy, $wc_taxonomies_wc_format))) && !$no_recursion_flag){

            $cache_key = 'termlink#' . $taxonomy .'#' . $term->term_id;
            if( false && $link = wp_cache_get($cache_key, 'terms')){
                $termlink = $link;

            }else{

                $no_recursion_flag = false;

                if( !is_null( $wpml_term_translations ) ){
                    $term_language = $term->term_id ? $wpml_term_translations->get_element_lang_code($term->term_taxonomy_id) : false;
                }else{
                    $term_language = $term->term_id ? $sitepress->get_language_for_element( $term->term_taxonomy_id, 'tax_'.$taxonomy ) : false;
                }

                if( $term_language ){

                    $taxonomy_obj = get_taxonomy( $taxonomy );
                    $base = isset($taxonomy_obj->rewrite['slug']) ? trim($taxonomy_obj->rewrite['slug'], '/') : false;

                    $slug_details = $this->get_translated_tax_slug( $taxonomy, $term_language );
                    $base_translated = $slug_details['translated_slug'];

                    $string_identifier = $taxonomy == 'product_tag' || $taxonomy == 'product_cat' ? $taxonomy : 'attribute';

                    if(!empty($base_translated) && $base_translated != $base && isset( $wp_rewrite->extra_permastructs[$taxonomy] ) ){

                        $buff = $wp_rewrite->extra_permastructs[$taxonomy]['struct'];
                        $wp_rewrite->extra_permastructs[$taxonomy]['struct'] = str_replace($base, $base_translated, $wp_rewrite->extra_permastructs[$taxonomy]['struct']);
                        $no_recursion_flag = true;
                        $termlink = get_term_link($term, $taxonomy);

                        $wp_rewrite->extra_permastructs[$taxonomy]['struct'] = $buff;

                    }

                }

                $no_recursion_flag = false;

                wp_cache_add($cache_key, $termlink, 'terms', 0);
            }

        }

        return $termlink;
    }

    function get_translated_tax_slug( $taxonomy, $language = false ){
        global $sitepress, $woocommerce_wpml, $wpdb;

        $strings_language = $woocommerce_wpml->strings->get_wc_context_language();

        $permalinks     = get_option( 'woocommerce_permalinks' );

        switch($taxonomy){
            case 'product_tag':
                $slug = !empty( $permalinks['tag_base'] ) ? trim($permalinks['tag_base'],'/') : 'product-tag';
                break;

            case 'product_cat':
                $slug = !empty( $permalinks['category_base'] ) ? trim($permalinks['category_base'],'/') : 'product-category';
                break;

            default:
                $slug = trim( $permalinks['attribute_base'], '/' );
                break;
        }

        if( !$language ){
            $language = $sitepress->get_current_language();
        }

        if($slug && $language != $strings_language) {

            $slug_translation = $wpdb->get_var($wpdb->prepare("
                                    SELECT t.value
                                    FROM {$wpdb->prefix}icl_string_translations t
                                        JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                                    WHERE t.language = %s AND t.status = %s AND s.name = %s AND s.value = %s
                                ", $language, ICL_STRING_TRANSLATION_COMPLETE, 'URL ' . $taxonomy . ' slug: ' . $slug, $slug));

            if ( is_null( $slug_translation ) ) {
                // handle exception - default woocommerce category and tag bases used
                $slug_translation = $woocommerce_wpml->get_translation_from_woocommerce_mo_file( $slug, $language );

            }

            return array( 'slug' => $slug, 'translated_slug' => $slug_translation );
        }

        return array( 'slug' => $slug, 'translated_slug' => $slug );

    }
}