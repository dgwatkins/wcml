<?php

class WCML_Attributes{

    function __construct(){
        add_action( 'init', array( $this, 'init' ) );

    }

    function init(){

        if( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'product_attributes' && isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'product' ){

            $this->load_js_and_css();
            add_action( 'admin_footer', array( $this, 'not_translatable_html' ) );

            if( isset( $_POST[ 'save_attribute' ] ) && isset( $_GET[ 'edit' ] ) ){
                $this->set_attribute_readonly_config( $_GET[ 'edit' ], $_POST );
            }

        }

        add_action( 'woocommerce_attribute_added', array( $this, 'set_attribute_readonly_config' ), 10, 2 );

    }

    function not_translatable_html(){
        global $woocommerce_wpml;

        $attr_id = isset( $_GET[ 'edit' ] ) ? absint( $_GET[ 'edit' ] ) : false;

        $attr_is_tnaslt = new WCML_Not_Translatable_Attributes( $attr_id, $woocommerce_wpml );
        $attr_is_tnaslt->show();
    }

    function load_js_and_css(){

        wp_register_script( 'wcml-attributes', WCML_PLUGIN_URL . '/res/js/wcml-attributes.js', array( 'jquery' ), WCML_VERSION );
        wp_enqueue_script( 'wcml-attributes' );

    }

    function get_attribute_terms( $attribute ){

        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
                        SELECT * FROM {$wpdb->term_taxonomy} x JOIN {$wpdb->terms} t ON x.term_id = t.term_id WHERE x.taxonomy = %s", $attribute ) );

    }

    function set_attribute_readonly_config( $id, $attribute ){
        global $sitepress,$woocommerce_wpml;

        $is_translatable = isset( $_POST[ 'wcml-is-translatable-attr' ] ) ? 1 : 0;

        if( $is_translatable === 0 ){
            //delete all translated attributes terms if "Translatable?" option un-checked
            $this->delete_translated_attribute_terms( $attribute['attribute_name'] );
            $this->set_variations_to_use_original_attributes( $attribute['attribute_name'] );
            $this->set_original_attributes_for_products( $attribute['attribute_name'] );
        }

        $wcml_settings = $woocommerce_wpml->get_settings();
        $wcml_settings[ 'attributes_settings' ][ $attribute['attribute_name'] ] = $is_translatable;
        $woocommerce_wpml->update_settings( $wcml_settings );

        $wpml_settings = $sitepress->get_settings();
        $wpml_settings['taxonomies_sync_option'][wc_attribute_taxonomy_name($attribute['attribute_name'])] = $is_translatable;

        if( isset($wpml_settings['translation-management'])){
            $wpml_settings['translation-management']['taxonomies_readonly_config'][wc_attribute_taxonomy_name( $attribute['attribute_name'] )] = $is_translatable;
        }

        $sitepress->save_settings($wpml_settings);

    }

    function delete_translated_attribute_terms( $attribute ){
        global $sitepress;

        $terms = $this->get_attribute_terms( 'pa_'.$attribute );

        foreach( $terms as $term ){
            $term_language_details = $sitepress->get_element_language_details( $term->term_id, 'tax_pa_'.$attribute );
            if( $term_language_details && $term_language_details->source_language_code ){
                wp_delete_term( $term->term_id, 'pa_'.$attribute );
            }
        }

    }

    function set_variations_to_use_original_attributes( $attribute ){
        global $wpdb, $sitepress;
        $terms = $this->get_attribute_terms( 'pa_'.$attribute );

        foreach( $terms as $term ){

            $variations = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value = %s",  'attribute_pa_'.$attribute, $term->slug ) );

            foreach( $variations as $variation ){
                //update taxonomy in translation of variation
                foreach( $sitepress->get_active_languages() as $language ){

                    $trnsl_variation_id = apply_filters( 'translate_object_id', $variation->post_id, 'product_variation', false, $language['code'] );
                    if( !is_null( $trnsl_variation_id ) ){
                        update_post_meta( $trnsl_variation_id, 'attribute_pa_'.$attribute, $term->slug );
                    }

                }

            }

        }

    }

    function set_original_attributes_for_products( $attribute ){
        global $sitepress;
        $terms = $this->get_attribute_terms( 'pa_'.$attribute );
        $cleared_products = array();
        foreach( $terms as $term ) {
            $args = array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'pa_'.$attribute,
                        'field' => 'slug',
                        'terms' => $term->slug
                    )
                )
            );

            $products = get_posts($args);

            foreach( $products as $product ){

                foreach( $sitepress->get_active_languages() as $language ) {

                    $trnsl_product_id = apply_filters( 'translate_object_id', $product->ID, 'product_variation', false, $language['code'] );

                    if ( !is_null( $trnsl_product_id ) ) {
                        if( !in_array( $trnsl_product_id, $trnsl_product_id ) ){
                            wp_delete_object_term_relationships( $trnsl_product_id, 'pa_'.$attribute );
                            $cleared_products[] = $trnsl_product_id;
                        }
                        wp_set_object_terms( $trnsl_product_id, $term->slug, 'pa_'.$attribute, true );
                    }
                }
            }
        }
    }


    function is_translatable_attribute( $attr_name ){
        global $woocommerce_wpml;

        return isset( $woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] ) ? $woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] : 1;
    }

}