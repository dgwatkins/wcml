<?php

class WCML_Editor_UI_Product_Job extends WPML_Editor_UI_Job {
    
    private $data = array();
    private $woocommerce_wpml;
    private $sitepress;
    private $wpdb;
    private $job_details;
    private $product;
    private $source_lang;
    private $target_lang;
    private $translation_complete;
    private $duplicate;
    private $not_display_fields_for_variables_product;

	function __construct( $job_details, &$woocommerce_wpml, &$sitepress, &$wpdb  ) {

        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;
        $this->wpdb = $wpdb;
        $this->set_private_variables( $job_details );
        $this->not_display_fields_for_variables_product = array( '_purchase_note', '_regular_price', '_sale_price',
                                                                 '_price', '_min_variation_price', '_max_variation_price',
                                                                 '_min_variation_regular_price', '_max_variation_regular_price',
                                                                 '_min_variation_sale_price', '_max_variation_sale_price' );

		parent::__construct(
            $job_details[ 'job_id' ],
            'wc_product', __( 'Product', 'woocommerce-multilingual' ),
            $this->product->post_title,
            get_post_permalink( $this->product->ID ),
            $this->source_lang,
            $this->target_lang,
            $this->translation_complete,
            $this->duplicate
        );

        $this->add_elements();
    }

    private function set_private_variables( $job_details ){

        $this->job_details = $job_details;
        $this->product = get_post( $job_details[ 'job_id' ] );
        $this->source_lang = $this->sitepress->get_language_for_element( $job_details[ 'job_id' ], 'post_product' );
        $this->target_lang = $job_details[ 'target'];
        $this->translation_complete = $this->is_translation_complete();
        $this->duplicate = $this->is_duplicate();
        $this->data = $this->get_data();

    }

    public function is_translation_complete(){

        $translation_complete = false;

        $product_trid         = $this->sitepress->get_element_trid( $this->product->ID, 'post_product' );
        $product_translations = $this->sitepress->get_element_translations( $product_trid, 'post_product', false, false, true );
        if ( isset( $job_details[ 'translation_complete' ] ) ) {
            $translation_complete = $job_details[ 'translation_complete' ] == 'true';
        } else if ( isset( $product_translations[ $this->target_lang ] ) ) {
            $tr_status = $this->wpdb->get_var(
                            $this->wpdb->prepare("
                                SELECT status FROM {$this->wpdb->prefix}icl_translation_status
                                WHERE translation_id = %d", $product_translations[ $this->target_lang ]->translation_id
                            )
            );

            $translation_complete = $tr_status == ICL_TM_COMPLETE;
        }

        return $translation_complete;
    }

    public function is_duplicate(){

        $is_duplicate_product = false;

        $product_trid         = $this->sitepress->get_element_trid( $this->product->ID, 'post_product' );
        $product_translations = $this->sitepress->get_element_translations( $product_trid, 'post_product', false, false, true );
        if ( isset( $product_translations[ $this->target_lang ] ) && get_post_meta( $product_translations[ $this->target_lang ]->element_id, '_icl_lang_duplicate_of', true) == $this->product->ID ) {
            $is_duplicate_product = true;
        }

        return $is_duplicate_product;
    }

    public function add_elements( ){

        $this->add_field( new WPML_Editor_UI_Auto_Slug_Title_Field( 'title', __( 'Title', 'woocommerce-multilingual' ), $this->data, true ) );
        $this->add_field( new WPML_Editor_UI_Single_Line_Field( 'slug', __( 'Slug', 'woocommerce-multilingual' ), $this->data, true ) );
        $this->add_field( new WPML_Editor_UI_WYSIWYG_Field( 'content', __( 'Content / Description', 'woocommerce-multilingual' ), $this->data, true ) );

        $excerpt_section = new WPML_Editor_UI_Field_Section( __( 'Excerpt', 'woocommerce-multilingual' ) );
        $excerpt_section->add_field( new WPML_Editor_UI_WYSIWYG_Field( 'excerpt', null, $this->data, true ) );
        $this->add_field( $excerpt_section );

        $purchase_note_section = new WPML_Editor_UI_Field_Section( __( 'Purchase note', 'woocommerce-multilingual' ) );
        $purchase_note_section->add_field( new WPML_Editor_UI_TextArea_Field( 'purchase-note', null, $this->data, true ) );
        $this->add_field( $purchase_note_section );

        $product_images = $this->woocommerce_wpml->media->product_images_ids( $this->product->ID );
        if ( !empty( $product_images ) ) {
            $images_section = new WPML_Editor_UI_Field_Section( __( 'Images', 'woocommerce-multilingual' ) );
            foreach( $product_images as $image_id ) {
                $attachment_data = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $this>wpdb->posts WHERE ID = %d", $image_id ) );
                if( !$attachment_data ) continue;
                $image = new WPML_Editor_UI_Field_Image( 'image-id-' . $image_id, $image_id, $this->data, true );
                $images_section->add_field( $image );
            }
            $this->add_field( $images_section );
        }

        $attributes = $this->get_custom_product_atributes( );
        if ( $attributes ){
            $attributes_section = new WPML_Editor_UI_Field_Section( __( 'Custom Product attributes', 'woocommerce-multilingual' ) );
            foreach( $attributes as $attr_key => $attribute ) {
                $group = new WPML_Editor_UI_Field_Group( '', true );
                $attribute_field = new WPML_Editor_UI_Single_Line_Field( $attr_key . '_name', __( 'Name', 'woocommerce-multilingual' ), $this->data, false );
                $group->add_field( $attribute_field );
                $attribute_field = new WPML_Editor_UI_Single_Line_Field( $attr_key , __( 'Value(s)', 'woocommerce-multilingual' ), $this->data, false );
                $group->add_field( $attribute_field );
                $attributes_section->add_field( $group );
            }
            $this->add_field( $attributes_section );
        }

        $custom_fields = $this->get_product_custom_fields_to_translate( $this->product->ID );

        if( $custom_fields ) {
            $custom_fields_section = new WPML_Editor_UI_Field_Section( __( 'Custom Fields', 'woocommerce-multilingual' ) );
            foreach( $custom_fields as $custom_field ) {
                $custom_field_input = new WPML_Editor_UI_Single_Line_Field( $custom_field, $this->get_product_custom_field_label( $custom_field ), $this->data, true );
                $custom_fields_section->add_field( $custom_field_input );
            }
            $this->add_field( $custom_fields_section );
        }

        $is_variable = false;
        if( $this->woocommerce_wpml->products->is_variable_product( $this->product->ID ) ){
            $files_data = $this->get_files_for_variations();
            $is_variable = true;
        }else{
            $files_data = array( $this->product->ID => $this->woocommerce_wpml->downloadable->get_files_data( $this->product->ID ) );
            if( !empty( $files_data ) ){
                $files_section = new WPML_Editor_UI_Field_Section( __( 'Download Files', 'woocommerce-multilingual' ) );
            }
        }

        foreach( $files_data as $post_id => $file_data ){
            $custom_product_sync = get_post_meta( $post_id, 'wcml_sync_files', true );
            if( ( !$custom_product_sync && !$this->woocommerce_wpml->settings['file_path_sync'] ) || ( $custom_product_sync && $custom_product_sync == 'self' ) ) {
                if( $is_variable ){
                    $files_section = new WPML_Editor_UI_Field_Section( sprintf( __( 'Download Files for Variation #%s', 'woocommerce-multilingual' ), $post_id ) );
                }

                foreach( $file_data as $key => $file ) {
                    $sub_group = new WPML_Editor_UI_Field_Group();
                    $field_input = new WPML_Editor_UI_Single_Line_Field( 'file-name'.$key.$post_id, __( 'Name', 'woocommerce-multilingual' ), $this->data, false );
                    $sub_group->add_field( $field_input );
                    $field_input = new WPML_Editor_UI_Single_Line_Field( 'file-url'.$key.$post_id, __( 'File URL', 'woocommerce-multilingual' ), $this->data, false );
                    $sub_group->add_field( $field_input );

                    $files_section->add_field( $sub_group );
                }

                if( $is_variable ){
                    $this->add_field( $files_section );
                }

            }
        }

        if( isset( $files_section ) && !$is_variable ){
            $this->add_field( $files_section );
        }

        do_action( 'wcml_gui_additional_box_html', $this, $this->product->ID, $this->data );

    }

    function get_data() {

        $trn_product_id = apply_filters( 'translate_object_id', $this->product->ID, 'product', false, $this->target_lang );
        $translation = false;
        if( !is_null( $trn_product_id ) ){
            $translation = get_post( $trn_product_id );
        }

		$element_data = array( 'title'    => array( 'original' => $this->product->post_title ),
					   'slug'     => array( 'original' => urldecode( $this->product->post_name ) ),
					   'content'  => array( 'original' => $this->product->post_content ),
                       'excerpt'  => array( 'original' => $this->product->post_excerpt ),
                        'purchase-note' => array( 'original' => get_post_meta( $this->product->ID, '_purchase_note', true ) )
                     );

        if ( $translation ) {
            $element_data[ 'title' ][ 'translation' ]   = $translation->post_title;
            $element_data[ 'slug' ][ 'translation' ]    = urldecode( $translation->post_name );
            $element_data[ 'content' ][ 'translation' ] = $translation->post_content;
            $element_data[ 'excerpt' ][ 'translation' ] = $translation->post_excerpt;
            $element_data[ 'purchase-note' ][ 'translation' ] = get_post_meta( $translation->ID, '_purchase_note', true );
        }

        $product_images = $this->woocommerce_wpml->media->product_images_ids( $this->product->ID );
        foreach( $product_images as $image_id ) {
            $attachment_data = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM {$this->wpdb->posts} WHERE ID = %d", $image_id ) );
            if( !$attachment_data ) continue;
            $element_data[ 'image-id-' . $image_id . '-title' ]       = array( 'original' => $attachment_data->post_title );
            $element_data[ 'image-id-' . $image_id . '-caption' ]     = array( 'original' => $attachment_data->post_excerpt );
            $element_data[ 'image-id-' . $image_id . '-description' ] = array( 'original' => $attachment_data->post_content );
            
            $trnsl_prod_image = apply_filters( 'translate_object_id', $image_id, 'attachment', false, $this->target_lang );
            if ( !is_null( $trnsl_prod_image ) ){
                $trnsl_attachment_data = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM {$this->wpdb->posts} WHERE ID = %d", $trnsl_prod_image ) );
                $element_data[ 'image-id-' . $image_id . '-title' ][ 'translation' ]       = $trnsl_attachment_data->post_title;
                $element_data[ 'image-id-' . $image_id . '-caption' ][ 'translation' ]     = $trnsl_attachment_data->post_excerpt;
                $element_data[ 'image-id-' . $image_id . '-description' ][ 'translation' ] = $trnsl_attachment_data->post_content;
            }
        }

        $attributes = $this->get_custom_product_atributes( );
        if( $attributes ){
            foreach( $attributes as $attr_key => $attribute ){
                $element_data[ $attr_key.'_name' ]       = array( 'original' => $attribute['name'] );
                $element_data[ $attr_key ]       = array( 'original' => $attribute['value'] );

                $trn_attribute = $this->woocommerce_wpml->attributes->get_custom_attribute_translation( $this->product->ID, $attr_key, $attribute, $this->target_lang );

                $element_data[ $attr_key.'_name' ][ 'translation' ]       = $trn_attribute['name'] ? $trn_attribute['name'] : '';
                $element_data[ $attr_key ][ 'translation' ]       = $trn_attribute['value'] ? $trn_attribute['value'] : '';
            }
        }

        $custom_fields = $this->get_product_custom_fields_to_translate( $this->product->ID );
        if( $custom_fields ){
            foreach( $custom_fields as $custom_field ) {
                $element_data[ $custom_field ]       = array( 'original' => get_post_meta( $this->product->ID, $custom_field, true ) );
                $element_data[ $custom_field ][ 'translation' ]       =  ( isset( $translation->ID ) && $translation->ID ) ? get_post_meta( $translation->ID, $custom_field, true) : '';

            }
        }

        if( $this->woocommerce_wpml->products->is_variable_product( $this->product->ID ) ){
            $files_data = $this->get_files_for_variations();
        }else{
            $files_data = array( $this->product->ID => $this->woocommerce_wpml->downloadable->get_files_data( $this->product->ID ) );
        }

        foreach( $files_data as $post_id => $file_data ) {

            $custom_product_sync = get_post_meta( $post_id, 'wcml_sync_files', true);
            if( ( !$custom_product_sync && !$this->woocommerce_wpml->settings['file_path_sync'] ) || ( $custom_product_sync && $custom_product_sync == 'self' ) ) {

                $orig_product_files = $file_data;
                $trnsl_product_files = array();
                if (isset($translation->ID) && $translation->ID) {
                    $trnsl_product_files = $this->woocommerce_wpml->downloadable->get_files_data( $translation->ID );
                }

                foreach ($orig_product_files as $key => $product_file) {

                    $element_data['file-name' . $key.$post_id] = array('original' => $product_file['label']);
                    $element_data['file-url' . $key.$post_id] = array('original' => $product_file['value']);


                    $element_data['file-name' . $key.$post_id]['translation'] = isset( $trnsl_product_files[$key]  ) ? $trnsl_product_files[$key]['label'] : '';
                    $element_data['file-url' . $key.$post_id]['translation'] = isset( $trnsl_product_files[$key] ) ? $trnsl_product_files[$key]['value'] : '';
                }
            }
        }

        $element_data = apply_filters( 'wcml_gui_additional_box_data', $element_data, $this->product->ID, $translation, $this->target_lang );

        return $element_data;
    }
    
    public function save_translations( $translations ) {
        global $sitepress_settings, $iclTranslationManagement;

        if ( get_post_type( $this->product->ID ) != 'product' ) {
            return;
        }

        $languages = $this->sitepress->get_active_languages();

        $product_trid = $this->sitepress->get_element_trid( $this->product->ID, 'post_' . $this->product->post_type );
        $tr_product_id = apply_filters( 'translate_object_id', $this->product->ID, 'product', false, $this->target_lang );
		
		$save_filters = new WCML_Editor_Save_Filters( $product_trid, $this->target_lang );
        if ( get_magic_quotes_gpc() ) {
            foreach ( $translations as $key => $data_item ) {
                if ( !is_array( $data_item ) ) {
                    $translations[$key] = stripslashes( $data_item );
                }
            }
        }

        if ( is_null( $tr_product_id ) ) {
            //insert new post
            $args = array();
            $args[ 'post_title' ] = $translations[ md5( 'title' ) ];
            $args[ 'post_type' ] = $this->product->post_type;
            $args[ 'post_content' ] = $translations[ md5( 'content' ) ];
            $args[ 'post_excerpt' ] = $translations[ md5( 'excerpt' ) ];
            $args[ 'post_status' ] = $this->product->post_status;
            $args[ 'menu_order '] = $this->product->menu_order;
            $args[ 'ping_status' ] = $this->product->ping_status;
            $args[ 'comment_status' ] = $this->product->comment_status;
            $product_parent = apply_filters( 'translate_object_id', $this->product->post_parent, 'product', false, $this->target_lang );
            $args[ 'post_parent'] = is_null( $product_parent ) ? 0 : $product_parent;

            //TODO: remove after change required WPML version > 3.3
            $_POST[ 'to_lang' ] = $this->target_lang;
            // for WPML > 3.3
            $_POST[ 'icl_post_language' ] = $this->target_lang;

            if ( $this->woocommerce_wpml->settings[ 'products_sync_date' ] ) {
                $args[ 'post_date' ] = $this->product->post_date;
            }

            $tr_product_id = wp_insert_post( $args );

            $translation_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT translation_id
                                                  FROM {$this->wpdb->prefix}icl_translations
                                                  WHERE element_type=%s AND trid=%d AND language_code=%s AND element_id IS NULL ",
                "post_product", $product_trid, $this->target_lang ) );

            if ( $translation_id ) {
                $this->wpdb->query(
                    $this->wpdb->prepare(
                        "DELETE FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND trid=%d",
                        $tr_product_id, $product_trid
                    )
                );

                $this->wpdb->update( $this->wpdb->prefix . 'icl_translations', array( 'element_id' => $tr_product_id ), array( 'translation_id' => $translation_id ) );
            } else {
                $this->sitepress->set_element_language_details( $tr_product_id, 'post_' . $this->product->post_type, $product_trid, $this->target_lang );
            }

            $this->woocommerce_wpml->sync_product_data->duplicate_product_post_meta( $this->product->ID, $tr_product_id, $translations, true );
        } else {
            //update post
            $args = array();
            $args[ 'ID' ] = $tr_product_id;
            $args[ 'post_title' ] = $translations[ md5( 'title' ) ];
            $args[ 'post_content' ] = $translations[ md5( 'content' ) ];
            $args[ 'post_excerpt' ] = $translations[ md5( 'excerpt' ) ];
            $args[ 'post_status' ] = $this->product->post_status;
            $args[ 'ping_status' ] = $this->product->ping_status;
            $args[ 'comment_status' ] = $this->product->comment_status;
            $product_parent = apply_filters( 'translate_object_id', $this->product->post_parent, 'product', false, $this->target_lang );
            $args[ 'post_parent' ] = is_null( $product_parent ) ? 0 : $product_parent;
            $_POST[ 'to_lang' ] = $this->target_lang;

            $this->sitepress->switch_lang( $this->target_lang );
            wp_update_post( $args );
            $this->sitepress->switch_lang();

            $post_name = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT post_name FROM {$this->wpdb->posts} WHERE ID=%d", $tr_product_id ) );
            if ( isset( $translations[ md5( 'slug' ) ] ) && $translations[ md5( 'slug' ) ] != $post_name ) {
                // update post_name
                // need set POST variable ( WPML used them when filtered this function)

                $new_post_name = sanitize_title( $translations[ md5( 'slug' ) ] ? $translations[ md5( 'slug' ) ] : $translations[ md5( 'title' ) ]);
                $_POST[ 'new_title' ] = $translations[ md5( 'title' ) ];
                $_POST[ 'new_slug' ] = $new_post_name;
                $new_slug = wp_unique_post_slug( $new_post_name, $tr_product_id, $this->product->post_status, $this->product->post_type, $args[ 'post_parent' ] );
                $this->wpdb->update( $this->wpdb->posts, array( 'post_name' => $new_slug ), array( 'ID' => $tr_product_id ) );
            }

            $this->sitepress->set_element_language_details( $tr_product_id, 'post_' . $this->product->post_type, $product_trid, $this->target_lang );
            $this->woocommerce_wpml->sync_product_data->duplicate_product_post_meta( $this->product->ID, $tr_product_id, $translations );
        }

        $product_translations = $this->sitepress->get_element_translations( $product_trid , 'post_product', false, false, true );
        //sync taxonomies
        $this->woocommerce_wpml->sync_product_data->sync_product_taxonomies( $this->product->ID, $tr_product_id, $this->target_lang );

        do_action( 'wcml_update_extra_fields', $this->product->ID, $tr_product_id, $translations, $this->target_lang );

        do_action( 'wcml_before_sync_product_data', $this->product->ID, $tr_product_id, $this->target_lang );

        $this->woocommerce_wpml->attributes->sync_product_attr( $this->product->ID, $tr_product_id, $this->target_lang, $translations );

        $this->woocommerce_wpml->attributes->sync_default_product_attr( $this->product->ID, $tr_product_id, $this->target_lang );

        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );
        //sync media
        if ( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ) {
            //sync feature image
            $this->woocommerce_wpml->media->sync_thumbnail_id( $this->product->ID, $tr_product_id, $this->target_lang );
        }

        if ( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ] ) {
            //sync product gallery
            $this->woocommerce_wpml->media->sync_product_gallery( $this->product->ID );
        }

        // synchronize post variations
        $this->woocommerce_wpml->sync_variations_data->sync_product_variations( $this->product->ID, $tr_product_id, $this->target_lang, $translations );

        $this->woocommerce_wpml->sync_product_data->sync_linked_products( $this->product->ID, $tr_product_id, $this->target_lang );

        //save images texts
        if ( isset( $translations[ 'images' ] ) ) {
            foreach ( $translations[ 'images' ] as $key => $image ) {
                //update image texts
                $this->wpdb->update(
                    $this->wpdb->posts,
                    array(
                        'post_title' => $image[ 'title' ],
                        'post_content' => $image[ 'description' ],
                        'post_excerpt' => $image[ 'caption' ]
                    ),
                    array('id' => $key)
                );
            }
        }

        if( $product_translations ){
            $iclTranslationManagement->update_translation_status(
                array(
                    'status' => $this->is_translation_complete() ? ICL_TM_COMPLETE : ICL_TM_IN_PROGRESS,
                    'needs_update' => 0,
                    'translation_id' => $product_translations[ $this->target_lang ]->translation_id,
                    'translator_id' => get_current_user_id()
                ));
        }
		
        if ( ob_get_length() > 0 ) {
            ob_clean();
        }
        ob_start();
        $return = array();

        $this->woocommerce_wpml->products->get_translation_statuses( $this->product->ID, $product_translations, $languages, isset( $translations[ 'slang' ] ) && $translations[ 'slang' ] != 'all' ? $translations[ 'slang' ] : false, $product_trid, $this->target_lang );
        $return[ 'status_link' ] = ob_get_clean();

        // no longer a duplicate
        delete_post_meta( $tr_product_id, '_icl_lang_duplicate_of', $this->product->ID );

        return $return;

    }

    public function get_custom_product_atributes( )
    {
        $attributes = get_post_meta( $this->product->ID, '_product_attributes', true);
        if (!is_array($attributes)) {
            $attributes = array();
        }

        foreach ($attributes as $key => $attribute) {
            if ($attribute['is_taxonomy']) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    //get product content labels
    public function get_product_custom_field_label( $field )
    {
        global $woocommerce_wpml, $sitepress;
        $settings = $sitepress->get_settings();
        $label = '';
        if (isset($settings['translation-management']['custom_fields_translation'][$field]) && $settings['translation-management']['custom_fields_translation'][$field] == 2) {
            if (in_array($field, $this->woocommerce_wpml->products->not_display_fields_for_variables_product)) {
                return false;
            }

            $exception = apply_filters('wcml_product_content_exception', true, $this->product->ID, $field);
            if ( !$exception ) {
                return false;
            }

            $custom_key_label = apply_filters('wcml_product_content_label', $field, $this->product->ID);
            if ($custom_key_label != $field) {
                $label = $custom_key_label;
                return $label;
            }

            $custom_key_label = str_replace('_', ' ', $field);
            $label = trim($custom_key_label[0]) ? ucfirst($custom_key_label) : ucfirst(substr($custom_key_label, 1));

        }

        return $label;
    }

    public function get_files_for_variations( ){

        global $wpdb;

        $variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'", $this->product->ID));
        $variations_files = array();

        foreach ($variations as $variation) {

            if( get_post_meta( $variation->ID, '_downloadable', true ) == 'yes' ){

                $variation_files = $this->woocommerce_wpml->downloadable->get_files_data( $variation->ID );

                if( !empty( $variation_files ) ){
                    $variations_files[ $variation->ID ] = $variation_files;
                }

            }

        }

        return $variations_files;

    }

    //get product content
    public function get_product_custom_fields_to_translate( $product_id ){
        $settings = $this->sitepress->get_settings();
        $contents = array();

        foreach( get_post_custom_keys( $product_id ) as $meta_key ) {
            if( isset( $settings[ 'translation-management' ][ 'custom_fields_translation' ][ $meta_key ] ) && $settings[ 'translation-management' ][ 'custom_fields_translation' ][ $meta_key ] == 2 ){
                if( $this->check_custom_field_is_single_value( $product_id, $meta_key ) ){
                    if( in_array( $meta_key, apply_filters( 'wcml_not_display_single_fields_to_translate', $this->not_display_fields_for_variables_product ) ) ){
                        continue;
                    }
                }else{
                    $exception = apply_filters( 'wcml_product_content_exception', true, $product_id, $meta_key );
                    if( $exception ) {
                        continue;
                    }
                }
                $contents[] = $meta_key;
            }
        }
        return apply_filters('wcml_product_content_fields', $contents, $product_id);
    }

    public function check_custom_field_is_single_value( $product_id, $meta_key ){
        $meta_value = maybe_unserialize( get_post_meta( $product_id, $meta_key, true ) );

        if( is_array( $meta_value ) ){
            return false;
        } else {
            return apply_filters( 'wcml_check_is_single', true, $product_id, $meta_key );
        }
    }

}