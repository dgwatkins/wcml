<?php

class WCML_Products{

    public $tp_support;

    private $woocommerce_wpml;
    private $sitepress;
    private $wpdb;


    public function __construct( &$woocommerce_wpml, &$sitepress, &$wpdb  )
    {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;
        $this->wpdb = $wpdb;

        if( is_admin() ){

            add_filter( 'woocommerce_json_search_found_products', array( $this, 'woocommerce_json_search_found_products' ) );

            add_filter( 'post_row_actions', array( $this, 'filter_product_actions' ), 10, 2 );

            if( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ){
                $this->tp_support = new WCML_TP_Support();
            }

        }else{
            add_filter( 'woocommerce_json_search_found_products', array( $this, 'filter_found_products_by_language' ) );
            add_filter( 'loop_shop_post_in', array( $this, 'filter_products_with_custom_prices' ), 100 );
            add_filter( 'woocommerce_related_products_args', array( $this, 'filter_related_products_args' ) );
        }

        add_filter( 'woocommerce_upsell_crosssell_search_products', array( $this, 'filter_woocommerce_upsell_crosssell_posts_by_language' ) );
        //update menu_order fro translations after ordering original products
        add_action( 'woocommerce_after_product_ordering', array( $this, 'update_all_products_translations_ordering' ) );
        //filter to copy excerpt value
        add_filter( 'wpml_copy_from_original_custom_fields', array( $this, 'filter_excerpt_field_content_copy' ) );

        $this->set_prices_config();
    }

    // Check if original product
    public function is_original_product( $product_id ){
        $cache_key =  $product_id;
        $cache_group = 'is_original_product';
        $temp_is_original = wp_cache_get($cache_key, $cache_group);

        if($temp_is_original) return $temp_is_original;

        $is_original = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT source_language_code IS NULL
                                FROM {$this->wpdb->prefix}icl_translations
                                WHERE element_id=%d AND element_type='post_product'",
                $product_id )
        );

        wp_cache_set( $cache_key, $is_original, $cache_group );

        return $is_original;
    }

    // Get original product language
    public function get_original_product_language( $product_id ){
        $cache_key = $product_id;
        $cache_group = 'original_product_language';
        $temp_language = wp_cache_get( $cache_key, $cache_group );
        if($temp_language) return $temp_language;

        $language = $this->wpdb->get_var( $this->wpdb->prepare( "
                            SELECT t2.language_code FROM {$this->wpdb->prefix}icl_translations as t1
                            LEFT JOIN {$this->wpdb->prefix}icl_translations as t2 ON t1.trid = t2.trid
                            WHERE t1.element_id=%d AND t1.element_type=%s AND t2.source_language_code IS NULL", $product_id, 'post_'.get_post_type($product_id) ) );

        wp_cache_set( $cache_key, $language, $cache_group );
        return $language;
    }

    public function is_variable_product( $product_id ){
        $get_variation_term_taxonomy_ids = $this->wpdb->get_col( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable' AND tt.taxonomy = 'product_type'" );
        $get_variation_term_taxonomy_ids = apply_filters( 'wcml_variation_term_taxonomy_ids',(array)$get_variation_term_taxonomy_ids );

        $is_variable_product = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count(object_id) FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id IN (".join(',',$get_variation_term_taxonomy_ids).")",$product_id ) );
        return $is_variable_product;
    }

    public function is_grouped_product($product_id){
        $get_variation_term_taxonomy_id = $this->wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.name = 'grouped'" );
        $is_grouped_product = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count(object_id) FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id = %d ",$product_id,$get_variation_term_taxonomy_id ) );

        return $is_grouped_product;
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
                        $job_id = $this->wpdb->get_var(
                                    $this->wpdb->prepare(
                                        "SELECT tj.job_id FROM {$this->wpdb->prefix}icl_translate_job AS tj
                                         LEFT JOIN {$this->wpdb->prefix}icl_translation_status AS ts
                                         ON tj.rid = ts.rid WHERE ts.translation_id=%d",
                                        $product_translations[ $language[ 'code' ] ]->translation_id )
                                    );
                    }else{
                        $job_id = false;
                    }

                    if( !current_user_can( 'wpml_manage_woocommerce_multilingual' ) && isset( $product_translations[ $language[ 'code' ] ] ) ) {
                        $tr_status = $this->wpdb->get_row(
                                        $this->wpdb->prepare(
                                            "SELECT status,translator_id FROM {$this->wpdb->prefix}icl_translation_status
                                            WHERE translation_id = %d",
                                            $product_translations[ $language[ 'code' ] ]->translation_id )
                                        );

                        if( !is_null( $tr_status ) && get_current_user_id() != $tr_status->translator_id ){
                            if( $tr_status->status == ICL_TM_IN_PROGRESS ) { ?>
                                <a title="<?php _e( 'Translation in progress', 'woocommerce-multilingual' ); ?>"><i
                                        class="otgs-ico-in-progress"></i></a>
                                <?php continue;
                            }elseif( $tr_status->status == ICL_TM_WAITING_FOR_TRANSLATOR && !$job_id ){
                                $tr_job_id = $this->wpdb->get_var(
                                                $this->wpdb->prepare(
                                                    "SELECT j.job_id FROM {$this->wpdb->prefix}icl_translate_job j
                                                    JOIN {$this->wpdb->prefix}icl_translation_status s ON j.rid = s.rid
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
                            $tr_status = $this->wpdb->get_row(
                                            $this->wpdb->prepare(
                                                "SELECT status,needs_update FROM {$this->wpdb->prefix}icl_translation_status
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
    public function filter_found_products_by_language( $found_products ){
        $current_page_language = $this->sitepress->get_current_language();

        foreach( $found_products as $product_id => $output_v ){
            $post_data = $this->wpdb->get_row(
                            $this->wpdb->prepare(
                                "SELECT * FROM {$this->wpdb->prefix}icl_translations
                                WHERE element_id = %d AND element_type LIKE 'post_%'", $product_id
                            )
                        );

            $product_language = $post_data->language_code;

            if( $product_language !== $current_page_language ){
                unset( $found_products[ $product_id ] );
            }
        }

        return $found_products;
    }

    /**
     * Takes off translated products from the Up-sells/Cross-sells tab.
     */
    public function filter_woocommerce_upsell_crosssell_posts_by_language( $posts ){
        foreach( $posts as $key => $post ){
            $post_id = $posts[ $key ]->ID;
            $post_data = $this->wpdb->get_row(
                            $this->wpdb->prepare(
                                "SELECT * FROM {$this->wpdb->prefix}icl_translations
                                WHERE element_id = %d ", $post_id
                            ),
                        ARRAY_A );

            if( $post_data[ 'language_code' ] !== $this->sitepress->get_current_language() ){
                unset( $posts[ $key ] );
            }
        }

        return $posts;
    }

    public function woocommerce_json_search_found_products( $found_products ){
        $new_found_products = array();
        foreach( $found_products as $post => $formatted_product_name ) {
            $parent = wp_get_post_parent_id( $post );
            if( ( isset( $_COOKIE [ '_wcml_dashboard_order_language' ] )
                    && ( ( !$parent && $this->sitepress->get_language_for_element( $post, 'post_product') == $_COOKIE [ '_wcml_dashboard_order_language' ] )
                        || ( $parent && $this->sitepress->get_language_for_element( $parent, 'post_product') == $_COOKIE [ '_wcml_dashboard_order_language' ] ) )
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

    //update menu_order fro translations after ordering original products
    public function update_all_products_translations_ordering(){
        if( $this->woocommerce_wpml->settings[ 'products_sync_order' ] ) {
            $current_language = $this->sitepress->get_current_language();
            if( $current_language == $this->sitepress->get_default_language() ){
                $products = $this->wpdb->get_results(
                                $this->wpdb->prepare(
                                    "SELECT p.ID FROM {$this->wpdb->posts} AS p
                                    LEFT JOIN {$this->wpdb->prefix}icl_translations AS icl
                                    ON icl.element_id = p.id
                                    WHERE p.post_type = 'product'
                                      AND p.post_status IN ( 'publish', 'future', 'draft', 'pending', 'private' )
                                      AND icl.element_type= 'post_product'
                                      AND icl.language_code = %s",
                                $current_language )
                            );

                foreach( $products as $product ){
                    $this->update_order_for_product_translations( $product->ID );
                }
            }
        }
    }

    //update menu_order fro translations after ordering original products
    public function update_order_for_product_translations( $product_id ){
        if( isset( $this->woocommerce_wpml->settings[ 'products_sync_order' ] ) && $this->woocommerce_wpml->settings[ 'products_sync_order' ] ){
            $current_language = $this->sitepress->get_current_language();

            if ( $current_language == $this->sitepress->get_default_language() ) {
                $menu_order = $this->wpdb->get_var( $this->wpdb->prepare("SELECT {$this->wpdb->posts} FROM %s WHERE ID = %d", $product_id ) );
                $trid = $this->sitepress->get_element_trid($product_id, 'post_product');
                $translations = $this->sitepress->get_element_translations($trid, 'post_product');

                foreach( $translations as $translation ){
                    if( $translation->element_id != $product_id ){
                        $this->wpdb->update( $this->wpdb->posts, array( 'menu_order' => $menu_order ), array( 'ID' => $translation->element_id ) );
                    }
                }
            }
        }
    }

    public function filter_excerpt_field_content_copy( $elements ) {

        if ( $elements[ 'post_type' ] == 'product' ) {
            $elements[ 'excerpt' ] ['editor_type'] = 'editor';
        }
        if ( function_exists( 'format_for_editor' ) ) {
            // WordPress 4.3 uses format_for_editor
            $elements[ 'excerpt' ][ 'value' ] = htmlspecialchars_decode( format_for_editor( $elements[ 'excerpt' ][ 'value' ], $_POST[ 'excerpt_type' ] ) );
        } else {
            // Backwards compatible for WordPress < 4.3
            if($_POST[ 'excerpt_type'] == 'rich'){
                $elements[ 'excerpt' ][ 'value' ] = htmlspecialchars_decode( wp_richedit_pre( $elements[ 'excerpt' ][ 'value' ] ) );
            }else{
                $elements[ 'excerpt' ][ 'value' ] = htmlspecialchars_decode( wp_htmledit_pre( $elements[ 'excerpt' ][ 'value' ] ) );
            }
        }
		return $elements;
	}

    // Check if user can translate product
    public function user_can_translate_product( $trid, $language_code ){
        global $iclTranslationManagement;

        $current_translator = $iclTranslationManagement->get_current_translator();
        $job_id = $this->wpdb->get_var( $this->wpdb->prepare("
			SELECT tj.job_id FROM {$this->wpdb->prefix}icl_translate_job tj
				JOIN {$this->wpdb->prefix}icl_translation_status ts ON tj.rid = ts.rid
				JOIN {$this->wpdb->prefix}icl_translations t ON ts.translation_id = t.translation_id
				WHERE t.trid = %d AND t.language_code='%s'
				ORDER BY tj.job_id DESC LIMIT 1
		", $trid, $language_code ) );

        if( $job_id && wpml_check_user_is_translator( $this->sitepress->get_source_language_by_trid( $trid ), $language_code ) ){
            return true;
        }
        return false;
    }

    public function filter_related_products_args( $args ){
        if( $this->woocommerce_wpml->settings[ 'enable_multi_currency' ] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset( $this->woocommerce_wpml->settings[ 'display_custom_prices' ] ) &&
            $this->woocommerce_wpml->settings[ 'display_custom_prices' ] )
        {

            $client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
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

    // display products with custom prices only if enabled "Show only products with custom prices in secondary currencies" option on settings page
    public function filter_products_with_custom_prices( $filtered_posts ) {

        if( $this->woocommerce_wpml->settings[ 'enable_multi_currency' ] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset( $this->woocommerce_wpml->settings[ 'display_custom_prices' ]  ) &&
            $this->woocommerce_wpml->settings[ 'display_custom_prices' ] ){

            $client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
            $woocommerce_currency = get_option( 'woocommerce_currency' );

            if( $client_currency == $woocommerce_currency ){
                return $filtered_posts;
            }
            $matched_products = array();
            $matched_products_query = $this->wpdb->get_results( "
	        	SELECT DISTINCT ID, post_parent, post_type FROM {$this->wpdb->posts}
				INNER JOIN {$this->wpdb->postmeta} ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = '_wcml_custom_prices_status' AND meta_value = 1
			", OBJECT_K );

            if ( $matched_products_query ) {
                remove_filter( 'get_post_metadata', array( $this->woocommerce_wpml->multi_currency->prices, 'product_price_filter' ), 10, 4);
                foreach ( $matched_products_query as $product ) {
                    if( !get_post_meta( $product->ID,'_price_'.$client_currency, true ) ) continue;
                    if ( $product->post_type == 'product' )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->ID, 'product', true );
                    if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->post_parent, get_post_type( $product->post_parent ), true );
                }
                add_filter('get_post_metadata', array( $this->woocommerce_wpml->multi_currency->prices, 'product_price_filter' ), 10, 4);
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

    public function set_prices_config(){
        global $iclTranslationManagement, $sitepress_settings;

        $wpml_settings = $this->sitepress->get_settings();

        if( !isset ( $wpml_settings[ 'translation-management' ] ) ||
            !isset( $iclTranslationManagement ) ||
            !( $iclTranslationManagement instanceof TranslationManagement ) ) {
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

        foreach( $keys as $key ){
            $iclTranslationManagement->settings[ 'custom_fields_readonly_config' ][] = $key;
            if( !isset( $sitepress_settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] ) ||
                $wpml_settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] != 1 ) {
                $wpml_settings[ 'translation-management' ][ 'custom_fields_translation' ][ $key ] = 1;
                $save = true;
            }

            if( !empty( $this->woocommerce_wpml->multi_currency ) ){
                foreach( $this->woocommerce_wpml->multi_currency->get_currency_codes() as $code ){
                    $new_key = $key.'_'.$code;
                    $iclTranslationManagement->settings[ 'custom_fields_readonly_config' ][] = $new_key;

                    if( !isset( $sitepress_settings[ 'translation-management' ][ 'custom_fields_translation' ][ $new_key ] ) ||
                        $wpml_settings[ 'translation-management' ][ 'custom_fields_translation' ][ $new_key ] != 0) {
                        $wpml_settings[ 'translation-management' ][ 'custom_fields_translation' ][ $new_key ] = 0;
                        $save = true;
                    }
                }
            }
        }

        if ($save) {
            $this->sitepress->save_settings( $wpml_settings );
        }
    }

}