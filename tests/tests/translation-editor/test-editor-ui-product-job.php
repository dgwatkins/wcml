 <?php

 /**
  * Class WCML_Editor_UI_Product_Job
  */
 class Test_Editor_UI_Product_Job extends WCML_UnitTestCase {

     private $default_language;
     private $second_language;

     function setUp() {
         parent::setUp();
         $this->default_language = $this->sitepress->get_default_language();
         $this->second_language = 'es';
     }

     public function test_get_product_custom_field_label(){
         $custom_field = '_test_custom';
         $product = $this->wcml_helper->add_product( $this->default_language, 'product', false );
         $this->wcml_helper->set_custom_field_to_translate( $custom_field );

         $job_details = array(
             'job_type'             => 'product',
             'job_id'               => $product->id,
             'target'               => $this->second_language,
             'translation_complete' => false,
         );

         $obj = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

         $custom_field_label = $obj->get_product_custom_field_label( $custom_field );
         $this->assertEquals( 'Test custom', $custom_field_label );

     }

 }
