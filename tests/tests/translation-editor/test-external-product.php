 <?php

 /**
  * Class Test_External_Product_Translation_Editor
  */
 class Test_External_Product_Translation_Editor_Section extends WCML_UnitTestCase {

     function setUp() {
         global $woocommerce, $sitepress, $woocommerce_wpml, $wpdb;
         parent::setUp();
         $this->sitepress = $sitepress;
         $this->woocommerce_wpml = $woocommerce_wpml;
         $this->woocommerce = $woocommerce;
         $this->wpdb = $wpdb;
         $this->default_language = $this->sitepress->get_default_language();
         $active_languages = $this->sitepress->get_active_languages();
         unset( $active_languages[ $this->default_language ] );
         $this->second_language = array_rand( $active_languages );

         wpml_test_reg_custom_post_type( 'wc_product_tab', true );
         $this->tp = new WPML_Element_Translation_Package;

         //force custom fields translatable
         add_filter( 'wcml_product_content_fields', array( $this, 'make_custom_fields_translatable' ) );
     }

     public function test_external_product_section_added(){

         $product = wpml_test_insert_post( $this->default_language, 'product', false );

         wp_set_object_terms( $product, 'external', 'product_type' );

         $expected_product_url = 'http://urlescu.com';
         $expected_button_text = 'Buy me already!';

         update_post_meta( $product, '_product_url', $expected_product_url );
         update_post_meta( $product, '_button_text', $expected_button_text );

         $job_details = array(
             'job_type'             => 'product',
             'job_id'               => $product,
             'target'               => $this->second_language,
             'translation_complete' => true,
         );

         $obj = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

         $all_fields = $obj->get_all_fields();
         foreach( $all_fields as $field ){
             if( $field['field_type'] === '_product_url' ){
                 $product_url = $field['field_data'];
             }
             if( $field['field_type'] === '_button_text' ){
                 $button_text = $field['field_data'];
             }
         }

         $this->assertNotNull( $product_url );
         $this->assertNotNull( $button_text );

         $this->assertEquals( $product_url, $expected_product_url );
         $this->assertEquals( $button_text, $expected_button_text );

     }

     public function make_custom_fields_translatable( $cf ){

         $cf[] = '_product_url';
         $cf[] = '_button_text';

         return $cf;
     }

 }
