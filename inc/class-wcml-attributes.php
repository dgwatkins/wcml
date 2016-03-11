<?php

class WCML_Attributes{

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress ){
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        add_action( 'init', array( $this, 'init' ) );

    }

    public function init(){

        if( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'product_attributes' && isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'product' ){
            $this->load_js_and_css();
            add_action( 'admin_footer', array( $this, 'not_translatable_html' ) );

            if( isset( $_POST[ 'save_attribute' ] ) && isset( $_GET[ 'edit' ] ) ){
                $this->set_attribute_readonly_config( $_GET[ 'edit' ], $_POST );
            }
        }

        add_action( 'woocommerce_attribute_added', array( $this, 'set_attribute_readonly_config' ), 10, 2 );

    }

    public function not_translatable_html(){
        $attr_id = isset( $_GET[ 'edit' ] ) ? absint( $_GET[ 'edit' ] ) : false;

        $attr_is_tnaslt = new WCML_Not_Translatable_Attributes( $attr_id, $this->woocommerce_wpml );
        $attr_is_tnaslt->show();
    }

    public function load_js_and_css(){

        wp_register_script( 'wcml-attributes', WCML_PLUGIN_URL . '/res/js/wcml-attributes.js', array( 'jquery' ), WCML_VERSION );
        wp_enqueue_script( 'wcml-attributes' );

    }

    public function get_attribute_terms( $attribute ){
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
                        SELECT * FROM {$wpdb->term_taxonomy} x JOIN {$wpdb->terms} t ON x.term_id = t.term_id WHERE x.taxonomy = %s", $attribute ) );

    }

    public function set_attribute_readonly_config( $id, $attribute ){

        $is_translatable = isset( $_POST[ 'wcml-is-translatable-attr' ] ) ? 1 : 0;

        if( $is_translatable === 0 ){
            //delete all translated attributes terms if "Translatable?" option un-checked
            $this->delete_translated_attribute_terms( $attribute['attribute_name'] );
            $this->set_variations_to_use_original_attributes( $attribute['attribute_name'] );
            $this->set_original_attributes_for_products( $attribute['attribute_name'] );
        }

        $wcml_settings = $this->woocommerce_wpml->get_settings();
        $wcml_settings[ 'attributes_settings' ][ $attribute['attribute_name'] ] = $is_translatable;
        $this->woocommerce_wpml->update_settings( $wcml_settings );

        $wpml_settings = $this->sitepress->get_settings();
        $wpml_settings['taxonomies_sync_option'][wc_attribute_taxonomy_name($attribute['attribute_name'])] = $is_translatable;

        if( isset($wpml_settings['translation-management'])){
            $wpml_settings['translation-management']['taxonomies_readonly_config'][wc_attribute_taxonomy_name( $attribute['attribute_name'] )] = $is_translatable;
        }

        $this->sitepress->save_settings($wpml_settings);

    }

    public function delete_translated_attribute_terms( $attribute ){
        $terms = $this->get_attribute_terms( 'pa_'.$attribute );

        foreach( $terms as $term ){
            $term_language_details = $this->sitepress->get_element_language_details( $term->term_id, 'tax_pa_'.$attribute );
            if( $term_language_details && $term_language_details->source_language_code ){
                wp_delete_term( $term->term_id, 'pa_'.$attribute );
            }
        }

    }

    public function set_variations_to_use_original_attributes( $attribute ){
        global $wpdb;
        $terms = $this->get_attribute_terms( 'pa_'.$attribute );

        foreach( $terms as $term ){
            $term_language_details = $this->sitepress->get_element_language_details( $term->term_id, 'tax_pa_'.$attribute );
            if( $term_language_details && is_null( $term_language_details->source_language_code ) ){
                $variations = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value = %s",  'attribute_pa_'.$attribute, $term->slug ) );

                foreach( $variations as $variation ){
                    //update taxonomy in translation of variation
                    foreach( $this->sitepress->get_active_languages() as $language ){

                        $trnsl_variation_id = apply_filters( 'translate_object_id', $variation->post_id, 'product_variation', false, $language['code'] );
                        if( !is_null( $trnsl_variation_id ) ){
                            update_post_meta( $trnsl_variation_id, 'attribute_pa_'.$attribute, $term->slug );
                        }
                    }
                }
            }
        }
    }

    public function set_original_attributes_for_products( $attribute ){

        $terms = $this->get_attribute_terms( 'pa_'.$attribute );
        $cleared_products = array();
        foreach( $terms as $term ) {
            $term_language_details = $this->sitepress->get_element_language_details( $term->term_id, 'tax_pa_'.$attribute );
            if( $term_language_details && is_null( $term_language_details->source_language_code ) ){
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

                    foreach( $this->sitepress->get_active_languages() as $language ) {

                        $trnsl_product_id = apply_filters( 'translate_object_id', $product->ID, 'product', false, $language['code'] );

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
    }


    public function is_translatable_attribute( $attr_name ){
        return isset( $this->woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] ) ? $this->woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] : 1;
    }

    public function sync_product_attr( $original_product_id, $tr_product_id, $language = false, $data = false ){

        //get "_product_attributes" from original product
        $orig_product_attrs = $this->get_product_atributes( $original_product_id );
        $trnsl_product_attrs = $this->get_product_atributes( $tr_product_id );

        $trnsl_labels = get_post_meta( $tr_product_id, 'attr_label_translations', true );

        foreach ( $orig_product_attrs as $key => $orig_product_attr ) {
            $sanitized_key = sanitize_title( $orig_product_attr[ 'name' ] );
            if( $sanitized_key != $key ) {
                $orig_product_attrs_buff = $orig_product_attrs[ $key ];
                unset( $orig_product_attrs[ $key ] );
                $orig_product_attrs[ $sanitized_key ] = $orig_product_attrs_buff;
                $key_to_save = $sanitized_key;
            }else{
                $key_to_save = $key;
            }
            if ( $data ){
                if ( isset( $data[ md5( $key ) ] ) && !empty( $data[ md5( $key ) ] ) && !is_array( $data[ md5( $key ) ] ) ) {
                    //get translation values from $data
                    $trnsl_labels[ $language ][ $key_to_save ] = stripslashes( $data[ md5( $key . '_name' ) ] );
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = $data[ md5( $key ) ];
                } else {
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = '';
                }
            }elseif( !$orig_product_attr[ 'is_taxonomy' ] ){
                if( isset( $trnsl_product_attrs[ $key ] ) ){
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = $trnsl_product_attrs[ $key ][ 'value' ];
                }else{
                    unset ( $orig_product_attrs[ $key_to_save ] );
                }
            }
        }

        update_post_meta( $tr_product_id, 'attr_label_translations', $trnsl_labels );
        //update "_product_attributes"
        update_post_meta( $tr_product_id, '_product_attributes', $orig_product_attrs );
    }

    public function get_product_atributes( $product_id ){
        $attributes = get_post_meta( $product_id, '_product_attributes', true );
        if( !is_array( $attributes ) ){
            $attributes = array();
        }
        return $attributes;
    }

}