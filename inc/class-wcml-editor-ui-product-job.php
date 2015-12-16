<?php

class WCML_Editor_UI_Product_Job extends WPML_Editor_UI_Job {
    
    private $data = array();
    
	function __construct( $job_id, $product, $translation, $source_lang, $target_lang ) {
        
        global $woocommerce_wpml, $wpdb;

		parent::__construct( $job_id, 'wc_product', $product->post_title,  get_post_permalink( $product->ID ), $source_lang, $target_lang );

		$data = $this->get_data( $product, $translation, $target_lang );
        $this->data = array_keys ( $data );

		$this->add_field( new WPML_Editor_UI_Single_Line_Field( 'title', 'Title', $data, true ) );
		$this->add_field( new WPML_Editor_UI_Single_Line_Field( 'slug', 'Slug', $data, true ) );
		$this->add_field( new WPML_Editor_UI_WYSIWYG_Field( 'content', 'Content / Description', $data, true ) );
        
		$excerpt_section = new WPML_Editor_UI_Field_Section( 'Excerpt' );
		$excerpt_section->add_field( new WPML_Editor_UI_WYSIWYG_Field( 'excerpt', null, $data, true ) );
		$this->add_field( $excerpt_section );
        
		$purchase_note_section = new WPML_Editor_UI_Field_Section( 'Purchase note' );
		$purchase_note_section->add_field( new WPML_Editor_UI_TextArea_Field( 'purchase-note', null, $data, true ) );
		$this->add_field( $purchase_note_section );

        $product_images = $woocommerce_wpml->products->product_images_ids( $product->ID );
		if ( !empty( $product_images ) ) {
			$images_section = new WPML_Editor_UI_Field_Section( 'Images' );
			foreach( $product_images as $image_id ) {
				$attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $image_id ) );
				if( !$attachment_data ) continue;
				$image = new WPML_Editor_UI_Field_Image( 'image-id-' . $image_id, $image_id, $data, true );
				$images_section->add_field( $image );
			}
			$this->add_field( $images_section );
		}

        $attributes = $woocommerce_wpml->products->get_custom_product_atributes( $product->ID );
        if ( $attributes ){
            $attributes_section = new WPML_Editor_UI_Field_Section( 'Custom Product attributes' );
            foreach( $attributes as $attr_key => $attribute ) {
                $group = new WPML_Editor_UI_Field_Group( '', true );
                $attribute_field = new WPML_Editor_UI_Single_Line_Field( $attr_key . '_name', 'Name', $data, false );
                $group->add_field( $attribute_field );
                $attribute_field = new WPML_Editor_UI_Single_Line_Field( $attr_key , 'Value(s)', $data, false );
                $group->add_field( $attribute_field );
                $attributes_section->add_field( $group );
            }
            $this->add_field( $attributes_section );
        }

        $custom_fields = $woocommerce_wpml->products->get_product_custom_fields_to_translate( $product->ID );

        if( $custom_fields ) {
            $custom_fields_section = new WPML_Editor_UI_Field_Section( 'Custom Fields' );
            foreach( $custom_fields as $custom_field ) {
                $custom_field_input = new WPML_Editor_UI_Single_Line_Field( $custom_field, $woocommerce_wpml->products->get_product_custom_field_label(  $product->ID, $custom_field ), $data, true );
                $custom_fields_section->add_field( $custom_field_input );
            }
            $this->add_field( $custom_fields_section );
        }

        do_action( 'wcml_gui_additional_box_html', $this, $product->ID, $data );
    }
    
    function get_data( $product, $translation, $target_lang ) {
        
        global $woocommerce_wpml, $wpdb;

		$data = array( 'title'    => array( 'original' => $product->post_title ),
					   'slug'     => array( 'original' => $product->post_name ),
					   'content'  => array( 'original' => $product->post_content ),
                       'excerpt'  => array( 'original' => $product->post_excerpt ),
                        'purchase-note' => array( 'original' => get_post_meta( $product->ID, '_purchase_note', true ) )
                     );

        if ( $translation ) {
    		$data[ 'title' ][ 'translation' ]   = $translation->post_title;
			$data[ 'slug' ][ 'translation' ]    = $translation->post_name;
			$data[ 'content' ][ 'translation' ] = $translation->post_content;
            $data[ 'excerpt' ][ 'translation' ] = $translation->post_excerpt;
            $data[ 'purchase-note' ][ 'translation' ] = get_post_meta( $translation->ID, '_purchase_note', true );
        }

        $product_images = $woocommerce_wpml->products->product_images_ids( $product->ID );
        
        foreach( $product_images as $image_id ) {
            $attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $image_id ) );
            if( !$attachment_data ) continue;
            $data[ 'image-id-' . $image_id ][ 'title' ]       = array( 'original' => $attachment_data->post_title );
            $data[ 'image-id-' . $image_id ][ 'caption' ]     = array( 'original' => $attachment_data->post_excerpt );
            $data[ 'image-id-' . $image_id ][ 'description' ] = array( 'original' => $attachment_data->post_content );
            
            $trnsl_prod_image = apply_filters( 'translate_object_id', $image_id, 'attachment', false, $target_lang );
            if ( !is_null( $trnsl_prod_image ) ){
                $trnsl_attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $trnsl_prod_image ) );
                $data[ 'image-id-' . $image_id ][ 'title' ][ 'translation' ]       = $attachment_data->post_title;
                $data[ 'image-id-' . $image_id ][ 'caption' ][ 'translation' ]     = $attachment_data->post_excerpt;
                $data[ 'image-id-' . $image_id ][ 'description' ][ 'translation' ] = $attachment_data->post_content;
            }
        }

        $attributes = $woocommerce_wpml->products->get_custom_product_atributes( $product->ID );

        if( $attributes ){
            foreach( $attributes as $attr_key => $attribute ){

                $data[ $attr_key.'_name' ]       = array( 'original' => $attribute['name'] );
                $data[ $attr_key ]       = array( 'original' => $attribute['value'] );

                $trn_attribute = $woocommerce_wpml->products->get_custom_attribute_translation( $product->ID, $attr_key, $attribute, $target_lang );

                $data[ $attr_key.'_name' ][ 'translation' ]       = $trn_attribute['name'] ? $trn_attribute['name'] : '';
                $data[ $attr_key ][ 'translation' ]       = $trn_attribute['value'] ? $trn_attribute['value'] : '';
            }
        }



        $custom_fields = $woocommerce_wpml->products->get_product_custom_fields_to_translate( $product->ID );

        if( $custom_fields ){

            foreach( $custom_fields as $custom_field ) {
                $data[ $custom_field ]       = array( 'original' => get_post_meta( $product->ID, $custom_field, true ) );
                $data[ $custom_field ][ 'translation' ]       =  $translation->ID ? get_post_meta( $translation->ID, $custom_field, true) : '';

            }

        }

        $data = apply_filters( 'wcml_gui_additional_box_data', $data, $product->ID, $translation, $target_lang );

        return $data;
    }
    
    public function save_translations( $translations ) {
        global $woocommerce_wpml, $sitepress, $wpdb, $sitepress_settings, $iclTranslationManagement;
		

        $original_product_id = $this->job_id;
        $orig_product = get_post( $original_product_id );
        $language = $this->get_target_language();

        $languages = $sitepress->get_active_languages();

        $product_trid = $sitepress->get_element_trid( $original_product_id, 'post_' . $orig_product->post_type );
        $tr_product_id = apply_filters( 'translate_object_id', $original_product_id, 'product', false, $language );

		
		$save_filters = new WCML_editor_save_filters( $product_trid, $language );
		
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
            $args[ 'post_type' ] = $orig_product->post_type;
            $args[ 'post_content' ] = $translations[ md5( 'content' ) ];
            $args[ 'post_excerpt' ] = $translations[ md5( 'excerpt' ) ];
            $args[ 'post_status' ] = $orig_product->post_status;
            $args[ 'menu_order '] = $orig_product->menu_order;
            $args[ 'ping_status' ] = $orig_product->ping_status;
            $args[ 'comment_status' ] = $orig_product->comment_status;
            $product_parent = apply_filters( 'translate_object_id', $orig_product->post_parent, 'product', false, $language );
            $args[ 'post_parent'] = is_null( $product_parent ) ? 0 : $product_parent;

            //TODO: remove after change required WPML version > 3.3
            $_POST[ 'to_lang' ] = $language;
            // for WPML > 3.3
            $_POST[ 'icl_post_language' ] = $language;

            if ( $woocommerce_wpml->settings[ 'products_sync_date' ] ) {
                $args[ 'post_date' ] = $orig_product->post_date;
            }

            $tr_product_id = wp_insert_post( $args );

            $translation_id = $wpdb->get_var( $wpdb->prepare( "SELECT translation_id
                                                  FROM {$wpdb->prefix}icl_translations
                                                  WHERE element_type=%s AND trid=%d AND language_code=%s AND element_id IS NULL ",
                "post_product", $product_trid, $language ) );

            if ( $translation_id ) {

                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND trid=%d",
                        $tr_product_id, $product_trid
                    )
                );

                $wpdb->update( $wpdb->prefix . 'icl_translations', array( 'element_id' => $tr_product_id ), array( 'translation_id' => $translation_id ) );

                $iclTranslationManagement->update_translation_status(
                    array(
                        'status' => ICL_TM_COMPLETE,
                        'needs_update' => 0,
                        'translation_id' => $translation_id,
                        'translator_id' => get_current_user_id()
                    ));

            } else {

                $sitepress->set_element_language_details( $tr_product_id, 'post_' . $orig_product->post_type, $product_trid, $language );

            }

            $woocommerce_wpml->products->duplicate_product_post_meta( $original_product_id, $tr_product_id, $translations, true );


        } else {

            //update post
            $args = array();
            $args[ 'ID' ] = $tr_product_id;
            $args[ 'post_title' ] = $translations[ md5( 'title' ) ];
            $args[ 'post_content' ] = $translations[ md5( 'content' ) ];
            $args[ 'post_excerpt' ] = $translations[ md5( 'excerpt' ) ];
            $args[ 'post_status' ] = $orig_product->post_status;
            $args[ 'ping_status' ] = $orig_product->ping_status;
            $args[ 'comment_status' ] = $orig_product->comment_status;
            $product_parent = apply_filters( 'translate_object_id', $orig_product->post_parent, 'product', false, $language );
            $args[ 'post_parent' ] = is_null( $product_parent ) ? 0 : $product_parent;
            $_POST[ 'to_lang' ] = $language;

            $sitepress->switch_lang( $language );
            wp_update_post( $args );
            $sitepress->switch_lang();

            $post_name = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $tr_product_id ) );
            if ( isset( $translations[ md5( 'slug' ) ] ) && $translations[ md5( 'slug' ) ] != $post_name ) {
                // update post_name
                // need set POST variable ( WPML used them when filtered this function)

                $new_post_name = sanitize_title( $translations[ md5( 'slug' ) ] ? $translations[ md5( 'slug' ) ] : $translations[ md5( 'title' ) ]);
                $_POST[ 'new_title' ] = $translations[ md5( 'title' ) ];
                $_POST[ 'new_slug' ] = $new_post_name;
                $new_slug = wp_unique_post_slug( $new_post_name, $tr_product_id, $orig_product->post_status, $orig_product->post_type, $args[ 'post_parent' ] );
                $wpdb->update( $wpdb->posts, array( 'post_name' => $new_slug ), array( 'ID' => $tr_product_id ) );
            }

            $sitepress->set_element_language_details( $tr_product_id, 'post_' . $orig_product->post_type, $product_trid, $language );
            $woocommerce_wpml->products->duplicate_product_post_meta( $original_product_id, $tr_product_id, $translations );

        }

        //sync taxonomies
        $woocommerce_wpml->products->sync_product_taxonomies( $original_product_id, $tr_product_id, $language );

        do_action( 'wcml_update_extra_fields', $original_product_id, $tr_product_id, $translations, $language );

        do_action( 'wcml_before_sync_product_data', $original_product_id, $tr_product_id, $language );

        $woocommerce_wpml->products->sync_product_attr( $original_product_id, $tr_product_id, $language, $translations );

        $woocommerce_wpml->products->sync_default_product_attr( $original_product_id, $tr_product_id, $language );

        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );
        //sync media
        if ( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ) {
            //sync feature image
            $woocommerce_wpml->products->sync_thumbnail_id( $original_product_id, $tr_product_id, $language );
        }

        if ( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ] ) {
            //sync product gallery
            $woocommerce_wpml->products->sync_product_gallery( $original_product_id );
        }

        // synchronize post variations
        $woocommerce_wpml->products->sync_product_variations( $original_product_id, $tr_product_id, $language, $translations );

        $woocommerce_wpml->products->sync_linked_products( $original_product_id, $tr_product_id, $language );

        //save images texts
        if ( isset( $translations[ 'images' ] ) ) {
            foreach ( $translations[ 'images' ] as $key => $image ) {
                //update image texts
                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_title' => $image[ 'title' ],
                        'post_content' => $image[ 'description' ],
                        'post_excerpt' => $image[ 'caption' ]
                    ),
                    array('id' => $key)
                );
            }
        }


        $product_translations = $sitepress->get_element_translations( $product_trid, 'post_product', false, false, true );
        if ( ob_get_length() ) {
            ob_clean();
        }
        ob_start();
        $return = array();

        $woocommerce_wpml->products->get_translation_statuses( $original_product_id, $product_translations, $languages, isset( $translations[ 'slang' ] ) && $translations[ 'slang' ] != 'all' ? $translations[ 'slang' ] : false, $product_trid, $language );
        $return[ 'status_link' ] = ob_get_clean();


        // no longer a duplicate
        if ( !empty( $translations[ 'end_duplication' ][ $original_product_id ][ $language ] ) ) {
            delete_post_meta( $tr_product_id, '_icl_lang_duplicate_of', $original_product_id );

        }

        return $return;

    }
    
}

class WCML_editor_save_filters {

	private $trid;
	private $language;
	
	public function __construct( $trid, $language ) {
		$this->trid     = $trid;
		$this->language = $language;

        add_filter( 'wpml_tm_save_post_trid_value', array( $this, 'wpml_tm_save_post_trid_value' ), 10, 2 );
        add_filter( 'wpml_tm_save_post_lang_value', array( $this, 'wpml_tm_save_post_lang_value' ), 10, 2 );
        add_filter( 'wpml_save_post_trid_value', array( $this, 'wpml_save_post_trid_value' ), 10, 3 );
        add_filter( 'wpml_save_post_lang', array( $this, 'wpml_save_post_lang_value' ), 10 );
	}
	
	public function __destruct() {
        remove_filter( 'wpml_tm_save_post_trid_value', array( $this, 'wpml_tm_save_post_trid_value' ), 10, 2 );
        remove_filter( 'wpml_tm_save_post_lang_value', array( $this, 'wpml_tm_save_post_lang_value' ), 10, 2 );
        remove_filter( 'wpml_save_post_trid_value', array( $this, 'wpml_save_post_trid_value' ), 10, 3 );
        remove_filter( 'wpml_save_post_lang', array( $this, 'wpml_save_post_lang_value' ), 10 );
	}

    // translation-management $trid filter
    function wpml_tm_save_post_trid_value( $trid, $post_id ) {
		$trid = $this->trid ? $this->trid : $trid;
        return $trid;
    }

    // translation-management $lang filter
    function wpml_tm_save_post_lang_value( $lang,$post_id ) {
        if(isset($_POST['action']) &&  $_POST['action'] == 'wpml_translation_dialog_save_job'){
            $lang = $this->language ? $this->language : $lang;
        }
        return $lang;
    }

    // sitepress $trid filter
    function wpml_save_post_trid_value( $trid,$post_status ) {
        if( $post_status != 'auto-draft' ){
			$trid = $this->trid ? $this->trid : $trid;
        }
        return $trid;
    }

    // sitepress $lang filter
    function wpml_save_post_lang_value( $lang ) {
        $lang = $this->language ? $this->language : $lang;
        return $lang;
    }
}