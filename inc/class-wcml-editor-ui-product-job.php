<?php

class WCML_Editor_UI_Product_Job extends WPML_Editor_UI_Job {
    
    private $data = array();
    
	function __construct( $product, $translation, $source_lang, $target_lang ) {
        
        global $woocommerce_wpml, $wpdb;

		parent::__construct( $product->ID, 'product', $product->post_title, '', $source_lang, $target_lang );

		$data = $this->get_data( $product, $translation );
        $this->data = $data;
		
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
		$images_section = new WPML_Editor_UI_Field_Section( 'Images' );
        foreach( $product_images as $image_id ) {
            $attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $image_id ) );
            if( !$attachment_data ) continue;
    		$image = new WPML_Editor_UI_Field_Image( 'image-id-' . $image_id, $image_id, $data, true );
    		$images_section->add_field( $image );
        }
        $this->add_field( $images_section );
        
        
    }
    
    function get_data( $product, $translation ) {
        
        global $woocommerce_wpml, $wpdb;

		$data = array( 'title'    => array( 'original' => $product->post_title ),
					   'slug'     => array( 'original' => $product->post_name ),
					   'content'  => array( 'original' => $product->post_content ),
                       'excerpt'  => array( 'original' => $product->post_excerpt )
                     );

        if ( $translation ) {
    		$data[ 'title' ][ 'translation' ]   = $translation->post_title;
			$data[ 'slug' ][ 'translation' ]    = $translation->post_name;
			$data[ 'content' ][ 'translation' ] = $translation->post_content;
            $data[ 'excerpt' ][ 'translation' ] = $translation->post_excerpt;
        }

        $product_images = $woocommerce_wpml->products->product_images_ids( $product->ID );
        
        foreach( $product_images as $image_id ) {
            $attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $image_id ) );
            if( !$attachment_data ) continue;
            $data[ 'image-id-' . $image_id ][ 'title' ]       = array( 'original' => $attachment_data->post_title );
            $data[ 'image-id-' . $image_id ][ 'caption' ]     = array( 'original' => $attachment_data->post_excerpt );
            $data[ 'image-id-' . $image_id ][ 'description' ] = array( 'original' => $attachment_data->post_content );
            
            $trnsl_prod_image = apply_filters( 'translate_object_id', $prod_image, 'attachment', false, $target_lang );
            if ( !is_null( $trnsl_prod_image ) ){
                $trnsl_attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $trnsl_prod_image ) );
                $data[ 'image-id-' . $image_id ][ 'title' ][ 'translation' ]       = $attachment_data->post_title;
                $data[ 'image-id-' . $image_id ][ 'caption' ][ 'translation' ]     = $attachment_data->post_excerpt;
                $data[ 'image-id-' . $image_id ][ 'description' ][ 'translation' ] = $attachment_data->post_content;
            }
        }
        
        return $data;
    }
    
    public function save_translations( $translations ) {
        foreach( $this->data as $id => $data ) {
            $key = md5( $id );
            if ( isset( $translations[ $key ] ) ) {
                $translation = $translations[ $key ];
                if ( $translation ) {
                    // Save the translation according to it's id
                }
            }
        }
    }
    
}