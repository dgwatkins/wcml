<?php

class WCML_Translation_Editor{

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress ) {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        add_filter( 'wpml-translation-editor-fetch-job', array( $this, 'fetch_translation_job_for_editor' ), 10, 2 );
        add_filter( 'wpml-translation-editor-job-data', array( $this, 'get_translation_job_data_for_editor' ), 10, 2 );
        add_action( 'admin_print_scripts', array( $this, 'preselect_product_type_in_admin_screen' ), 11 );

    }

    public function fetch_translation_job_for_editor( $job, $job_details ) {
        global $wpdb;

        if ( $job_details[ 'job_type' ] == 'wc_product' ) {
            $job = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $wpdb );
        }

        return $job;
    }

    public function get_translation_job_data_for_editor( $job_data ) {
        global $iclTranslationManagement;

        // See if it's a WooCommerce product.
        $job = $iclTranslationManagement->get_translation_job ( $job_data['job_id'] );
        if ( $job && $job->original_post_type == 'post_product' ) {
            $job_data['job_type'] = 'wc_product';
            $job_data['job_id']   = $job->original_doc_id;
        }

        return $job_data;
    }



    public function preselect_product_type_in_admin_screen(){
        global $pagenow;
        if( 'post-new.php' == $pagenow ){
            if( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'product' && isset( $_GET[ 'trid' ] ) ){
                $translations = $this->sitepress->get_element_translations( $_GET[ 'trid' ], 'post_product_type' );
                foreach( $translations as $translation ){
                    if( $translation->original ) {
                        $source_lang = $translation->language_code;
                        break;
                    }
                }
                $terms = get_the_terms( $translations[ $source_lang ]->element_id, 'product_type' );
                echo '<script type="text/javascript">';
                echo PHP_EOL . '// <![CDATA[' . PHP_EOL;
                echo 'addLoadEvent(function(){'. PHP_EOL;
                echo "jQuery('#product-type option').removeAttr('selected');" . PHP_EOL;
                echo "jQuery('#product-type option[value=\"" . $terms[0]->slug . "\"]').attr('selected', 'selected');" . PHP_EOL;
                echo '});'. PHP_EOL;
                echo PHP_EOL . '// ]]>' . PHP_EOL;
                echo '</script>';
            }
        }
    }

}