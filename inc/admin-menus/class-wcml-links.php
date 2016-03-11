<?php

class WCML_Links {

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress ){
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        add_filter( 'wpml_link_to_translation', array( $this, '_filter_link_to_translation' ), 100, 4 );
        add_filter( 'wpml_post_edit_page_link_to_translation', array( $this,'_filter_link_to_translation' ) );
        add_filter( 'wpml_post_translation_job_url', array( $this, '_filter_job_link_to_translation' ), 100, 4 );

    }

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

    public function _filter_link_to_translation( $link, $post_id, $lang, $trid ){

        if ( $this->woocommerce_wpml->settings[ 'trnsl_interface' ] &&
            (
                ( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'product' ) ||
                ( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) == 'product' )
            )
        ) {
            if ( empty( $post_id ) && isset( $_GET[ 'post' ] ) ) {
                $post_id = $_GET[ 'post' ];
            }
            if ( isset( $_GET[ 'post' ] ) || $this->get_original_product_language( $post_id ) != $lang ) {
                $link = admin_url( 'admin.php?page=wpml-wcml&tab=products&prid=' . $post_id );
            }
        }
        return $link;
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