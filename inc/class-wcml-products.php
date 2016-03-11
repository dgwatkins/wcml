<?php

class WCML_Products{

    public $not_display_fields_for_variables_product = array( '_purchase_note', '_regular_price', '_sale_price', '_price', '_min_variation_price', '_max_variation_price', '_min_variation_regular_price', '_max_variation_regular_price', '_min_variation_sale_price', '_max_variation_sale_price' );
    private $tinymce_plugins_for_rtl = '';
    public $yoast_seo_fields = array( '_yoast_wpseo_focuskw', '_yoast_wpseo_title', '_yoast_wpseo_metadesc' );
    public $tp_support;

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress )
    {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        add_action( 'init', array( $this, 'init' ) );

        //add action for coupons data from WC_Coupon construct
        add_action( 'woocommerce_coupon_loaded', array( $this, 'wcml_coupon_loaded' ) );
        add_action( 'init', array( $this, 'set_prices_config' ), 9999 ); // After TM

    }

    public function init(){

        if(is_admin()){
            add_action( 'wp_ajax_wcml_update_product', array( $this, 'update_product_actions' ) );
            add_action( 'wp_ajax_wcml_product_data', array( $this, 'product_data_html' ) );

            // filters to sync variable products
            add_action( 'save_post', array( $this, 'sync_post_action' ), 110, 2 ); // After WPML

            add_filter( 'future_product', array( $this, 'set_schedule_for_translations'), 10, 2 );
            //when save new attachment duplicate product gallery
            add_action( 'wpml_media_create_duplicate_attachment', array( $this, 'sync_product_gallery_duplicate_attachment' ), 11, 2 );
            add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'sync_product_variations_action' ), 11 );
            add_action( 'wp_ajax_woocommerce_remove_variations', array( $this, 'remove_translations_for_variations' ), 9 );

            add_filter( 'icl_make_duplicate', array( $this, 'icl_make_duplicate'), 11, 4 );

            //remove media sync on product page
            add_action( 'admin_head', array( $this, 'remove_language_options' ), 11 );

            add_filter( 'woocommerce_json_search_found_products', array( $this, 'woocommerce_json_search_found_products' ) );
            add_filter( 'tiny_mce_before_init', array( $this, '_mce_set_plugins' ), 9 );

            add_action( 'woocommerce_duplicate_product', array( $this, 'woocommerce_duplicate_product' ), 10, 2 );

            add_filter( 'post_row_actions', array( $this, 'filter_product_actions' ), 10, 2 );

            add_filter ( 'locale',array( $this, 'update_product_action_locale_check' ) );

            add_filter( 'wpml_translation_job_post_meta_value_translated', array($this, 'filter_product_attributes_for_translation'), 10, 2 );

            add_action( 'wp_ajax_woocommerce_feature_product' , array( $this, 'sync_feature_product_meta' ), 9 );

            if (defined('ICL_SITEPRESS_VERSION') && version_compare(ICL_SITEPRESS_VERSION, '3.2', '>=')) {
                $this->tp_support = new WCML_TP_Support();
            }

            //downloadable files custom
            add_action( 'woocommerce_product_options_downloads', array( $this, 'product_options_downloads_custom_option' ) );
            add_action( 'woocommerce_variation_options_download', array( $this, 'product_options_downloads_custom_option' ), 10, 3 );

        }else{
            add_filter('woocommerce_json_search_found_products', array($this, 'filter_found_products_by_language'));
            add_filter( 'loop_shop_post_in', array( $this, 'filter_products_with_custom_prices' ), 100 );
            add_filter( 'woocommerce_related_products_args', array( $this, 'filter_related_products_args' ) );
        }

        add_action('woocommerce_email', array($this, 'woocommerce_email_refresh_text_domain'));
        add_action('wp_ajax_woocommerce_update_shipping_method', array($this, 'wcml_refresh_text_domain'), 9);
        add_action('wp_ajax_nopriv_woocommerce_update_shipping_method', array($this, 'wcml_refresh_text_domain'), 9);


        add_filter( 'woocommerce_upsell_crosssell_search_products', array( $this, 'filter_woocommerce_upsell_crosssell_posts_by_language' ) );

        add_filter( 'icl_post_alternative_languages', array( $this, 'hide_post_translation_links' ) );

        add_action( 'woocommerce_reduce_order_stock', array( $this, 'sync_product_stocks_reduce' ) );
        add_action( 'woocommerce_restore_order_stock', array( $this, 'sync_product_stocks_restore' ) );

        add_action('icl_pro_translation_completed', array($this, 'icl_pro_translation_completed'));

        //add filter when add term on product page
        add_filter( 'wpml_create_term_lang', array( $this, 'product_page_add_language_info_to_term' ) );

        //quick & bulk edit
        add_action( 'woocommerce_product_quick_edit_save', array( $this, 'woocommerce_product_quick_edit_save' ) );
        add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'woocommerce_product_quick_edit_save' ) );

        //save taxonomy in WPML interface
        add_action( 'wp_ajax_wpml_tt_save_term_translation', array( $this, 'update_taxonomy_in_variations' ), 7 );

        add_action( 'wp_ajax_woocommerce_remove_variation', array( $this, 'remove_variation_ajax' ), 9 );

        if(defined('WPSEO_VERSION') && defined('WPSEO_PATH') && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab']) && $_GET['tab'] == 'products'){
            if(version_compare(WPSEO_VERSION, '3', '<' )) {
                require_once WPSEO_PATH . 'admin/class-metabox.php';
            } elseif( file_exists( WPSEO_PATH . 'admin/metabox/class-metabox.php' ) ) {
                require_once WPSEO_PATH . 'admin/metabox/class-metabox.php';
            }
        }

        // Override cached widget id
        add_filter( 'woocommerce_cached_widget_id', array( $this, 'override_cached_widget_id' ) );

        //update menu_order fro translations after ordering original products
        add_action( 'woocommerce_after_product_ordering', array( $this, 'update_all_products_translations_ordering' ) );

        //filter to copy excerpt value
        add_filter( 'wpml_copy_from_original_custom_fields', array( $this, 'filter_excerpt_field_content_copy' ) );

        add_filter( 'icl_wpml_config_array', array( $this, 'set_taxonomies_config' ) );

        add_filter( 'manage_product_posts_columns', array( $this, 'add_languages_column' ), 100 );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'lock_variable_fields' ), 10, 3 );

        add_action( 'woocommerce_product_set_stock_status', array($this, 'sync_stock_status_for_translations'), 10, 2);
        add_action( 'woocommerce_variation_set_stock_status', array($this, 'sync_stock_status_for_translations'), 10, 2);
        
    }

    public function create_product_translation_package( $product_id, $trid, $language, $status ){
        global $wpdb, $iclTranslationManagement;
        //create translation package
        $translation_id = $wpdb->get_var( $wpdb->prepare("
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

    public function sync_product_data( $original_product_id, $tr_product_id, $lang ){

        $this->duplicate_product_post_meta( $original_product_id, $tr_product_id );

        $this->woocommerce_wpml->attributes->sync_product_attr( $original_product_id, $tr_product_id );

        $this->sync_default_product_attr( $original_product_id, $tr_product_id, $lang );

        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );
        //sync media
        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ){
            //sync feature image
            $this->sync_thumbnail_id( $original_product_id, $tr_product_id, $lang );
        }

        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ]){
            //sync product gallery
            $this->sync_product_gallery( $original_product_id );
        }

        //sync taxonomies
        $this->sync_product_taxonomies( $original_product_id, $tr_product_id, $lang );

        //duplicate variations
        $this->sync_product_variations( $original_product_id, $tr_product_id, $lang );

        $this->sync_linked_products( $original_product_id, $tr_product_id, $lang );
    }

    public function is_variable_product( $product_id ){
        global $wpdb;
        $get_variation_term_taxonomy_ids = $wpdb->get_col( "SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable' AND tt.taxonomy = 'product_type'" );
        $get_variation_term_taxonomy_ids = apply_filters( 'wcml_variation_term_taxonomy_ids',(array)$get_variation_term_taxonomy_ids );

        $is_variable_product = $wpdb->get_var( $wpdb->prepare( "SELECT count(object_id) FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN (".join(',',$get_variation_term_taxonomy_ids).")",$product_id ) );
        return $is_variable_product;
    }

    public function is_grouped_product($product_id){
        global $wpdb;
        $get_variation_term_taxonomy_id = $wpdb->get_var( "SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'grouped'" );
        $is_grouped_product = $wpdb->get_var( $wpdb->prepare( "SELECT count(object_id) FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d ",$product_id,$get_variation_term_taxonomy_id ) );

        return $is_grouped_product;
    }

    public function sync_product_taxonomies( $original_product_id, $tr_product_id, $lang ){
        global $wpdb;
        remove_filter( 'get_term', array( $this->sitepress,'get_term_adjust_id' ) ); // AVOID filtering to current language
        $taxonomies = get_object_taxonomies( 'product' );
        foreach( $taxonomies as $taxonomy ) {
            $terms = get_the_terms( $original_product_id, $taxonomy );
            $terms_array = array();
            if ( $terms ) {
                foreach ( $terms as $term ) {
                    if( $term->taxonomy == "product_type" ){
                        $terms_array[] = $term->name;
                        continue;
                    }
                    $tr_id = apply_filters( 'translate_object_id', $term->term_id, $taxonomy, false, $lang );
                    if( !is_null( $tr_id ) ){
                        // not using get_term - unfiltered get_term
                        $translated_term = $wpdb->get_row( $wpdb->prepare( "
                            SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_id, $taxonomy ) );
                        $terms_array[] = $translated_term->term_id;
                    }
                }
                if( $taxonomy != 'product_type' && !is_taxonomy_hierarchical( $taxonomy ) ){
                    $terms_array = array_unique( array_map( 'intval', $terms_array ) );
                }
                $this->sitepress->switch_lang( $lang );
                wp_set_post_terms( $tr_product_id, $terms_array, $taxonomy );
                $this->sitepress->switch_lang();
            }
        }
    }

    //duplicate product post meta
    public function duplicate_product_post_meta( $original_product_id, $trnsl_product_id, $data = false , $add = false ){
        $settings = $this->sitepress->get_settings();
        $lang = $this->sitepress->get_language_for_element( $trnsl_product_id,'post_product' );
        $all_meta = get_post_custom( $original_product_id );
        unset( $all_meta[ '_thumbnail_id' ] );

        foreach( wp_get_post_terms( $original_product_id, 'product_type', array( "fields" => "names" ) ) as $type ){
            $product_type = $type;
        }

        foreach ( $all_meta as $key => $meta ) {
            if (
                !isset( $settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] ) ||
                $settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] == 0
            ) {
                continue;
            }
            foreach ( $meta as $meta_value ) {
                if( $key == '_downloadable_files' ){
                    $this->sync_files_to_translations( $original_product_id, $trnsl_product_id, $data );
                }elseif ( $data ) {
                    if (
                        isset( $data[ md5( $key ) ] ) &&
                        isset( $settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] ) &&
                        $settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] == 2 ) {
                        $meta_value = $data[ md5( $key ) ];
                    }
                    $meta_value = apply_filters( 'wcml_meta_value_before_add', $meta_value, $key );
                    update_post_meta( $trnsl_product_id, $key, $meta_value );
                }
            }
        }

        do_action( 'wcml_after_duplicate_product_post_meta', $original_product_id, $trnsl_product_id, $data );
    }

    public function sync_product_gallery( $product_id ){
        if( !defined( 'WPML_MEDIA_VERSION' ) ){
            return;
        }
        $product_gallery = get_post_meta( $product_id, '_product_image_gallery', true );
        $gallery_ids = explode( ',', $product_gallery );

        $trid = $this->sitepress->get_element_trid( $product_id, 'post_product' );
        $translations = $this->sitepress->get_element_translations( $trid, 'post_product', true );
        foreach( $translations as $translation ){
            $duplicated_ids = '';
            if ( !$translation->original ) {
                foreach( $gallery_ids as $image_id ){
                    if( get_post( $image_id ) ) {
                        $duplicated_id = apply_filters( 'translate_object_id', $image_id, 'attachment', false, $translation->language_code );
                        if ( is_null( $duplicated_id ) && $image_id ) {
                            $duplicated_id = WPML_Media::create_duplicate_attachment( $image_id, wp_get_post_parent_id( $image_id ), $translation->language_code );
                        }
                        $duplicated_ids .= $duplicated_id . ',';
                    }
                }
                $duplicated_ids = substr( $duplicated_ids, 0, strlen( $duplicated_ids ) - 1 );
                update_post_meta( $translation->element_id, '_product_image_gallery', $duplicated_ids );
            }
        }
    }

    public function get_translation_flags( $active_languages, $slang = false, $job_language = false ){
        $available_languages = array();

        foreach( $active_languages as $key => $language ){
            if( $job_language && $language[ 'code' ] != $job_language ) {
                continue;
            }elseif ( !$slang ||
                (
                    ( $slang != $language[ 'code' ] ) &&
                    ( current_user_can( 'wpml_operate_woocommerce_multilingual' ) ||
                        wpml_check_user_is_translator( $slang, $language[ 'code' ] ) ) &&
                    ( !isset( $_POST[ 'translation_status_lang' ] ) ||
                        ( isset( $_POST[ 'translation_status_lang' ] ) &&
                            ( $_POST[ 'translation_status_lang' ] == $language[ 'code' ] ) ||
                            $_POST[ 'translation_status_lang' ]=='' )
                    )
                )
            ){
                $available_languages[ $key ][ 'name' ] = $language[ 'english_name' ];
                $available_languages[ $key ][ 'flag_url' ] = $this->sitepress->get_flag_url( $language[ 'code' ] );
            }
        }

        return $available_languages;
    }

    public function get_translation_statuses( $original_product_id, $product_translations, $active_languages, $slang = false, $trid = false, $job_language = false ){
        global $wpdb;

        foreach ( $active_languages as $language ) {
            if( $job_language && $language['code'] != $job_language ) {
                continue;
            }elseif( isset( $product_translations[ $language[ 'code' ] ] ) && $product_translations[ $language[ 'code' ] ]->original ) { ?>
                <span title="<?php echo $language['english_name'] . ': ' . __('Original language', 'woocommerce-multilingual'); ?>">
                    <i class="otgs-ico-original"></i>
                </span>
            <?php }elseif(
                    $slang != $language[ 'code' ] &&
                    ( !isset( $_POST[ 'translation_status_lang' ] ) ||
                        ( isset( $_POST['translation_status_lang']) &&
                            $_POST['translation_status_lang'] == $language['code'] ||
                            $_POST['translation_status_lang'] == ''
                        )
                    )
                ) {
                    if( isset( $product_translations[ $language[ 'code' ] ] ) ) {
                        $job_id = $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT tj.job_id FROM {$wpdb->prefix}icl_translate_job AS tj
                                         LEFT JOIN {$wpdb->prefix}icl_translation_status AS ts
                                         ON tj.rid = ts.rid WHERE ts.translation_id=%d",
                                        $product_translations[ $language[ 'code' ] ]->translation_id )
                                    );
                    }else{
                        $job_id = false;
                    }

                    if( !current_user_can( 'wpml_manage_woocommerce_multilingual' ) && isset( $product_translations[ $language[ 'code' ] ] ) ) {
                        $tr_status = $wpdb->get_row(
                                        $wpdb->prepare(
                                            "SELECT status,translator_id FROM " . $wpdb->prefix . "icl_translation_status
                                            WHERE translation_id = %d",
                                            $product_translations[ $language[ 'code' ] ]->translation_id )
                                        );

                        if( !is_null( $tr_status ) && get_current_user_id() != $tr_status->translator_id ){
                            if( $tr_status->status == ICL_TM_IN_PROGRESS ) { ?>
                                <a title="<?php _e( 'Translation in progress', 'woocommerce-multilingual' ); ?>"><i
                                        class="otgs-ico-in-progress"></i></a>
                                <?php continue;
                            }elseif( $tr_status->status == ICL_TM_WAITING_FOR_TRANSLATOR && !$job_id ){
                                $tr_job_id = $wpdb->get_var(
                                                $wpdb->prepare(
                                                    "SELECT j.job_id FROM {$wpdb->prefix}icl_translate_job j
                                                    JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                                                    WHERE s.translation_id = %d",
                                                    $product_translations[ $language[ 'code' ] ]->translation_id )
                                                );
                                ?>
                                <a data-action="product-translation-dialog" class="js-wcml-dialog-trigger"
                                   data-id="<?php echo $original_product_id; ?>"
                                   data-job_id="<?php echo $tr_job_id; ?>"
                                   data-language="<?php echo $language[ 'code' ]; ?>"
                                   title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Take this and edit', 'woocommerce-multilingual' ); ?>">
                                    <i class="otgs-ico-add"></i>
                                </a>
                                <?php continue;
                            }
                        }
                    } ?>
                    <a class="js-wcml-translation-dialog-trigger"
                       data-id="<?php echo $original_product_id; ?>"
                       data-job_id="<?php echo $job_id; ?>"
                       data-language="<?php echo $language[ 'code' ]; ?>"

                        <?php if( isset( $product_translations[ $language[ 'code' ] ] ) ){
                            $tr_status = $wpdb->get_row(
                                            $wpdb->prepare(
                                                "SELECT status,needs_update FROM " . $wpdb->prefix . "icl_translation_status
                                                WHERE translation_id = %d",
                                                $product_translations[ $language[ 'code' ] ]->translation_id )
                                            );
                            if  (!$tr_status ) { ?>
                                title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Add translation', 'woocommerce-multilingual' ); ?>">
                                <i class="otgs-ico-add"></i>
                            <?php }elseif( $tr_status->needs_update ){ ?>
                                title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Update translation', 'woocommerce-multilingual' ); ?>">
                                <i class="otgs-ico-refresh"></i>
                            <?php }elseif( $tr_status->status != ICL_TM_COMPLETE && $tr_status->status != ICL_TM_DUPLICATE ){ ?>
                                title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Finish translating', 'woocommerce-multilingual' ); ?>">
                                <i class="otgs-ico-refresh"></i>
                            <?php }elseif( $tr_status->status == ICL_TM_COMPLETE ){ ?>
                                title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Edit translation', 'woocommerce-multilingual' ); ?>">
                                <i class="otgs-ico-edit"></i>
                            <?php }elseif( $tr_status->status == ICL_TM_DUPLICATE ){ ?>
                                title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Edit translation', 'woocommerce-multilingual' ); ?>">
                                <i class="otgs-ico-duplicate"></i>
                            <?php }
                        } else { ?>
                            title="<?php echo $language[ 'english_name' ] . ': ' . __( 'Add translation', 'woocommerce-multilingual' ); ?>">
                            <i class="otgs-ico-add"></i>
                        <?php } ?>
                    </a>
            <?php } ?>
        <?php }
    }

    /*
     * sync product variations
     * $product_id - original product id
     * $tr_product_id - translated product id
     * $lang - trnsl language
     * $data - array of values (when we save original product this array is empty, but when we update translation in this array we have price values and etc.)     *
     * */
    public function sync_product_variations( $product_id, $tr_product_id, $lang, $data = false, $trbl = false ){
        global $wpdb, $sitepress_settings, $wpml_post_translations;

        remove_action ( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
        $is_variable_product = $this->is_variable_product( $product_id );

        if( $is_variable_product ){
            $get_all_post_variations = $wpdb->get_results(
                                            $wpdb->prepare(
                                                "SELECT * FROM $wpdb->posts
                                                WHERE post_status IN ('publish','private')
                                                  AND post_type = 'product_variation'
                                                  AND post_parent = %d ORDER BY ID",
                                            $product_id )
                                        );
            $duplicated_post_variation_ids = array();
            $min_max_prices = array();

            foreach( $get_all_post_variations as $k => $post_data ){
                $duplicated_post_variation_ids[] = $post_data->ID;
            }

            foreach( $min_max_prices as $price_key => $min_max_price ){
                update_post_meta( $product_id, $price_key, $min_max_price );
            }
            $all_taxs = get_object_taxonomies( 'product_variation' );

            foreach( $get_all_post_variations as $k => $post_data ) {
                // Find if this has already been duplicated
                $variation_id = $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT post_id FROM $wpdb->postmeta AS pm
                                        JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = pm.post_id
                                        WHERE tr.element_type = 'post_product_variation'
                                          AND tr.language_code = %s
                                          AND pm.meta_key = '_wcml_duplicate_of_variation'
                                          AND pm.meta_value = %d",
                                    $lang, $post_data->ID )
                                );
                $trid = $this->sitepress->get_element_trid( $post_data->ID, 'post_product_variation' );
                if( !empty( $variation_id ) && !is_null( $variation_id ) ) {
                    // Update variation
                    wp_update_post( array(
                        'ID' => $variation_id,
                        'post_author' => $post_data->post_author,
                        'post_date_gmt' => $post_data->post_date_gmt,
                        'post_content' => $post_data->post_content,
                        'post_title' => $post_data->post_title,
                        'post_excerpt' => $post_data->post_excerpt,
                        'post_status' => $post_data->post_status,
                        'comment_status' => $post_data->comment_status,
                        'ping_status' => $post_data->ping_status,
                        'post_password' => $post_data->post_password,
                        'post_name' => $post_data->post_name,
                        'to_ping' => $post_data->to_ping,
                        'pinged' => $post_data->pinged,
                        'post_modified' => $post_data->post_modified,
                        'post_modified_gmt' => $post_data->post_modified_gmt,
                        'post_content_filtered' => $post_data->post_content_filtered,
                        'post_parent' => $tr_product_id, // current post ID
                        'menu_order' => $post_data->menu_order,
                        'post_type' => $post_data->post_type,
                        'post_mime_type' => $post_data->post_mime_type,
                        'comment_count' => $post_data->comment_count
                    ));
                } else {
                    // Add new variation
                    $guid = $post_data->guid;
                    $replaced_guid = str_replace( $product_id, $tr_product_id, $guid );
                    $slug = $post_data->post_name;
                    $replaced_slug = str_replace( $product_id, $tr_product_id, $slug );
                    $variation_id = wp_insert_post( array(
                        'post_author' => $post_data->post_author,
                        'post_date_gmt' => $post_data->post_date_gmt,
                        'post_content' => $post_data->post_content,
                        'post_title' => $post_data->post_title,
                        'post_excerpt' => $post_data->post_excerpt,
                        'post_status' => $post_data->post_status,
                        'comment_status' => $post_data->comment_status,
                        'ping_status' => $post_data->ping_status,
                        'post_password' => $post_data->post_password,
                        'post_name' => $replaced_slug,
                        'to_ping' => $post_data->to_ping,
                        'pinged' => $post_data->pinged,
                        'post_modified' => $post_data->post_modified,
                        'post_modified_gmt' => $post_data->post_modified_gmt,
                        'post_content_filtered' => $post_data->post_content_filtered,
                        'post_parent' => $tr_product_id, // current post ID
                        'guid' => $replaced_guid,
                        'menu_order' => $post_data->menu_order,
                        'post_type' => $post_data->post_type,
                        'post_mime_type' => $post_data->post_mime_type,
                        'comment_count' => $post_data->comment_count
                    ));
                    add_post_meta( $variation_id, '_wcml_duplicate_of_variation', $post_data->ID );
                    $this->sitepress->set_element_language_details( $variation_id, 'post_product_variation', $trid, $lang );
                }
                //sync media
                $this->sync_thumbnail_id( $post_data->ID, $variation_id, $lang );
                //sync file_paths
                $this->sync_files_to_translations( $post_data->ID, $variation_id, $data );
                //sync description
                if( isset( $data[ md5( 'variation_desc'.$variation_id ) ] ) ){
                    update_post_meta( $variation_id, '_variation_description', $data[ md5( 'variation_desc'.$variation_id ) ] );
                }
                // sync taxonomies
                if ( !empty( $all_taxs ) ) {
                    foreach ( $all_taxs as $tt ) {
                        $terms = get_the_terms( $post_data->ID, $tt );
                        if ( !empty( $terms ) ) {
                            $tax_sync = array();
                            foreach ( $terms as $term ) {
                                if ( $this->sitepress->is_translated_taxonomy( $tt ) ) {
                                    $term_id = apply_filters( 'translate_object_id', $term->term_id, $tt, false, $lang );
                                } else {
                                    $term_id = $term->term_id;
                                }
                                if ( $term_id ) {
                                    $tax_sync[] = intval( $term_id );
                                }
                            }
                            //set the fourth parameter in 'true' because we need to add new terms, instead of replacing all
                            wp_set_object_terms( $variation_id, $tax_sync, $tt, true );
                        }
                    }
                }
            }
            $get_current_post_variations = $wpdb->get_results(
                                                $wpdb->prepare(
                                                    "SELECT * FROM $wpdb->posts
                                                    WHERE post_status IN ('publish','private')
                                                    AND post_type = 'product_variation'
                                                    AND post_parent = %d ORDER BY ID",
                                                $tr_product_id )
                                            );
            // Delete variations that no longer exist
            foreach( $get_current_post_variations as $key => $post_data ){
                $variation_id = get_post_meta( $post_data->ID, '_wcml_duplicate_of_variation', true );
                if( !in_array( $variation_id, $duplicated_post_variation_ids ) ){
                    wp_delete_post( $post_data->ID, true );
                    unset( $get_current_post_variations[ $key ] );
                }
            }
            // custom fields to copy
            $cf = (array)$sitepress_settings[ 'translation-management' ][ 'custom_fields_translation' ];
            // synchronize post variations post meta
            $current_post_variation_ids = array();
            foreach( $get_current_post_variations as $k => $post_data ){
                $current_post_variation_ids[] = $post_data->ID;
            }
            // refresh parent-children transients
            delete_transient( 'wc_product_children_' . $tr_product_id );
            delete_transient( '_transient_wc_product_children_ids_' . $tr_product_id );
            $original_product_attr = get_post_meta( $product_id, '_product_attributes', true );
            $tr_product_attr = get_post_meta( $tr_product_id, '_product_attributes', true );

            foreach( $duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id ){
                $get_all_post_meta = $wpdb->get_results(
                                        $wpdb->prepare(
                                            "SELECT * FROM $wpdb->postmeta WHERE post_id = %d",
                                            $duplicated_post_variation_id )
                                        );
                //delete non exists attributes
                $get_all_variation_attributes = $wpdb->get_results(
                                                    $wpdb->prepare(
                                                        "SELECT * FROM $wpdb->postmeta
                                                        WHERE post_id = %d
                                                          AND meta_key LIKE 'attribute_%%' ",
                                                    $current_post_variation_ids[ $dp_key ] )
                                                );
                foreach( $get_all_variation_attributes as $variation_attribute ){
                    $attribute_name = substr( $variation_attribute->meta_key, 10 );
                    if( !isset( $original_product_attr[ $attribute_name ] ) ){
                        delete_post_meta( $current_post_variation_ids[ $dp_key ], $variation_attribute->meta_key );
                    }
                }

                foreach( $get_all_post_meta as $k => $post_meta ){
                    $meta_key = $post_meta->meta_key;
                    $meta_value = maybe_unserialize( $post_meta->meta_value );
                    // update current post variations meta
                    if( ( substr( $meta_key, 0, 10 ) == 'attribute_' || isset( $cf[ $meta_key ] ) && $cf[ $meta_key ] == 1 ) ) {
                        // adjust the global attribute slug in the custom field
                        $attid = null;
                        if( substr( $meta_key, 0, 10 ) == 'attribute_' ) {
                            $tax = substr( $meta_key, 10 );
                            if( taxonomy_exists( $tax ) ){
                                $attid = $this->wcml_get_term_id_by_slug( $tax, $meta_value );
                                if( $this->woocommerce_wpml->attributes->is_translatable_attribute( $tax ) && $attid ){
                                    $term_obj = $this->wcml_get_term_by_id( $attid, $tax );
                                    $trid = $this->sitepress->get_element_trid( $term_obj->term_taxonomy_id, 'tax_' . $tax );
                                    if( $trid ) {
                                        $translations = $this->sitepress->get_element_translations( $trid, 'tax_' . $tax );
                                        if( isset( $translations[ $lang ] ) ){
                                            $meta_value = $wpdb->get_var(
                                                            $wpdb->prepare(
                                                                "SELECT slug FROM $wpdb->terms WHERE term_id = %s",
                                                            $translations[ $lang ]->term_id )
                                                            );
                                        }else{
                                            $meta_value = $meta_value.'_'.$lang;
                                        }
                                    }
                                }
                            }else{
                                if( isset( $original_product_attr[ $tax ] ) ){
                                    $tax = sanitize_title( $original_product_attr[ $tax ][ 'name' ] );
                                }
                                if( isset( $original_product_attr[ $tax ] ) ){
                                    if( isset( $tr_product_attr[ $tax ] ) ){
                                        $values_arrs = array_map( 'trim', explode( '|', $original_product_attr[ $tax ][ 'value' ] ) );
                                        $values_arrs_tr = array_map( 'trim', explode( '|', $tr_product_attr[ $tax ][ 'value' ] ) );
                                        foreach( $values_arrs as $key => $value ){
                                            $value_sanitized = sanitize_title( $value );
                                            if(
                                                ( $value_sanitized == strtolower( urldecode( $meta_value ) ) ||
                                                    strtolower( $value_sanitized ) == $meta_value ||
                                                    $value == $meta_value )
                                                && isset( $values_arrs_tr[ $key ] ) )
                                            {
                                                $meta_value = $values_arrs_tr[ $key ];
                                            }
                                        }
                                    }else{
                                        $meta_value = $meta_value.'_'.$lang;
                                    }
                                }
                                $meta_key = 'attribute_'.$tax;
                            }
                        }
                        update_post_meta( $current_post_variation_ids[ $dp_key ], $meta_key, $meta_value );
                    }
                    //sync variation prices
                    if(
                        ( $this->woocommerce_wpml->settings[ 'enable_multi_currency' ] == WCML_MULTI_CURRENCIES_INDEPENDENT || $trbl ) &&
                        in_array( $meta_key, array( '_sale_price', '_regular_price', '_price' ) )
                    ){
                        $meta_value = get_post_meta( $duplicated_post_variation_ids[ $dp_key ], $meta_key, true );
                        update_post_meta( $current_post_variation_ids[ $dp_key ], $meta_key, $meta_value );
                    }
                }
            }
        }
        add_action ( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
    }

    /* Change locale to saving language - needs for sanitize_title exception wcml-390 */
    public function update_product_action_locale_check( $locale ){

        if( isset($_POST['action']) && $_POST['action'] == 'wpml_translation_dialog_save_job' ){
            return $this->sitepress->get_locale( $_POST[ 'job_details' ][ 'target' ] );
        }
        return $locale;
    }

    public function sync_linked_products( $product_id, $translated_product_id, $lang ){
        global $wpdb;

        //sync up-sells
        $original_up_sells = maybe_unserialize( get_post_meta( $product_id, '_upsell_ids', true ) );
        $trnsl_up_sells = array();
        if( $original_up_sells ){
            foreach( $original_up_sells as $original_up_sell_product ) {
                $trnsl_up_sells[] = apply_filters( 'translate_object_id', $original_up_sell_product, get_post_type( $original_up_sell_product ), false, $lang );
            }
        }
        update_post_meta( $translated_product_id, '_upsell_ids', $trnsl_up_sells );
        //sync cross-sells
        $original_cross_sells = maybe_unserialize( get_post_meta( $product_id, '_crosssell_ids', true ) );
        $trnsl_cross_sells = array();
        if( $original_cross_sells )
            foreach( $original_cross_sells as $original_cross_sell_product ){
                $trnsl_cross_sells[] = apply_filters( 'translate_object_id', $original_cross_sell_product, get_post_type( $original_cross_sell_product ), false, $lang );
            }
        update_post_meta( $translated_product_id, '_crosssell_ids', $trnsl_cross_sells );
        // refresh parent-children transients (e.g. this child goes to private or draft)
        $translated_product_parent_id = wp_get_post_parent_id( $translated_product_id );
        if ( $translated_product_parent_id ) {
            delete_transient( 'wc_product_children_' . $translated_product_parent_id );
            delete_transient( '_transient_wc_product_children_ids_' . $translated_product_parent_id );
        }
    }

    public function sync_thumbnail_id( $orig_post_id, $trnsl_post_id, $lang ){
        if( defined( 'WPML_MEDIA_VERSION' ) ){
            $thumbnail_id = get_post_meta( $orig_post_id, '_thumbnail_id', true );
            $trnsl_thumbnail = apply_filters( 'translate_object_id', $thumbnail_id, 'attachment', false, $lang );
            if( is_null( $trnsl_thumbnail ) && $thumbnail_id ){
                $trnsl_thumbnail = WPML_Media::create_duplicate_attachment( $thumbnail_id, wp_get_post_parent_id( $thumbnail_id ), $lang );
            }

            update_post_meta( $trnsl_post_id, '_thumbnail_id', $trnsl_thumbnail );
            update_post_meta( $orig_post_id, '_wpml_media_duplicate', 1 );
            update_post_meta( $orig_post_id, '_wpml_media_featured', 1 );
        }
    }

    //product quickedit
    public function filter_product_actions( $actions, $post ){
        if(
            $post->post_type == 'product' &&
            !$this->is_original_product( $post->ID ) &&
            isset( $actions[ 'inline hide-if-no-js' ] ) )
        {
            $new_actions = array();
            foreach( $actions as $key => $action ) {
                if( $key == 'inline hide-if-no-js' ) {
                    $new_actions[ 'quick_hide' ] = '<a href="#TB_inline?width=200&height=150&inlineId=quick_edit_notice" class="thickbox" title="' .
                                                    __('Edit this item inline', 'woocommerce-multilingual') . '">' .
                                                    __('Quick Edit', 'woocommerce-multilingual') . '</a>';
                } else {
                    $new_actions[ $key ] = $action;
                }
            }
            return $new_actions;
        }
        return $actions;
    }

    /**
     * Filters upsell/crosell products in the correct language.
     */
    function filter_found_products_by_language($found_products){
        global $wpdb, $sitepress;

        $current_page_language = $sitepress->get_current_language();

        foreach($found_products as $product_id => $output_v){
            $post_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = %d AND element_type LIKE 'post_%'", $product_id ) );
            $product_language = $post_data->language_code;

            if($product_language !== $current_page_language){
                unset($found_products[$product_id]);
            }
        }

        return $found_products;
    }

    /**
     * Takes off translated products from the Up-sells/Cross-sells tab.
     *
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function filter_woocommerce_upsell_crosssell_posts_by_language($posts){
        global $sitepress, $wpdb;

        foreach($posts as $key => $post){
            $post_id = $posts[$key]->ID;
            $post_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = %d ", $post_id), ARRAY_A );

            if($post_data['language_code'] !== $sitepress->get_current_language()){
                unset($posts[$key]);
            }
        }

        return $posts;
    }

    /**
     * Avoids the post translation links on the product post type.
     *
     * @global type $post
     * @return type
     */
    function hide_post_translation_links($output){
        global $post;

        if(is_null($post)){
            return $output;
        }

        $post_type = get_post_type($post->ID);
        $checkout_page_id = get_option('woocommerce_checkout_page_id');

        if($post_type == 'product' || is_page($checkout_page_id)){
            $output = '';
        }

        return $output;
    }

    function sync_product_stocks_reduce( $order ){
        return $this->sync_product_stocks( $order, 'reduce' );
    }

    function sync_product_stocks_restore( $order ){
        return $this->sync_product_stocks( $order, 'restore' );
    }

    function sync_product_stocks( $order, $action ){
        global $sitepress;
        $order_id = $order->id;

        foreach ( $order->get_items() as $item ) {
            if (isset($item['variation_id']) && $item['variation_id']>0){
                $trid = $sitepress->get_element_trid($item['variation_id'], 'post_product_variation');
                $translations = $sitepress->get_element_translations($trid,'post_product_variation');
                $ld = $sitepress->get_element_language_details($item['variation_id'], 'post_product_variation');
            } else {
                $trid = $sitepress->get_element_trid($item['product_id'], 'post_product');
                $translations = $sitepress->get_element_translations($trid,'post_product');
                $ld = $sitepress->get_element_language_details($item['product_id'], 'post_product');
            }

            // Process for non-current languages
            foreach($translations as $translation){
                if ($ld->language_code != $translation->language_code) {

                    //check if product exist
                    if(get_post_type($translation->element_id) == 'product_variation' && !get_post(wp_get_post_parent_id($translation->element_id))){
                        continue;
                    }

                    $_product = wc_get_product($translation->element_id);

                    if ( $_product && $_product->exists() && $_product->managing_stock() ) {

                        $total_sales    = get_post_meta($_product->id, 'total_sales', true);

                        if( $action == 'reduce'){
                            $stock  = $_product->reduce_stock($item['qty']);
                            $total_sales   += $item['qty'];
                        }else{
                            $stock  = $_product->increase_stock( $item['qty'] );
                            $total_sales   -= $item['qty'];
                        }
                        update_post_meta($translation->element_id, 'total_sales', $total_sales);
                    }
                }

            }
        }

    }

    function sanitize_cpa_values($values) {
        // Text based, separate by pipe
         $values = explode('|', esc_html(stripslashes($values)));
         $values = array_map('trim', $values);
         $values = implode('|', $values);
         return $values;
    }

    /**
     * This function takes care of synchronizing original product
     */
    function sync_post_action( $post_id, $post ){
        global $pagenow, $sitepress, $sitepress_settings,$woocommerce_wpml;
        $original_language = $this->get_original_product_language( $post_id );
        $current_language = $sitepress->get_current_language();
        $duplicated_post_id = apply_filters( 'translate_object_id', $post_id, 'product', false, $original_language );
        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );

        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ] ){
            //sync product gallery
            $this->sync_product_gallery( $duplicated_post_id );
        }

        // check its a product
        $post_type = get_post_type( $post_id );

        //set trid for variations
        if ( $post_type == 'product_variation' ) {
            $var_lang = $sitepress->get_language_for_element( wp_get_post_parent_id( $post_id ), 'post_product' );
            if( $this->is_original_product( wp_get_post_parent_id( $post_id ) ) ){
                $sitepress->set_element_language_details( $post_id, 'post_product_variation', false, $var_lang );
            }
        }

        if ( $post_type != 'product' ) {
            return;
        }

        // exceptions
        $ajax_call = ( !empty( $_POST[ 'icl_ajx_action' ] ) && $_POST[ 'icl_ajx_action' ] == 'make_duplicates' );
        if ( empty( $duplicated_post_id ) || isset( $_POST[ 'autosave' ] ) ) {
            return;
        }

        if( $pagenow != 'post.php' && $pagenow != 'post-new.php' && $pagenow != 'admin.php' && !$ajax_call ){
            return;
        }
        if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'trash') {
            return;
        }

        // If we reach this point, we go ahead with sync.
        // Remove filter to avoid double sync
        remove_action( 'save_post', array( $this, 'sync_post_action' ), 110, 2 );

        do_action( 'wcml_before_sync_product', $duplicated_post_id, $post_id );

        //trnsl_interface option
        if (!$woocommerce_wpml->settings['trnsl_interface'] && $original_language != $current_language ) {

            if( !isset( $_POST[ 'wp-preview' ] ) || empty( $_POST[ 'wp-preview' ] ) ){
                $this->sync_date_and_parent( $duplicated_post_id, $post_id, $current_language );
                $this->sync_product_data( $duplicated_post_id, $post_id, $current_language );

                do_action( 'wcml_before_sync_product_data', $duplicated_post_id, $post_id, $current_language );
            }

            return;
        }

        // get language code
        $language_details = $sitepress->get_element_language_details( $post_id, 'post_product' );
        if ( $pagenow == 'admin.php' && empty( $language_details ) ) {
            //translation editor support: sidestep icl_translations_cache
            global $wpdb;
            $language_details = $wpdb->get_row( $wpdb->prepare( "SELECT element_id, trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = 'post_product'", $post_id ) );
        }

        if ( empty( $language_details ) ) {
            return;
        }

        //save custom prices
        $this->save_custom_prices( $duplicated_post_id );

        //save files option
        $this->save_files_option( $duplicated_post_id );

        // pick posts to sync
        $traslated_products = array();
        $translations = $sitepress->get_element_translations( $language_details->trid, 'post_product', false, true );
        foreach ( $translations as $translation ) {
            if ( $translation->original ) {
                $original_product_id = $translation->element_id;
            } else {
                $traslated_products[ $translation->element_id ] = $translation;
            }
        }

        foreach( $traslated_products as $translated_product_id => $translation ) {
            $lang = $translation->language_code;

            do_action( 'wcml_before_sync_product_data', $original_product_id, $translated_product_id, $lang );

            // Filter upsell products, crosell products and default attributes for translations
            $this->duplicate_product_post_meta( $original_product_id, $translated_product_id );

            if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ){
                //sync feature image
                $this->sync_thumbnail_id( $original_product_id, $translated_product_id, $lang );
            }

            $this->sync_date_and_parent( $original_product_id, $translated_product_id, $lang );

            $this->sync_product_taxonomies( $original_product_id, $translated_product_id, $lang );

            $this->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );

            $this->woocommerce_wpml->attributes->sync_product_attr( $original_product_id, $translated_product_id );

            $this->update_order_for_product_translations( $original_product_id );

            // synchronize post variations
            $this->sync_product_variations( $original_product_id, $translated_product_id, $lang );
            $this->sync_linked_products( $original_product_id, $translated_product_id, $lang );

        }

        $this->sync_product_variations_custom_prices( $original_product_id );
    }


    function sync_product_variations_action( $product_id ){
        global $sitepress, $wpdb;

        if( $this->is_original_product( $product_id ) ){

            $this->sync_product_variations_custom_prices( $product_id );

            $trid = $sitepress->get_element_trid( $product_id, 'post_product' );
            if ( empty( $trid ) ) {
                $trid = $wpdb->get_var( $wpdb->prepare( "SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = 'post_product'", $product_id ) );
            }

            $translations = $sitepress->get_element_translations( $trid, 'post_product' );
            foreach ( $translations as $translation ) {
                if ( !$translation->original ) {
                    $this->sync_product_variations($product_id, $translation->element_id, $translation->language_code);

                    $this->sync_default_product_attr($product_id, $translation->element_id, $translation->language_code);
                }
            }

        }
    }

    function remove_translations_for_variations(){
        check_ajax_referer( 'delete-variations', 'security' );

        if ( ! current_user_can( 'edit_products' ) ) {
            die(-1);
        }

        global $sitepress;

        $variation_ids = (array) $_POST['variation_ids'];

        foreach ( $variation_ids as $variation_id ) {

            $trid = $sitepress->get_element_trid( $variation_id, 'post_product_variation' );
            $translations = $sitepress->get_element_translations( $trid, 'post_product_variation' );

            foreach ( $translations as $translation ) {
                if ( !$translation->original ) {
                    wp_delete_post( $translation->element_id );
                }
            }

        }

    }

    function save_custom_prices($post_id){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if( isset( $_POST[ '_wcml_custom_prices' ] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices' )){

            if( isset( $_POST[ '_wcml_custom_prices' ][ $post_id ] ) ) {
                $wcml_custom_prices_option = $_POST[ '_wcml_custom_prices' ][ $post_id ];
            }else{
                $current_option = get_post_meta( $post_id, '_wcml_custom_prices_status', true );
                $wcml_custom_prices_option = $current_option ? $current_option : 0;
            }

            update_post_meta( $post_id, '_wcml_custom_prices_status', $wcml_custom_prices_option );

            if( $wcml_custom_prices_option == 1){

                $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                foreach( $currencies as $code => $currency ){
                    $sale_price = wc_format_decimal( $_POST[ '_custom_sale_price' ][ $code ] );
                    $regular_price = wc_format_decimal( $_POST[ '_custom_regular_price' ][ $code ] );

                    $date_from = isset( $_POST[ '_custom_sale_price_dates_from' ][ $code ] ) ? strtotime( $_POST[ '_custom_sale_price_dates_from' ][ $code ] ) : '';
                    $date_to = isset( $_POST[ '_custom_sale_price_dates_to' ][ $code ] ) ? strtotime( $_POST[ '_custom_sale_price_dates_to' ][ $code ] ) : '';
                    $schedule = $_POST[ '_wcml_schedule' ][ $code ];

                    $custom_prices = apply_filters( 'wcml_update_custom_prices_values',
                        array( '_regular_price' => $regular_price,
                            '_sale_price' => $sale_price,
                            '_wcml_schedule_' => $schedule,
                            '_sale_price_dates_from' => $date_from,
                            '_sale_price_dates_to' => $date_to ),
                        $code
                    );

                    $this->update_custom_prices( $post_id, $custom_prices , $code );
                }
            }
        }
    }

    function save_files_option( $post_id ){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, 'wcml_save_files_option_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if( isset( $_POST[ 'wcml_file_path_option' ] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_files_option' )){

            if( isset( $_POST[ 'wcml_file_path_option' ][ $post_id ] ) ) {

                if( $_POST[ 'wcml_file_path_option' ][ $post_id ] == 'on' ){
                    update_post_meta( $post_id, 'wcml_sync_files', $_POST[ 'wcml_file_path_sync' ][ $post_id ] );
                }else{
                    delete_post_meta( $post_id, 'wcml_sync_files' );
                }
            }
        }
    }

    //sync product parent & post_status
    function sync_date_and_parent( $duplicated_post_id, $post_id, $lang ){
        global $wpdb,$woocommerce_wpml;

        $tr_parent_id = apply_filters( 'translate_object_id', wp_get_post_parent_id( $duplicated_post_id ), 'product', false, $lang );

        $orig_product = get_post( $duplicated_post_id );

        $args = array();
        $args[ 'post_parent' ] = is_null( $tr_parent_id )? 0 : $tr_parent_id;

        //sync product date
        if( !empty($woocommerce_wpml->settings[ 'products_sync_date' ]) ){
            $args[ 'post_date' ] = $orig_product->post_date;
        }

        $wpdb->update(
            $wpdb->posts,
            $args,
            array( 'id' => $post_id )
        );


    }

    function update_custom_prices( $post_id, $custom_prices, $code ){
        $price = '';

        foreach( $custom_prices as $custom_price_key => $custom_price_value ){
            update_post_meta( $post_id, $custom_price_key.'_'.$code, $custom_price_value );
        }

        if ( $custom_prices['_sale_price_dates_to']  && ! $custom_prices['_sale_price_dates_from'] )
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, strtotime( 'NOW', current_time( 'timestamp' ) ) );

        // Update price if on sale
        if (  $custom_prices['_sale_price'] != '' && $custom_prices['_sale_price_dates_to'] == '' &&  $custom_prices['_sale_price_dates_from'] == '' ){
            $price = stripslashes( $custom_prices['_sale_price'] );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices['_sale_price'] ) );
        }else{
            $price = stripslashes( $custom_prices['_regular_price'] );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices['_regular_price'] ) );
        }

        if ( $custom_prices['_sale_price'] != '' && $custom_prices['_sale_price_dates_from'] < strtotime( 'NOW', current_time( 'timestamp' ) ) ){
            update_post_meta( $post_id, '_price_'.$code, stripslashes($custom_prices['_sale_price']) );
            $price = stripslashes( $custom_prices['_sale_price'] );
        }

        if ( $custom_prices['_sale_price_dates_to'] && $custom_prices['_sale_price_dates_to'] < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
            update_post_meta( $post_id, '_price_'.$code, stripslashes($custom_prices['_regular_price']) );
            $price = stripslashes( $custom_prices['_regular_price'] );
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, '');
            update_post_meta( $post_id, '_sale_price_dates_to_'.$code, '');
        }

        return $price;
    }

    function sync_product_variations_custom_prices( $product_id ){
        global $wpdb,$woocommerce_wpml;
        $is_variable_product = $this->is_variable_product($product_id);

        if($is_variable_product){
            $get_all_post_variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts
                            WHERE post_status IN ('publish','private') AND post_type = 'product_variation' AND post_parent = %d ORDER BY ID",$product_id));

            $duplicated_post_variation_ids = array();
            $min_max_prices = array();
            foreach($get_all_post_variations as $k => $post_data){
                $duplicated_post_variation_ids[] = $post_data->ID;

                //save files option
                $this->save_files_option( $post_data->ID );

                if( !isset( $_POST['_wcml_custom_prices'][$post_data->ID] ) ){
                    continue; // save changes for individual variation
                }

                //save custom prices for variation
                $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_variation_' . $post_data->ID . '_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                if(isset( $_POST['_wcml_custom_prices'][$post_data->ID]) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices_variation_' . $post_data->ID )){

                    update_post_meta($post_data->ID,'_wcml_custom_prices_status',$_POST['_wcml_custom_prices'][$post_data->ID]);

                    $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                    if($_POST['_wcml_custom_prices'][$post_data->ID] == 1){
                        foreach($currencies as $code => $currency){
                            $sale_price = $_POST['_custom_variation_sale_price'][$code][$post_data->ID];
                            $regular_price = $_POST['_custom_variation_regular_price'][$code][$post_data->ID];

                            $date_from = strtotime($_POST['_custom_sale_price_dates_from'][$code][$post_data->ID]);
                            $date_to = strtotime($_POST['_custom_sale_price_dates_to'][$code][$post_data->ID]);
                            $schedule = $_POST['_wcml_schedule'][$code][$post_data->ID];

                            $custom_prices = apply_filters( 'wcml_update_custom_prices_values',
                                array( '_regular_price' => $regular_price,
                                    '_sale_price' => $sale_price,
                                    '_wcml_schedule_' => $schedule,
                                    '_sale_price_dates_from' => $date_from,
                                    '_sale_price_dates_to' => $date_to ),
                                $code,
                                $post_data->ID
                            );

                            $price = $this->update_custom_prices( $post_data->ID, $custom_prices, $code );

                            if(!isset($min_max_prices['_min_variation_price_'.$code]) || ($price && $price < $min_max_prices['_min_variation_price_'.$code])){
                                $min_max_prices['_min_variation_price_'.$code] = $price;
                                $min_max_prices['_min_price_variation_id_'.$code] = $post_data->ID;
                            }

                            if(!isset($min_max_prices['_max_variation_price_'.$code]) || ($price && $price > $min_max_prices['_max_variation_price_'.$code])){
                                $min_max_prices['_max_variation_price_'.$code] = $price;
                                $min_max_prices['_max_price_variation_id_'.$code] = $post_data->ID;
                            }

                            if(!isset($min_max_prices['_min_variation_regular_price_'.$code]) || ($regular_price && $regular_price < $min_max_prices['_min_variation_regular_price_'.$code])){
                                $min_max_prices['_min_variation_regular_price_'.$code] = $regular_price;
                            }

                            if(!isset($min_max_prices['_max_variation_regular_price_'.$code]) || ($regular_price && $regular_price > $min_max_prices['_max_variation_regular_price_'.$code])){
                                $min_max_prices['_max_variation_regular_price_'.$code] = $regular_price;
                            }

                            if(!isset($min_max_prices['_min_variation_sale_price_'.$code]) || ($sale_price && $sale_price < $min_max_prices['_min_variation_sale_price_'.$code])){
                                $min_max_prices['_min_variation_sale_price_'.$code] = $sale_price;
                            }

                            if(!isset($min_max_prices['_max_variation_sale_price_'.$code]) || ($sale_price && $sale_price > $min_max_prices['_max_variation_sale_price_'.$code])){
                                $min_max_prices['_max_variation_sale_price_'.$code] = $sale_price;
                            }
                        }
                    }
                }
            }
        }
    }


    function sync_default_product_attr( $orig_post_id, $transl_post_id, $lang ){
        global $wpdb, $woocommerce_wpml;
        $original_default_attributes = get_post_meta( $orig_post_id, '_default_attributes', true );
        if( !empty( $original_default_attributes ) ){
            $unserialized_default_attributes = array();
            foreach(maybe_unserialize( $original_default_attributes ) as $attribute => $default_term_slug ){
                // get the correct language
                if ( substr( $attribute, 0, 3 ) == 'pa_' ) {
                    //attr is taxonomy
                    if( $woocommerce_wpml->attributes->is_translatable_attribute( $attribute ) ){
                        $default_term_id = $this->wcml_get_term_id_by_slug( $attribute, $default_term_slug );
                        $tr_id = apply_filters( 'translate_object_id', $default_term_id, $attribute, false, $lang );

                        if( $tr_id ){
                            $translated_term = $this->wcml_get_term_by_id( $tr_id, $attribute );
                            $unserialized_default_attributes[$attribute] = $translated_term->slug;
                        }
                    }else{
                        $unserialized_default_attributes[$attribute] = $default_term_slug;
                    }
                }else{
                    //custom attr
                    $orig_product_attributes = get_post_meta( $orig_post_id, '_product_attributes', true );
                    $unserialized_orig_product_attributes = maybe_unserialize( $orig_product_attributes );
                    if( isset( $unserialized_orig_product_attributes[$attribute] ) ){
                        $orig_attr_values = explode( '|', $unserialized_orig_product_attributes[$attribute]['value'] );
                        foreach( $orig_attr_values as $key=>$orig_attr_value ){
                            $orig_attr_value_sanitized = strtolower( sanitize_title ( $orig_attr_value ) );
                            if( $orig_attr_value_sanitized == $default_term_slug || trim( $orig_attr_value ) == trim( $default_term_slug ) ){
                                $tnsl_product_attributes = get_post_meta( $transl_post_id, '_product_attributes', true );
                                $unserialized_tnsl_product_attributes = maybe_unserialize( $tnsl_product_attributes );
                                if( isset( $unserialized_tnsl_product_attributes[$attribute] ) ){
                                    $trnsl_attr_values = explode( '|', $unserialized_tnsl_product_attributes[$attribute]['value'] );

                                    if( $orig_attr_value_sanitized == $default_term_slug ){
                                        $trnsl_attr_value = strtolower( sanitize_title( trim( $trnsl_attr_values[$key] ) ) );
                                    }else{
                                        $trnsl_attr_value = trim( $trnsl_attr_values[$key] );
                                    }

                                    $unserialized_default_attributes[$attribute] = $trnsl_attr_value;
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
        $wpdb->update( $wpdb->postmeta, $data, $where );
    }

    /*
     * get attribute translation
     */
    function get_custom_attribute_translation($product_id, $attribute_key, $attribute, $lang_code) {
        $tr_post_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $lang_code);
        $transl = array();
        if ($tr_post_id) {
            if (!$attribute['is_taxonomy']) {
                $tr_attrs = get_post_meta($tr_post_id, '_product_attributes', true);

                if ($tr_attrs) {
                    foreach ($tr_attrs as $key=>$tr_attr) {
                        if ($attribute_key == $key) {
                            $transl['value'] = $tr_attr['value'];
                            $trnsl_labels = maybe_unserialize( get_post_meta( $tr_post_id, 'attr_label_translations', true ) );
                            if(isset($trnsl_labels[$lang_code][$attribute_key])){
                                $transl['name'] = $trnsl_labels[$lang_code][$attribute_key];
                            }else{
                                $transl['name'] = $tr_attr['name'];
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

    //get product content
    function get_product_custom_fields_to_translate($product_id)
    {
        global $woocommerce_wpml, $sitepress;
        $settings = $sitepress->get_settings();
        $contents = array();

        foreach (get_post_custom_keys($product_id) as $meta_key) {
            if (isset($settings['translation-management']['custom_fields_translation'][$meta_key]) && $settings['translation-management']['custom_fields_translation'][$meta_key] == 2) {
                if ($this->check_custom_field_is_single_value($product_id, $meta_key)) {
                    if (in_array($meta_key, apply_filters('wcml_not_display_single_fields_to_translate', $this->not_display_fields_for_variables_product))) {
                        continue;
                    }
                } else {
                    $exception = apply_filters('wcml_product_content_exception', true, $product_id, $meta_key);
                    if ($exception) {
                        continue;
                    }
                }
                $contents[] = $meta_key;
            }
        }

        return apply_filters('wcml_product_content_fields', $contents, $product_id);
    }

    function check_custom_field_is_single_value($product_id, $meta_key)
    {

        $meta_value = maybe_unserialize(get_post_meta($product_id, $meta_key, true));
        if (is_array($meta_value)) {
            return false;
        } else {
            return apply_filters('wcml_check_is_single', true, $product_id, $meta_key);
        }

    }

    function product_options_downloads_custom_option( $loop = false, $variation_data = false, $variation = false ){
        global $pagenow, $woocommerce_wpml;

        if( ( isset($_GET['post'] ) && ( get_post_type($_GET['post']) != 'product' || !$this->is_original_product( $_GET['post'] ) ) ) ||
            ( isset($_GET['post_type'] ) && $_GET['post_type'] == 'product' && isset( $_GET['source_lang'] ) ) ){
            return;
        }

        $this->load_custom_files_js_css();

        $product_id = false;
        $is_variation = false;

        if( $pagenow == 'post.php' && isset( $_GET['post'] ) ){
            $product_id = $_GET['post'];
        }

        if( $variation ) {
            $product_id = $variation->ID;
            $is_variation = true;
        }

        $download_options = new WCML_Custom_Files_UI( $woocommerce_wpml, $product_id, $is_variation );
        $download_options->show();

    }

    function load_custom_files_js_css(){
        wp_register_style( 'wpml-wcml-files', WCML_PLUGIN_URL . '/res/css/wcml-files.css', null, WCML_VERSION );
        wp_register_script( 'wcml-scripts-files', WCML_PLUGIN_URL . '/res/js/files.js', array( 'jquery' ), WCML_VERSION );

        wp_enqueue_style('wpml-wcml-files');
        wp_enqueue_script('wcml-scripts-files');
    }

    function product_images_ids($product_id)
    {
        global $wpdb;
        $product_images_ids = array();

        //thumbnail image
        $tmb = get_post_meta($product_id, '_thumbnail_id', true);
        if ($tmb) {
            $product_images_ids[] = $tmb;
        }

        //product gallery
        $product_gallery = get_post_meta($product_id, '_product_image_gallery', true);
        if ($product_gallery) {
            $product_gallery = explode(',', $product_gallery);
            foreach ($product_gallery as $img) {
                if (!in_array($img, $product_images_ids)) {
                    $product_images_ids[] = $img;
                }
            }
        }

        foreach (wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type) {
            $product_type = $type;
        }

        if (isset($product_type) && $product_type == 'variable') {
            $get_post_variations_image = $wpdb->get_col($wpdb->prepare("SELECT pm.meta_value FROM $wpdb->posts AS p
                        LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
                        WHERE pm.meta_key='_thumbnail_id' AND p.post_status IN ('publish','private') AND p.post_type = 'product_variation' AND p.post_parent = %d ORDER BY ID", $product_id));
            foreach ($get_post_variations_image as $variation_image) {
                if ($variation_image && !in_array($variation_image, $product_images_ids)) {
                    $product_images_ids[] = $variation_image;
                }
            }
        }

        return $product_images_ids;
    }

    //update taxonomy in variations
    function update_taxonomy_in_variations(){
        global $wpdb;
        $original_element   = filter_input( INPUT_POST, 'translation_of', FILTER_SANITIZE_NUMBER_INT );
        $taxonomy           = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $language           = filter_input( INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $slug               = filter_input( INPUT_POST, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $name               = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $term_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d",$original_element));
        $original_term = $this->wcml_get_term_by_id( $term_id, $taxonomy );
        $original_slug = $original_term->slug;
        //get variations with original slug

        $variations = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value = %s",'attribute_'.$taxonomy,$original_slug));

        foreach($variations as $variation){
            //update taxonomy in translation of variation
            $trnsl_variation_id = apply_filters( 'translate_object_id',$variation->post_id,'product_variation',false,$language);
            if(!is_null($trnsl_variation_id)){
                if(!$slug){
                    $slug = sanitize_title($name);
                }
                update_post_meta($trnsl_variation_id,'attribute_'.$taxonomy,$slug);
            }
        }
    }

    function product_page_add_language_info_to_term($lang){
        if(isset($_POST['action']) && $_POST['action'] == 'woocommerce_add_new_attribute'){
            global $sitepress;
            $lang = $sitepress->get_default_language();
        }
        return $lang;

    }

    function sync_product_gallery_duplicate_attachment($att_id, $dup_att_id){

        $product_id = wp_get_post_parent_id($att_id);
        $post_type = get_post_type($product_id);
        if ($post_type != 'product') {
            return;
        }
        $this->sync_product_gallery($product_id);
    }

    function remove_language_options(){
        global $WPML_media,$typenow;
        if( defined('WPML_MEDIA_VERSION') && $typenow == 'product'){
            remove_action('icl_post_languages_options_after',array( $WPML_media,'language_options'));
            add_action( 'icl_post_languages_options_after', array( $this, 'media_inputs' ) );
        }
    }

    function media_inputs(){
        $wpml_media_options = maybe_unserialize(get_option('_wpml_media'));

        echo '<input name="icl_duplicate_attachments" type="hidden" value="'.$wpml_media_options['new_content_settings']['duplicate_media'].'" />';
        echo '<input name="icl_duplicate_featured_image" type="hidden" value="'.$wpml_media_options['new_content_settings']['duplicate_featured'].'" />';
    }

    function icl_pro_translation_completed($tr_product_id) {
        global $sitepress;

        $trid = $sitepress->get_element_trid($tr_product_id,'post_product');
        $translations = $sitepress->get_element_translations($trid,'post_product');

        foreach($translations as $translation){
            if($translation->original){
                $original_product_id = $translation->element_id;
            }
        }

        if(!isset($original_product_id)){
            return;
        }

        $lang = $sitepress->get_language_for_element($tr_product_id,'post_product');

        $this->sync_product_data($original_product_id, $tr_product_id, $lang);
    }





    function remove_variation_ajax(){
        global $sitepress;
        if(isset($_POST['variation_id'])){
            $trid = $sitepress->get_element_trid( filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ), 'post_product_variation');
            if ($trid) {
                $translations = $sitepress->get_element_translations($trid, 'post_product_variation');
                if($translations){
                    foreach($translations as $translation){
                        if(!$translation->original){
                            wp_delete_post($translation->element_id,true);
                        }
                    }
                }
            }
        }
    }

    function icl_make_duplicate($master_post_id, $lang, $postarr, $id){
        if( get_post_type( $master_post_id ) == 'product' ){
            $this->sync_product_data($master_post_id, $id, $lang);

            // recount terms only first time
            if( !get_post_meta( $id, '_wcml_terms_recount' ) ){
                $product_cats = wp_get_post_terms( $id, 'product_cat' );

                if(!empty($product_cats)) {

                    foreach ($product_cats as $product_cat) {
                        $cats_to_recount[$product_cat->term_id] = $product_cat->parent;
                    }
                    _wc_term_recount($cats_to_recount, get_taxonomy('product_cat'), true, false);
                    add_post_meta($id, '_wcml_terms_recount', 'yes');

                }
            }

        }
    }

    function woocommerce_json_search_found_products($found_products) {
        global $sitepress;

        $new_found_products = array();
        foreach($found_products as $post => $formatted_product_name) {
            $parent = wp_get_post_parent_id($post);

            if( ( isset( $_COOKIE [ '_wcml_dashboard_order_language' ] )
                    && ( ( !$parent && $sitepress->get_language_for_element( $post, 'post_product') == $_COOKIE [ '_wcml_dashboard_order_language' ] )
                        || ( $parent && $sitepress->get_language_for_element( $parent, 'post_product') == $_COOKIE [ '_wcml_dashboard_order_language' ] ) )
                )
                ||
                ( ! isset( $_COOKIE [ '_wcml_dashboard_order_language' ] )
                    && ( ( !$parent && $this->is_original_product($post) )
                        || ( $parent && $this->is_original_product($parent) ) )
                )
            ) {

                $new_found_products[$post] = $formatted_product_name;

            }
        }

        return $new_found_products;
    }


    function woocommerce_email_refresh_text_domain(){
        if( !isset($_GET['page']) || ( isset($_GET['page']) && $_GET['page'] != 'wc-settings' ) ){
            $this->wcml_refresh_text_domain();
        }
    }

    function wcml_refresh_text_domain(){
        global $woocommerce;
        $domain = 'woocommerce';
        unload_textdomain($domain);
        $woocommerce->load_plugin_textdomain();
    }


    function _mce_set_plugins($settings) {
        if (is_rtl()) {
            if(!empty($settings['plugins'])){
                $this->tinymce_plugins_for_rtl = $settings['plugins'];
            }else{
                $settings['plugins'] = $this->tinymce_plugins_for_rtl;
            }
        }
        return $settings;
    }



    /*
    * Get custom attribute translation
    * Returned translated attribute or original if missed
    */
    function get_custom_attr_translation( $product_id, $tr_product_id, $taxonomy, $attribute ){

        $orig_product_attributes = get_post_meta($product_id, '_product_attributes', true);
        $unserialized_orig_product_attributes = maybe_unserialize($orig_product_attributes);
        foreach($unserialized_orig_product_attributes as $orig_attr_key => $orig_product_attribute){
            $orig_attr_key = urldecode($orig_attr_key);
            if( strtolower($taxonomy) == $orig_attr_key){
                $values = explode('|',$orig_product_attribute['value']);
                foreach($values as $key_id => $value){
                    if(trim($value," ") == $attribute){
                        $attr_key_id = $key_id;
                    }
                }
            }
        }

        $trnsl_product_attributes = get_post_meta($tr_product_id, '_product_attributes', true);
        $unserialized_trnsl_product_attributes = maybe_unserialize($trnsl_product_attributes);
        $taxonomy = sanitize_title($taxonomy);
        $trnsl_attr_values = explode('|',$unserialized_trnsl_product_attributes[$taxonomy]['value']);

        if(isset($attr_key_id) && isset($trnsl_attr_values[$attr_key_id])){
            return trim($trnsl_attr_values[$attr_key_id]);
        }

        return $attribute;
    }

    function wcml_coupon_loaded($coupons_data){
        global $sitepress;

        $product_ids  = array();
        $exclude_product_ids  = array();
        $product_categories_ids  = array();
        $exclude_product_categories_ids  = array();

        foreach($coupons_data->product_ids as $prod_id){
            $post_type = get_post_field('post_type', $prod_id);
            $trid = $sitepress->get_element_trid($prod_id,'post_' . $post_type);
            $translations = $sitepress->get_element_translations($trid,'post_' . $post_type);
            foreach($translations as $translation){
                $product_ids[] = $translation->element_id;
            }
        }
        foreach($coupons_data->exclude_product_ids as $prod_id){
            $post_type = get_post_field('post_type', $prod_id);
            $trid = $sitepress->get_element_trid($prod_id,'post_' . $post_type);
            $translations = $sitepress->get_element_translations($trid,'post_' . $post_type);
            foreach($translations as $translation){
                $exclude_product_ids[] = $translation->element_id;
            }
        }

        foreach($coupons_data->product_categories as $cat_id){
            $term = $this->wcml_get_term_by_id( $cat_id,'product_cat' );
            $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_product_cat');
            $translations = $sitepress->get_element_translations($trid,'tax_product_cat');

            foreach($translations as $translation){
                $product_categories_ids[] = $translation->term_id;
            }
        }

        foreach($coupons_data->exclude_product_categories as $cat_id){
            $term = $this->wcml_get_term_by_id( $cat_id,'product_cat' );
            $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_product_cat');
            $translations = $sitepress->get_element_translations($trid,'tax_product_cat');
            foreach($translations as $translation){
                $exclude_product_categories_ids[] = $translation->term_id;
            }
        }

        $coupons_data->product_ids = $product_ids;
        $coupons_data->exclude_product_ids = $exclude_product_ids;
        $coupons_data->product_categories = $product_categories_ids;
        $coupons_data->exclude_product_categories = $exclude_product_categories_ids;

        return $coupons_data;
    }


    function set_taxonomies_config( $config_all ) {
        global $woocommerce_wpml;

        $all_products_taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' );

        foreach($all_products_taxonomies as $tax_key => $tax) {
            if( $tax_key == 'product_type' ) continue;

            $found = false;
            $att_sett = $woocommerce_wpml->attributes->is_translatable_attribute( $tax_key );

            foreach( $config_all['wpml-config']['taxonomies']['taxonomy'] as $key => $taxonomy ){

                if( $tax_key == $taxonomy['value'] ){
                    $config_all['wpml-config']['taxonomies']['taxonomy'][$key]['attr']['translate'] = $att_sett;
                    $found = true;
                }

            }

            if( !$found ){
                $config_all['wpml-config']['taxonomies']['taxonomy'][] = array( 'value' => $tax_key, 'attr' => array( 'translate' => $att_sett ) );
            }

        }

        return $config_all;
    }


    function set_prices_config(){
        global $sitepress, $iclTranslationManagement, $sitepress_settings, $woocommerce_wpml;

        $wpml_settings = $sitepress->get_settings();

        if (!isset($wpml_settings['translation-management']) || !isset($iclTranslationManagement) || !( $iclTranslationManagement instanceof TranslationManagement) ) {
            return;
        }

        $keys = array(
            '_regular_price',
            '_sale_price',
            '_price',
            '_min_variation_regular_price',
            '_min_variation_sale_price',
            '_min_variation_price',
            '_max_variation_regular_price',
            '_max_variation_sale_price',
            '_max_variation_price',
            '_sale_price_dates_from',
            '_sale_price_dates_to',
            '_wcml_schedule'
        );

        $save = false;

        foreach ($keys as $key) {
            $iclTranslationManagement->settings['custom_fields_readonly_config'][] = $key;
            if (!isset($sitepress_settings['translation-management']['custom_fields_translation'][$key]) ||
                $wpml_settings['translation-management']['custom_fields_translation'][$key] != 1) {
                $wpml_settings['translation-management']['custom_fields_translation'][$key] = 1;
                $save = true;
            }

            if(!empty($woocommerce_wpml->multi_currency_support)){
                foreach($woocommerce_wpml->multi_currency_support->get_currency_codes() as $code){
                    $new_key = $key.'_'.$code;
                    $iclTranslationManagement->settings['custom_fields_readonly_config'][] = $new_key;
                    if (!isset($sitepress_settings['translation-management']['custom_fields_translation'][$new_key]) ||
                        $wpml_settings['translation-management']['custom_fields_translation'][$new_key] != 0) {
                        $wpml_settings['translation-management']['custom_fields_translation'][$new_key] = 0;
                        $save = true;
                    }
                }
            }

        }

        if ($save) {
            $sitepress->save_settings($wpml_settings);
        }
    }

    function woocommerce_duplicate_product($new_id, $post){
        global $sitepress,$wpdb;

        //duplicate original first
        $trid = $sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
        $orig_id = $sitepress->get_original_element_id_by_trid( $trid );
        $orig_lang = $this->get_original_product_language( $post->ID );

        $wc_admin = new WC_Admin_Duplicate_Product();

        if( $orig_id == $post->ID ){
            $sitepress->set_element_language_details($new_id, 'post_' . $post->post_type, false, $orig_lang);
            $new_trid = $sitepress->get_element_trid( $new_id, 'post_' . $post->post_type );

            $new_orig_id = $new_id;
        }else{
            $post_to_duplicate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $orig_id ));

            if ( ! empty( $post_to_duplicate ) ) {
                $new_orig_id = $wc_admin->duplicate_product( $post_to_duplicate );

                do_action( 'wcml_after_duplicate_product' , $new_id, $post_to_duplicate );

                $sitepress->set_element_language_details( $new_orig_id, 'post_' . $post->post_type, false, $orig_lang );
                $new_trid = $sitepress->get_element_trid( $new_orig_id, 'post_' . $post->post_type );
                if( get_post_meta( $orig_id, '_icl_lang_duplicate_of' ) ){
                    update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
                }


                $sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, $new_trid, $sitepress->get_current_language() );
            }
        }

        $translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type );


        if($translations){

            foreach($translations as $translation){
                if( !$translation->original && $translation->element_id != $post->ID ){
                    $post_to_duplicate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $translation->element_id ) );

                    if ( ! empty( $post_to_duplicate ) ) {
                        $new_id = $wc_admin->duplicate_product( $post_to_duplicate );

                        $new_id_obj = get_post( $new_id );
                        $new_slug = wp_unique_post_slug( sanitize_title( $new_id_obj->post_title ), $new_id, $post_to_duplicate->post_status, $post_to_duplicate->post_type, $new_id_obj->post_parent );
                        $wpdb->update( $wpdb->posts, array( 'post_name' => $new_slug, 'post_status' => $post_to_duplicate->post_status ), array( 'ID' => $new_id ) );

                        do_action( 'wcml_after_duplicate_product' , $new_id, $post_to_duplicate );

                        $sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, $new_trid, $translation->language_code );

                        if( get_post_meta( $translation->element_id, '_icl_lang_duplicate_of' ) ){
                            update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
                        }

                    }
                }
            }

        }

    }

    function woocommerce_product_quick_edit_save($product){
        global $sitepress;
        $is_original = $this->is_original_product($product->id);

        $trid = $sitepress->get_element_trid($product->id, 'post_product');
        if ($trid) {
            $translations = $sitepress->get_element_translations($trid, 'post_product');
            if($translations){
                foreach($translations as $translation){
                    if($is_original){
                        if(!$translation->original){
                            $this->sync_product_data($product->id,$translation->element_id,$translation->language_code);
                        }
                    }elseif($translation->original){
                        $this->sync_product_data($translation->element_id,$product->id,$sitepress->get_language_for_element($product->id,'post_product'));
                    }
                }
            }
        }

    }

    function override_cached_widget_id($widget_id){

        if (defined('ICL_LANGUAGE_CODE')){
            $widget_id .= ':' . ICL_LANGUAGE_CODE;
        }

        return $widget_id;
    }


    //update menu_order fro translations after ordering original products
    function update_all_products_translations_ordering(){
        global $wpdb, $sitepress, $woocommerce_wpml;

        if( $woocommerce_wpml->settings['products_sync_order'] ) {

            $current_language = $sitepress->get_current_language();

            if ($current_language == $sitepress->get_default_language()) {
                $products = $wpdb->get_results($wpdb->prepare("SELECT p.ID FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = p.id WHERE p.post_type = 'product' AND p.post_status IN ( 'publish', 'future', 'draft', 'pending', 'private' ) AND icl.element_type= 'post_product' AND icl.language_code = %s", $current_language));

                foreach ($products as $product) {
                    $this->update_order_for_product_translations( $product->ID );
                }
            }
        }
    }

    //update menu_order fro translations after ordering original products
    function update_order_for_product_translations( $product_id ){
        global $wpdb, $sitepress, $woocommerce_wpml;

        if( isset($woocommerce_wpml->settings['products_sync_order']) && $woocommerce_wpml->settings['products_sync_order'] ) {

            $current_language = $sitepress->get_current_language();

            if ( $current_language == $sitepress->get_default_language() ) {
                $menu_order = $wpdb->get_var($wpdb->prepare("SELECT menu_order FROM $wpdb->posts WHERE ID = %d", $product_id ) );

                $trid = $sitepress->get_element_trid($product_id, 'post_product');
                $translations = $sitepress->get_element_translations($trid, 'post_product');

                foreach ($translations as $translation) {

                    if ($translation->element_id != $product_id) {
                        $wpdb->update( $wpdb->posts, array('menu_order' => $menu_order), array('ID' => $translation->element_id));
                    }
                }
            }

        }
    }


    function filter_excerpt_field_content_copy( $elements ) {

        if ( $elements[ 'post_type' ] == 'product' ) {
            $elements[ 'excerpt' ] ['editor_type'] = 'editor';
        }

        if ( function_exists( 'format_for_editor' ) ) {
            // WordPress 4.3 uses format_for_editor
            $elements[ 'excerpt' ] ['value'] = htmlspecialchars_decode(format_for_editor($elements[ 'excerpt' ] ['value'], $_POST[ 'excerpt_type']));
        } else {
            // Backwards compatible for WordPress < 4.3
            if($_POST[ 'excerpt_type'] == 'rich'){
                $elements[ 'excerpt' ] ['value'] = htmlspecialchars_decode(wp_richedit_pre($elements[ 'excerpt' ] ['value']));
            }else{
                $elements[ 'excerpt' ] ['value'] = htmlspecialchars_decode(wp_htmledit_pre($elements[ 'excerpt' ] ['value']));
            }
        }
        
		return $elements;
	}

    // Check if user can translate product
    function user_can_translate_product( $trid, $language_code ){
        global $wpdb, $iclTranslationManagement,$sitepress;

        $current_translator = $iclTranslationManagement->get_current_translator();
        $job_id = $wpdb->get_var($wpdb->prepare("
			SELECT tj.job_id FROM {$wpdb->prefix}icl_translate_job tj
				JOIN {$wpdb->prefix}icl_translation_status ts ON tj.rid = ts.rid
				JOIN {$wpdb->prefix}icl_translations t ON ts.translation_id = t.translation_id
				WHERE t.trid = %d AND t.language_code='%s'
				ORDER BY tj.job_id DESC LIMIT 1
		", $trid, $language_code));

        if( $job_id && wpml_check_user_is_translator( $sitepress->get_source_language_by_trid($trid), $language_code)){
            return true;
        }

        return false;
    }


    // Check if original product
    function is_original_product( $product_id ){

        $cache_key =  $product_id;
        $cache_group = 'is_original_product';

        $temp_is_original = wp_cache_get($cache_key, $cache_group);
        if($temp_is_original) return $temp_is_original;


        global $wpdb;

        $is_original = $wpdb->get_var( $wpdb->prepare( "SELECT source_language_code IS NULL FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type='post_product'", $product_id ) );

        wp_cache_set( $cache_key, $is_original, $cache_group );

        return $is_original;
    }

    // Get original product language
    function get_original_product_language( $product_id ){

        $cache_key = $product_id;
        $cache_group = 'original_product_language';

        $temp_language = wp_cache_get( $cache_key, $cache_group );
        if($temp_language) return $temp_language;

        global $wpdb;

        $language = $wpdb->get_var( $wpdb->prepare( "
                            SELECT t2.language_code FROM {$wpdb->prefix}icl_translations as t1
                            LEFT JOIN {$wpdb->prefix}icl_translations as t2 ON t1.trid = t2.trid
                            WHERE t1.element_id=%d AND t1.element_type=%s AND t2.source_language_code IS NULL", $product_id, 'post_'.get_post_type($product_id) ) );

        wp_cache_set( $cache_key, $language, $cache_group );

        return $language;
    }

    function add_languages_column( $columns ){

        if ( ( version_compare(ICL_SITEPRESS_VERSION, '3.2', '<') && version_compare( WOOCOMMERCE_VERSION, '2.3', '<' ) ) || array_key_exists( 'icl_translations',$columns ) ){
            return $columns;
        }

        global $sitepress,$wpdb;

        $active_languages = $sitepress->get_active_languages();
        if ( count( $active_languages ) <= 1 || get_query_var( 'post_status' ) == 'trash') {
            return $columns;
        }

        $languages = array();
        foreach ( $active_languages as $v ) {
            if ( $v[ 'code' ] == $sitepress->get_current_language() )
                continue;
            $languages[ ] = $v[ 'code' ];
        }

        $res = $wpdb->get_results( $wpdb->prepare ( "
			SELECT f.lang_code, f.flag, f.from_template, l.name
			FROM {$wpdb->prefix}icl_flags f
				JOIN {$wpdb->prefix}icl_languages_translations l ON f.lang_code = l.language_code
			WHERE l.display_language_code = %s AND f.lang_code IN (%s)
		", $sitepress->get_admin_language(), join( "','", $languages ) ) );

        foreach ( $res as $r ) {
            if ( $r->from_template ) {
                $wp_upload_dir = wp_upload_dir();
                $flag_path         = $wp_upload_dir[ 'baseurl' ] . '/flags/';
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

        if(!$added){
            $new_columns[ 'icl_translations' ] = $flags_column;
        }

        return $new_columns;

    }

    // display products with custom prices only if enabled "Show only products with custom prices in secondary currencies" option on settings page
    function filter_products_with_custom_prices( $filtered_posts ) {
        global $wpdb,$woocommerce_wpml;

        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset($woocommerce_wpml->settings['display_custom_prices']) &&
            $woocommerce_wpml->settings['display_custom_prices'] ){

            $client_currency = $woocommerce_wpml->multi_currency_support->get_client_currency();
            $woocommerce_currency = get_option('woocommerce_currency');

            if( $client_currency == $woocommerce_currency ){
                return $filtered_posts;
            }

            $matched_products = array();

            $matched_products_query = $wpdb->get_results( "
	        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
				INNER JOIN $wpdb->postmeta ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = '_wcml_custom_prices_status' AND meta_value = 1
			", OBJECT_K );

            if ( $matched_products_query ) {

                remove_filter('get_post_metadata', array($woocommerce_wpml->multi_currency_support, 'product_price_filter'), 10, 4);

                foreach ( $matched_products_query as $product ) {
                    if( !get_post_meta( $product->ID,'_price_'.$client_currency, true ) ) continue;
                    if ( $product->post_type == 'product' )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->ID, 'product', true );
                    if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->post_parent, get_post_type($product->post_parent), true );
                }

                add_filter('get_post_metadata', array($woocommerce_wpml->multi_currency_support, 'product_price_filter'), 10, 4);
            }

            // Filter the id's
            if ( sizeof( $filtered_posts ) == 0) {
                $filtered_posts = $matched_products;
                $filtered_posts[] = 0;
            } else {
                $filtered_posts = array_intersect( $filtered_posts, $matched_products );
                $filtered_posts[] = 0;
            }
        }

        return $filtered_posts;
    }

    function filter_related_products_args( $args ){
        global $woocommerce_wpml;

        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset($woocommerce_wpml->settings['display_custom_prices']) &&
            $woocommerce_wpml->settings['display_custom_prices'] ){

            $client_currency = $woocommerce_wpml->multi_currency_support->get_client_currency();
            $woocommerce_currency = get_option('woocommerce_currency');

            if( $client_currency != $woocommerce_currency ){
                $args['meta_query'][] =  array(
                    'key'     => '_wcml_custom_prices_status',
                    'value'   => 1,
                    'compare' => '=',
                );
            }

        }

        return $args;
    }

    function filter_product_attributes_for_translation( $translated, $key ){

        $translated = $translated
            ? preg_match('#^(?!field-_product_attributes-(.+)-(.+)-(?!value|name))#', $key) : 0;

        return $translated;

    }

    /*
     * Use custom query, because get_term_by function return false for terms with "0" slug      *
     */
    function wcml_get_term_id_by_slug( $taxonomy, $slug ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.slug = %s LIMIT 1", $taxonomy, $slug ) );
    }

    function wcml_get_term_by_id( $term_id, $taxonomy ){
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("
                            SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $term_id, $taxonomy ) );
    }

    function lock_variable_fields( $loop, $variation_data, $variation ){

        $product_id = false;
        if( ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'product' ) ){
            $product_id = $_GET['post'];
        }elseif( isset( $_POST['action'] ) && $_POST['action'] == 'woocommerce_load_variations' && isset( $_POST['product_id'] ) ){
            $product_id = $_POST['product_id'];
        }

        if( !$product_id ){
            return;
        }elseif( !$this->is_original_product( $product_id ) ){ ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    wcml_lock_variation_fields();
                });
            </script>
            <?php
        }

    }

    function wcml_get_translated_variation($variation_id, $language)
    {

        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT tt.element_id FROM {$wpdb->prefix}icl_translations AS t INNER JOIN {$wpdb->prefix}icl_translations AS tt ON t.trid = tt.trid WHERE t.element_type = 'post_product_variation' AND t.element_id = %s AND tt.language_code = %s", $variation_id, $language));

    }

    function sync_feature_product_meta(){

        if ( current_user_can( 'edit_products' ) && check_admin_referer( 'woocommerce-feature-product' ) ) {
            $product_id = absint( $_GET['product_id'] );

            if ( 'product' === get_post_type( $product_id ) && $this->is_original_product( $product_id ) ) {
                global $sitepress;

                $value = get_post_meta( $product_id, '_featured', true ) === 'yes' ? 'no' : 'yes';

                $trid = $sitepress->get_element_trid( $product_id, 'post_product' );
                $translations = $sitepress->get_element_translations( $trid, 'post_product', true );
                foreach( $translations as $translation ){

                    if ( !$translation->original ) {

                        update_post_meta( $translation->element_id, '_featured', $value );
                    }
                }

            }
        }

    }

    function set_schedule_for_translations( $deprecated, $post ){
        global $sitepress;

        if( $this->is_original_product( $post->ID ) ) {

            $trid = $sitepress->get_element_trid( $post->ID, 'post_product');
            $translations = $sitepress->get_element_translations( $trid, 'post_product', true);
            foreach ($translations as $translation) {

                if (!$translation->original) {
                    wp_clear_scheduled_hook('publish_future_post', array($translation->element_id));
                    wp_schedule_single_event(strtotime(get_gmt_from_date($post->post_date) . ' GMT'), 'publish_future_post', array($translation->element_id));
                }
            }
        }
    }

    function sync_stock_status_for_translations( $id, $status ){
        global $sitepress;

        $type = get_post_type( $id );
        $trid = $sitepress->get_element_trid( $id, 'post_'.$type );
        $translations = $sitepress->get_element_translations( $trid, 'post_'.$type, true);
        foreach ($translations as $translation) {
            if ( $translation->element_id != $id ) {
                update_post_meta( $translation->element_id, '_stock_status', $status );
            }
        }

    }

    public function get_files_data( $product_id ){

        $files = maybe_unserialize( get_post_meta( $product_id, '_downloadable_files', true ) );

        if ( $files ) {
            foreach ($files as $file) {
                $files_info = array();
                $files_info['value'] = $file['file'];
                $files_info['label'] = $file['name'];
                $files_data[] = $files_info;
            }
        } else {
            $files_data = array( );
        }

        return $files_data;

    }

    public function sync_files_to_translations( $original_id, $trnsl_id, $data ){
        global $woocommerce_wpml;

        $custom_product_sync = get_post_meta( $original_id, 'wcml_sync_files', true );
        if( ( $custom_product_sync && $custom_product_sync == 'self' ) || !$woocommerce_wpml->settings[ 'file_path_sync' ] ){
            if( $data ){
                $orig_var_files = $this->get_files_data( $original_id );
                $file_paths_array = array();
                foreach( $orig_var_files as $key => $var_file ){
                    $key_file = md5( $data[ md5( 'file-url'.$key.$original_id ) ].$data[ md5( 'file-name'.$key.$original_id ) ] );
                    $file_paths_array[ $key_file ][ 'name' ] = $data[ md5( 'file-name'.$key.$original_id ) ];
                    $file_paths_array[ $key_file ][ 'file' ] = $data[ md5( 'file-url'.$key.$original_id ) ];
                }
                update_post_meta( $trnsl_id,'_downloadable_files',$file_paths_array );
            }
        }elseif( $woocommerce_wpml->settings[ 'file_path_sync' ] ){
            $orig_file_path = maybe_unserialize( get_post_meta( $original_id, '_downloadable_files', true ) );
            update_post_meta( $trnsl_id, '_downloadable_files', $orig_file_path );
        }

    }

}