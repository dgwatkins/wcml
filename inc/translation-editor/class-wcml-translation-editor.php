<?php

class WCML_Translation_Editor{

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress, &$wpdb ) {

        $this->woocommerce_wpml =& $woocommerce_wpml;
        $this->sitepress        =& $sitepress;
        $this->wpdb             =& $wpdb;

        add_filter( 'wpml-translation-editor-fetch-job', array( $this, 'fetch_translation_job_for_editor' ), 10, 2 );
        add_filter( 'wpml-translation-editor-job-data', array( $this, 'get_translation_job_data_for_editor' ), 10, 2 );
        add_action( 'admin_print_scripts', array( $this, 'preselect_product_type_in_admin_screen' ), 11 );

        add_filter( 'icl_post_alternative_languages', array( $this, 'hide_post_translation_links' ) );

        add_filter( 'manage_product_posts_columns', array( $this, 'add_languages_column' ), 100 );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'lock_variable_fields' ), 10, 3 );

        if( is_admin() ){
            add_filter( 'wpml_use_tm_editor', array( $this, 'force_woocommerce_native_editor'), 100 );
            add_action( 'wpml_pre_status_icon_display', array( $this, 'force_remove_wpml_translation_editor_links'), 100 );
            add_action( 'wp_ajax_wcml_editor_auto_slug', array( $this, 'auto_generate_slug' ) );
        }

    }

    public function fetch_translation_job_for_editor( $job, $job_details ) {

        if ( $job_details[ 'job_type' ] == 'post_product' ) {
            $job = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
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

    /**
     * Avoids the post translation links on the product post type.
     *
     * @global type $post
     * @return type
     */
    public function hide_post_translation_links($output){
        global $post;

        if( is_null( $post ) ){
            return $output;
        }

        $post_type = get_post_type( $post->ID );
        $checkout_page_id = get_option( 'woocommerce_checkout_page_id' );

        if( $post_type == 'product' || is_page( $checkout_page_id ) ){
            $output = '';
        }

        return $output;
    }

    public function create_product_translation_package( $product_id, $trid, $language, $status ){
        global $iclTranslationManagement;
        //create translation package
        $translation_id = $this->wpdb->get_var( $wpdb->prepare("
                                SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code='%s'
                            ", $trid, $language));

        $md5 = $iclTranslationManagement->post_md5( get_post( $product_id ) );
        $translation_package = $iclTranslationManagement->create_translation_package( $product_id );

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        list( $rid, $update ) = $iclTranslationManagement->update_translation_status( array(
            'translation_id'        => $translation_id,
            'status'                => $status,
            'translator_id'         => $user_id,
            'needs_update'          => 0,
            'md5'                   => $md5,
            'translation_service'   => 'local',
            'translation_package'   => serialize( $translation_package )
        ));

        if( !$update ){
            $job_id = $iclTranslationManagement->add_translation_job($rid, $user_id , $translation_package );
        }

    }

    public function add_languages_column( $columns ){

        if ( ( version_compare(ICL_SITEPRESS_VERSION, '3.2', '<') && version_compare( WOOCOMMERCE_VERSION, '2.3', '<' ) ) || array_key_exists( 'icl_translations', $columns ) ){
            return $columns;
        }
        $active_languages = $this->sitepress->get_active_languages();

        if ( count( $active_languages ) <= 1 || get_query_var( 'post_status' ) == 'trash') {
            return $columns;
        }
        $languages = array();

        foreach ( $active_languages as $v ) {
            if ( $v[ 'code' ] == $this->sitepress->get_current_language() )
                continue;
            $languages[ ] = $v[ 'code' ];
        }
        $res = $this->wpdb->get_results( $this->wpdb->prepare ( "
			SELECT f.lang_code, f.flag, f.from_template, l.name
			FROM {$this->wpdb->prefix}icl_flags f
				JOIN {$this->wpdb->prefix}icl_languages_translations l ON f.lang_code = l.language_code
			WHERE l.display_language_code = %s AND f.lang_code IN (%s)
		", $this->sitepress->get_admin_language(), join( "','", $languages ) ) );

        foreach ( $res as $r ) {
            if ( $r->from_template ) {
                $wp_upload_dir = wp_upload_dir();
                $flag_path     = $wp_upload_dir[ 'baseurl' ] . '/flags/';
            } else {
                $flag_path = ICL_PLUGIN_URL . '/res/flags/';
            }
            $flags[ $r->lang_code ] = '<img src="' . $flag_path . $r->flag . '" width="18" height="12" alt="' . $r->name . '" title="' . $r->name . '" />';
        }

        $flags_column = '';
        foreach ( $active_languages as $v ) {
            if ( isset( $flags[ $v[ 'code' ] ] ) )
                $flags_column .= $flags[ $v[ 'code' ] ];
        }

        $new_columns = array();
        $added = false;
        foreach ( $columns as $k => $v ) {
            $new_columns[ $k ] = $v;
            if ( $k == 'name' ) {
                $new_columns[ 'icl_translations' ] = $flags_column;
                $added = true;
            }
        }

        if( !$added ){
            $new_columns[ 'icl_translations' ] = $flags_column;
        }

        return $new_columns;
    }

    public function lock_variable_fields( $loop, $variation_data, $variation ){

        $product_id = false;

        if( ( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) == 'product' ) ){
            $product_id = $_GET[ 'post' ];
        }elseif( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'woocommerce_load_variations' && isset( $_POST[ 'product_id' ] ) ){
            $product_id = $_POST[ 'product_id' ];
        }

        if( !$product_id ){
            return;
        }elseif( ! $this->woocommerce_wpml->products->is_original_product( $product_id ) ){ ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    wcml_lock_variation_fields();
                });
            </script>
            <?php
        }
    }

    /**
     * Forces the translation editor to be used for products when enabled in WCML
     *
     * @param $use_tm_editor
     * @return int
     */
    public function force_woocommerce_native_editor( $use_tm_editor ){
        $current_screen  = get_current_screen();

        if( $current_screen->id == 'edit-product' || $current_screen->id == 'product' ){

            if ( !$this->woocommerce_wpml->settings[ 'trnsl_interface' ] ) {
                $use_tm_editor = 0;
            }else{
                $use_tm_editor = 1;
            }

        } elseif( $current_screen->id == 'wpml_page_wpml-wcml' ) {
            $use_tm_editor = 1;
        }

        return $use_tm_editor;
    }

    /**
     * Removes the translation editor links when the WooCommerce native products editor is used in WCML
     */
    public function force_remove_wpml_translation_editor_links(){
        global $wpml_tm_status_display_filter;
        $current_screen  = get_current_screen();

        if ( !$this->woocommerce_wpml->settings[ 'trnsl_interface' ] && ( $current_screen->id == 'edit-product' || $current_screen->id == 'product' ) ) {
            remove_filter( 'wpml_link_to_translation', array( $wpml_tm_status_display_filter, 'filter_status_link' ), 10, 4 );
        }

    }

    public function auto_generate_slug(){
        $title = filter_input( INPUT_POST, 'title');

        $post_name = sanitize_title( $title );

        $slug = wp_unique_post_slug($post_name, 0, 'draft', 'product', 0);
        $slug = urldecode( $slug );

        echo json_encode( array('slug' => $slug) );
        exit;


    }
}