<?php

class WCML_Links {

    public static function generate_tracking_link( $link, $term = false, $content = false, $id = false ) {

        $params = '?utm_source=wcml-admin&utm_medium=plugin&utm_term=';
        $params .= $term ? $term : 'WPML';
        $params .= '&utm_content=';
        $params .= $content ? $content : 'required-plugins';
        $params .= '&utm_campaign=WCML';

        if ( $id ) {
            $params .= $id;
        }
        return $link . $params;

    }

    public static function filter_woocommerce_redirect_location( $link ) {
        global $sitepress;
        return html_entity_decode( $sitepress->convert_url( $link ) );
    }



    public function _filter_job_link_to_translation( $link, $post_id, $job_id, $lang ){
        global $woocommerce_wpml;

        if (!$woocommerce_wpml->settings['trnsl_interface']) {
            return $link;
        }
        if (get_post_type($post_id) == 'product') {
            $link = '#" data-action="product-translation-dialog"
                    class="js-wcml-dialog-trigger"
                    data-id="' . $post_id . '"
                    data-job_id="' . $job_id . '"
                    data-language="' . $lang;
        }
        return $link;
    }
}