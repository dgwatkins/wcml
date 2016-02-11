<?php

class Test_WCML_Products_UI extends WCML_UnitTestCase {


    function setUp(){
        parent::setUp();

        global $woocommerce_wpml,$sitepress;

        $this->wcml_products = new WCML_Products();
        $this->wcml_products_ui = new WCML_Products_UI( $woocommerce_wpml, $sitepress );
        $this->wcml_helper = new WCML_Helper();

        // create 100 dummy products
        for($i = 0; $i < 100; $i++){
            $product = $this->wcml_helper->add_product( 'en' , false, sprintf('Test Product: %d', $i) );
            $trid_map[$i] = $product->trid;
        }

        //add translations
        for($i = 0; $i<100; $i++) {
            $product = $this->wcml_helper->add_product( 'es', $trid_map[$i], sprintf('Test Product ES: %d', $i) );
        }

        //add 10 dummy products in ES
        $trid_map = array();
        for($i = 0; $i<10; $i++) {
            $product = $this->wcml_helper->add_product( 'es', false, sprintf( 'Test Product ES ORIGINAL: %d', $i ) );
            $trid_map[$i] = $product->trid;
        }

        //add translations
        for($i = 0; $i<10; $i++) {
            $product = $this->wcml_helper->add_product( 'en', $trid_map[$i], sprintf( 'Test Product ES ORIGINAL EN: %d', $i ) );
        }


    }


    function test_wcml_products_get_product_list() {
        global $sitepress;

        $en_products = $this->wcml_products_ui->get_product_list(1, 5, 'en');
        $es_products = $this->wcml_products_ui->get_product_list(3, 4, 'es');

        if($es_products){
            $lang_info = $sitepress->get_element_language_details($es_products[0]->ID, 'post_product');
            $es_product_0_lang = $lang_info->language_code;
        }


        // Get products per page (EN) - page 1, 5 per page
        $this->assertEquals( 5, count($en_products) );

        // Get products per page (ES)- page 3, 4 per page
        $this->assertEquals( 2, count($es_products) );

        // ES products query shows ES products
        $this->assertEquals( 'es', $es_product_0_lang );

    }

    function test_wcml_get_products_categories(){

        $products = $this->wcml_products_ui->get_product_list( 1, 20, 'en');

        foreach( $products as $key => $product ){
            $this->wcml_helper->add_term( 'Category '.$key, 'product_cat', 'en', $product->ID );
        }
        $this->assertEquals( 20, count( $this->wcml_products_ui->get_products_categories() ) );

        $products = $this->wcml_products_ui->get_product_list( 1, 10, 'es');

        foreach( $products as $key => $product ){
            $this->wcml_helper->add_term( 'Category ES '.$key, 'product_cat', 'es', $product->ID );
        }
        $this->assertEquals( 10, count( $this->wcml_products_ui->get_products_categories( 'es' ) ) );
    }

    function test_wcml_get_categories_list(){

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product cat' );
        $this->wcml_helper->add_term( 'Category 1', 'product_cat', 'en', $product->id );
        $this->wcml_helper->add_term( 'Category 2', 'product_cat', 'en', $product->id );


        $cat_list = $this->wcml_products_ui->get_categories_list( $product->id, $this->wcml_products_ui->get_cat_url() );
        $this->assertEquals( 2, count( $cat_list ) );
        $this->assertEquals( 'Category 1, ', $cat_list[0]['name'] );
        $this->assertEquals( 'Category 2', $cat_list[1]['name'] );

    }

    function test_wcml_get_products_from_filter(){

        $this->assertEquals( false, $this->wcml_products_ui->get_products_from_filter() );

        $_GET['cat'] = 0;
        $products_info = $this->wcml_products_ui->get_products_from_filter();

        $this->assertEquals( 20, count( $products_info[ 'products' ] ) );
        //we set 110 original products in SetUp method above
        $this->assertEquals( 110, $products_info[ 'products_count' ] );


        $term = $this->wcml_helper->add_term( 'Category 1', 'product_cat', 'en' );

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product cat' );
        $this->wcml_helper->add_term( false, 'product_cat', 'en', $product->id, false, $term->term_id );

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product cat 2' );
        $this->wcml_helper->add_term( false, 'product_cat', 'en', $product->id, false, $term->term_id );
        $_GET['cat'] = $term->term_id;
        $products_info = $this->wcml_products_ui->get_products_from_filter();
        $this->assertEquals( 2, $products_info[ 'products_count' ] );


    }

    function test_wcml_get_products_count(){
        //we set 110 original products in SetUp method above
        $this->assertEquals( 110, $this->wcml_products_ui->get_products_count( $this->wcml_products_ui->get_source_language() ) );
        //we set 10 ES original products in SetUp method above
        $this->assertEquals( 10, $this->wcml_products_ui->get_products_count( 'es' ) );
    }

    function test_wcml_get_product_list(){

        //test hierarchical
        $parent_product = $this->wcml_helper->add_product( 'en' , false, 'Test Product hierarchical' );
        $child_product = $this->wcml_helper->add_product( 'en' , false, 'Test Product hierarchical child', $parent_product->id );

        $products_info = $this->wcml_products_ui->get_product_list( 1, 20, $this->wcml_products_ui->get_source_language() );
        $this->assertEquals( $parent_product->id, $products_info[0]->ID );
        $this->assertEquals( $child_product->id, $products_info[1]->ID );


        $this->assertEquals( 20, count( $products_info ) );

    }

    function test_wcml_get_product_info_for_translators(){
        global $wpdb;

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product for translator' );
        $user_id    = $this->make_current_user_admin();

        $translator_id    = $this->make_current_user_admin();

        $tm_records       = new WPML_TM_Records( $wpdb );
        $subject          = new WPML_Translation_Job_Factory( $tm_records );
        $subject->create_local_post_job(
            $product->id, 'es', $user_id );

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product for translator 2' );
        $subject->create_local_post_job( $product->id, 'es', 1 );

        $products_info = $this->wcml_products_ui->get_product_info_for_translators();
        $this->assertEquals( 1, $products_info[ 'products_count' ] );

        $this->logout_current_user();
    }


    function test_wcml_get_product_info_from_self_edit_mode(){

        $this->assertFalse( $this->wcml_products_ui->get_product_info_from_self_edit_mode() );

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product by id' );

        $_GET[ 'prid' ] = $product->id;

        $products_info = $this->wcml_products_ui->get_product_info_from_self_edit_mode();
        $this->assertEquals( 1, $products_info[ 'products_count' ] );
    }

    function test_wcml_check_rendered_table(){

        $this->make_current_user_wcml_admin();

        $content = $this->wcml_products_ui->get_view();
        $dom = new DOMDocument();

        $dom->loadHTML( $content );

        //check drop-downs from filter at top
        $selectors  = $dom->getElementsByTagName( 'select' );

        $status_lang = $selectors->item(0);
        $this->assertNotEmpty( $status_lang );
        $this->assertTrue( $status_lang->hasChildNodes() );

        $product_categories = $selectors->item(1);
        $this->assertNotEmpty( $product_categories );
        $this->assertTrue( $product_categories->hasChildNodes() );

        $translation_statuses = $selectors->item(2);
        $this->assertNotEmpty( $translation_statuses );
        $this->assertTrue( $translation_statuses->hasChildNodes() );

        $product_statuses = $selectors->item(3);
        $this->assertNotEmpty( $product_statuses );
        $this->assertTrue( $product_statuses->hasChildNodes() );

        //check products table
        $img  = $dom->getElementsByTagName( 'img' );
        $this->assertNotEmpty( $img );

        $products  = $dom->getElementsByTagName( 'tr' );
        // 1 tr uses for filter drop-downs
        $this->assertEquals( 21, $products->length );

        //check pagination
        $current_page  = $dom->getElementById( 'current-page-selector' );
        $this->assertNotEmpty( $current_page );
        $this->assertEquals( 1, $current_page->getAttribute('value') );

    }


}