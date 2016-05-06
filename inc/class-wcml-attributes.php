<?php

class WCML_Attributes{

    private $woocommerce_wpml;
    private $sitepress;
    private $wpdb;

    public function __construct( &$woocommerce_wpml, &$sitepress, &$wpdb ){
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;
        $this->wpdb = $wpdb;

        if( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'product_attributes' && isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'product' ){

            add_action( 'admin_footer', array( $this, 'not_translatable_html' ) );

            if( isset( $_POST[ 'save_attribute' ] ) && isset( $_GET[ 'edit' ] ) ){
                $this->set_attribute_readonly_config( $_GET[ 'edit' ], $_POST );
            }
        }

        add_action( 'woocommerce_attribute_added', array( $this, 'set_attribute_readonly_config' ), 10, 2 );
        add_filter( 'wpml_translation_job_post_meta_value_translated', array($this, 'filter_product_attributes_for_translation'), 10, 2 );

    }

    public function not_translatable_html(){
        $attr_id = isset( $_GET[ 'edit' ] ) ? absint( $_GET[ 'edit' ] ) : false;

        $attr_is_tnaslt = new WCML_Not_Translatable_Attributes( $attr_id, $this->woocommerce_wpml );
        $attr_is_tnaslt->show();
    }

    public function get_attribute_terms( $attribute ){

        return $this->wpdb->get_results($this->wpdb->prepare("
                        SELECT * FROM {$this->wpdb->term_taxonomy} x JOIN {$this->wpdb->terms} t ON x.term_id = t.term_id WHERE x.taxonomy = %s", $attribute ) );

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
        $this->set_attribute_config_in_wpml_settings( $attribute['attribute_name'], $is_translatable );
    }

    public function set_attribute_config_in_wpml_settings( $attribute_name, $is_translatable ){
        $wpml_settings = $this->sitepress->get_settings();
        $wpml_settings['taxonomies_sync_option'][wc_attribute_taxonomy_name( $attribute_name )] = $is_translatable;

        if( isset($wpml_settings['translation-management'])){
            $wpml_settings['translation-management']['taxonomies_readonly_config'][wc_attribute_taxonomy_name( $attribute_name )] = $is_translatable;
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
        $terms = $this->get_attribute_terms( 'pa_'.$attribute );

        foreach( $terms as $term ){
            $term_language_details = $this->sitepress->get_element_language_details( $term->term_id, 'tax_pa_'.$attribute );
            if( $term_language_details && is_null( $term_language_details->source_language_code ) ){
                $variations = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key=%s AND meta_value = %s",  'attribute_pa_'.$attribute, $term->slug ) );

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

        if( !isset( $this->woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] ) ){
            $this->set_attribute_config_in_wpml_settings( str_replace( 'pa_', '', $attr_name ), 1 );
        }

        return isset( $this->woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] ) ? $this->woocommerce_wpml->settings[ 'attributes_settings' ][ str_replace( 'pa_', '', $attr_name ) ] : 1;
    }

    public function get_translatable_attributes(){
        $attributes = wc_get_attribute_taxonomies();

        $translatable_attributes = array();
        foreach( $attributes as $attribute ){
            if( $this->is_translatable_attribute( $attribute->attribute_name) ){
                $translatable_attributes[] = $attribute;
            }
        }

        return $translatable_attributes;
    }

    public function set_translatable_attributes( $attributes ){
        $this->woocommerce_wpml->settings[ 'attributes_settings' ] = $attributes;
        $this->woocommerce_wpml->update_settings();
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
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = $data[ md5( $key ) ];
                } else {
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = '';
                }

                if ( isset( $data[ md5( $key . '_name' ) ] ) && !empty( $data[ md5( $key . '_name' ) ] ) && !is_array( $data[ md5( $key . '_name' ) ] ) ) {
                    //get translation values from $data
                    $trnsl_labels[ $language ][ $key_to_save ] = stripslashes( $data[ md5( $key . '_name' ) ] );
                } else {
                    $trnsl_labels[ $language ][ $key_to_save ] = '';
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

    public function sync_default_product_attr( $orig_post_id, $transl_post_id, $lang ){
        $original_default_attributes = get_post_meta( $orig_post_id, '_default_attributes', true );
        if( !empty( $original_default_attributes ) ){
            $unserialized_default_attributes = array();
            foreach(maybe_unserialize( $original_default_attributes ) as $attribute => $default_term_slug ){
                // get the correct language
                if ( substr( $attribute, 0, 3 ) == 'pa_' ) {
                    //attr is taxonomy
                    if( $this->is_translatable_attribute( $attribute ) ){
                        $default_term_id = $this->woocommerce_wpml->terms->wcml_get_term_id_by_slug( $attribute, $default_term_slug );
                        $tr_id = apply_filters( 'translate_object_id', $default_term_id, $attribute, false, $lang );

                        if( $tr_id ){
                            $translated_term = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $tr_id, $attribute );
                            $unserialized_default_attributes[ $attribute ] = $translated_term->slug;
                        }
                    }else{
                        $unserialized_default_attributes[ $attribute ] = $default_term_slug;
                    }
                }else{
                    //custom attr
                    $orig_product_attributes = get_post_meta( $orig_post_id, '_product_attributes', true );
                    $unserialized_orig_product_attributes = maybe_unserialize( $orig_product_attributes );

                    if( isset( $unserialized_orig_product_attributes[ $attribute ] ) ){
                        $orig_attr_values = explode( '|', $unserialized_orig_product_attributes[ $attribute ][ 'value' ] );

                        foreach( $orig_attr_values as $key => $orig_attr_value ){
                            $orig_attr_value_sanitized = strtolower( sanitize_title ( $orig_attr_value ) );

                            if( $orig_attr_value_sanitized == $default_term_slug || trim( $orig_attr_value ) == trim( $default_term_slug ) ){
                                $tnsl_product_attributes = get_post_meta( $transl_post_id, '_product_attributes', true );
                                $unserialized_tnsl_product_attributes = maybe_unserialize( $tnsl_product_attributes );

                                if( isset( $unserialized_tnsl_product_attributes[ $attribute ] ) ){
                                    $trnsl_attr_values = explode( '|', $unserialized_tnsl_product_attributes[ $attribute ][ 'value' ] );

                                    if( $orig_attr_value_sanitized == $default_term_slug ){
                                        $trnsl_attr_value = strtolower( sanitize_title( trim( $trnsl_attr_values[ $key ] ) ) );
                                    }else{
                                        $trnsl_attr_value = trim( $trnsl_attr_values[ $key ] );
                                    }
                                    $unserialized_default_attributes[ $attribute ] = $trnsl_attr_value;
                                }
                            }
                        }
                    }
                }
            }

            $data = array( 'meta_value' => maybe_serialize( $unserialized_default_attributes ) );
        }else{
            $data = array( 'meta_value' => maybe_serialize( array() ) );
        }

        $where = array( 'post_id' => $transl_post_id, 'meta_key' => '_default_attributes' );
        $this->wpdb->update( $this->wpdb->postmeta, $data, $where );
    }

    /*
     * get attribute translation
     */
    public function get_custom_attribute_translation( $product_id, $attribute_key, $attribute, $lang_code ){
        $tr_post_id = apply_filters( 'translate_object_id', $product_id, 'product', false, $lang_code );
        $transl = array();
        if( $tr_post_id ){
            if( !$attribute[ 'is_taxonomy' ] ){
                $tr_attrs = get_post_meta($tr_post_id, '_product_attributes', true);
                if( $tr_attrs ){
                    foreach( $tr_attrs as $key => $tr_attr ) {
                        if( $attribute_key == $key ){
                            $transl[ 'value' ] = $tr_attr[ 'value' ];
                            $trnsl_labels = maybe_unserialize( get_post_meta( $tr_post_id, 'attr_label_translations', true ) );

                            if( isset( $trnsl_labels[ $lang_code ][ $attribute_key ] ) ){
                                $transl[ 'name' ] = $trnsl_labels[ $lang_code ][ $attribute_key ];
                            }else{
                                $transl[ 'name' ] = $tr_attr[ 'name' ];
                            }
                            return $transl;
                        }
                    }
                }
                return false;
            }
        }
        return false;
    }

    /*
    * Get custom attribute translation
    * Returned translated attribute or original if missed
    */
    public function get_custom_attr_translation( $product_id, $tr_product_id, $taxonomy, $attribute ){
        $orig_product_attributes = get_post_meta( $product_id, '_product_attributes', true );
        $unserialized_orig_product_attributes = maybe_unserialize( $orig_product_attributes );

        foreach( $unserialized_orig_product_attributes as $orig_attr_key => $orig_product_attribute ){
            $orig_attr_key = urldecode( $orig_attr_key );
            if( strtolower( $taxonomy ) == $orig_attr_key ){
                $values = explode( '|', $orig_product_attribute[ 'value' ] );

                foreach( $values as $key_id => $value ){
                    if( trim( $value," " ) == $attribute ){
                        $attr_key_id = $key_id;
                    }
                }
            }
        }

        $trnsl_product_attributes = get_post_meta( $tr_product_id, '_product_attributes', true );
        $unserialized_trnsl_product_attributes = maybe_unserialize( $trnsl_product_attributes );
        $taxonomy = sanitize_title( $taxonomy );
        $trnsl_attr_values = explode( '|', $unserialized_trnsl_product_attributes[ $taxonomy ][ 'value' ] );

        if( isset( $attr_key_id ) && isset( $trnsl_attr_values[ $attr_key_id ] ) ){
            return trim( $trnsl_attr_values[ $attr_key_id ] );
        }

        return $attribute;
    }

    public function filter_product_attributes_for_translation( $translated, $key ){
        $translated = $translated
            ? preg_match('#^(?!field-_product_attributes-(.+)-(.+)-(?!value|name))#', $key) : 0;

        return $translated;
    }

}