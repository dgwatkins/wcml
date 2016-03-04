<?php

class WCML_Helper {

    function _construct(){

        wpml_test_reg_custom_post_type( 'product' );
        $settings_helper = wpml_load_settings_helper();
        $settings_helper->set_post_type_translatable( 'product' );
        $settings_helper->set_post_type_translatable( 'product_variation' );

    }


    public static function add_product( $language, $trid = false, $title = false, $parent = 0, $meta = array() ) {
        global $wpml_post_translations;

        if( !$title ){
            $title = 'Test Product ' . time() . rand( 1000, 9999 ) ;
        }

        $product_id = wpml_test_insert_post( $language, 'product', $trid, $title, $parent );

        $default_meta = array(
            '_price'            => 10,
            '_regular_price'    => 10,
            '_sale_price'       => '',
            '_sku'              => 'DUMMY SKU',
            '_manage-stock'     => 'no',
            '_tax_status'       => 'taxable',
            '_downloadable'     => 'no',
            '_virtual'          => 'taxable',
            '_visibility'       => 'visible',
            '_stock_status'     => 'instock'
        );

        foreach( $default_meta as $key => $value){
            update_post_meta( $product_id, $key, isset( $meta[$key] ) ? $meta[$key] : $default_meta[$key] );
        }

        $ret = new stdClass();

        $ret->id    = $product_id;
        $ret->trid  = !$trid ? $wpml_post_translations->get_element_trid( $product_id, 'post_product' ) : $trid;

        return $ret;

    }

    public static function add_term( $name, $taxonomy, $language, $product_id = false, $trid = false , $term_id = false) {
        global $wpml_term_translations;

        if( !$term_id ){
            $new_term = wpml_test_insert_term( $language, $taxonomy, $trid, $name );
            $term_id = $new_term[ 'term_id' ];
        }

        $term = get_term( $term_id, $taxonomy );

        if( $product_id ){
            wp_set_post_terms(
                $product_id,
                array( $term_id ),
                $taxonomy,
                true
            );
        }

        $term->trid = $wpml_term_translations->get_element_trid( $term->term_taxonomy_id );

        return $term;

    }

    public static function add_product_variation( $language, $trid = false, $product_id = 0 ) {
        global $wpml_post_translations;

        $product_id = wpml_test_insert_post( $language, 'product_variation', $trid, 'Variation ' . time() . rand( 1000, 9999 ), $product_id );

        $ret = new stdClass();

        $ret->id    = $product_id;
        $ret->trid  = $wpml_post_translations->get_element_trid( $product_id, 'post_product' );

        return $ret;

    }

    public static function register_attribute( $name) {

        $taxonomy   = 'pa_'.$name;
        wpml_test_reg_custom_taxonomy( $taxonomy );
        $settings_helper = wpml_load_settings_helper();
        $settings_helper->set_taxonomy_translatable( $taxonomy );

    }

    public static function add_attribute_term( $term, $attr_name, $language, $trid = false ) {
        global $wpml_term_translations;

        $term = wpml_test_insert_term( $language, 'pa_'.$attr_name, $trid, $term );
        $term['trid'] = $wpml_term_translations->get_element_trid( $term[ 'term_taxonomy_id' ] );

        return $term;

    }

    public static function add_local_attribute( $product_id, $name, $values ) {
        $orig_attrs = array(
            sanitize_title( $name ) =>
                array(
                    'name' => $name ,
                    'value' => $values,
                    'is_taxonomy' => 0
                ));
        add_post_meta( $product_id, '_product_attributes', $orig_attrs );


    }

    public static function update_product( $product_data ) {
        global $wpml_post_translations;

        wp_update_post( $product_data );

    }

}