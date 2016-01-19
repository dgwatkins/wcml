<?php

class WCML_Helper {


    public static function add_product( $product, $language, $trid = null ) {
        global $sitepress;

        if(is_string($product)){
            $product = array('post_title' => $product);
        }

        $default_product = array(
            'post_title'    => 'Test Product ' . time() . rand( 1000, 9999 ),
            'post_content'  => 'Test Product Content ' . time() . rand( 1000, 9999 ),
            'post_type'     => 'product',
            'post_status'   => 'publish'

        );

        foreach ( $default_product as $k => $v ) {
            if ( !isset($product[$k]) ) {
                $product[$k] = $v;
            }
        }

        $product_id = wp_insert_post( $product );

        $sitepress->set_element_language_details($product_id, 'post_product', $trid, $language);

        $ret = new stdClass();

        $ret->product       = $product;
        $ret->product_id    = $product_id;
        $ret->trid          = $sitepress->get_element_trid($product_id, 'post_product');

        return $ret;

    }

}