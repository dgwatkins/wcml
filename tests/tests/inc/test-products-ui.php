<?php

class Test_WCML_Products_UI extends WCML_UnitTestCase {


    function setUp(){
        parent::setUp();

        $this->wcml_products_ui = new WCML_Products_UI( $this->woocommerce_wpml, $this->sitepress );
        $this->languages = array_map('trim', explode(',', WPML_TEST_LANGUAGE_CODES));
        $this->test_data = array();


        $this->test_data[ 'original_products_count' ] = 0;

        $this->add_test_products();

        $this->add_test_categories();

        $this->add_drafts_products();

        $this->add_products_with_childs();

        $this->add_products_with_categories();

        $this->set_filter_values();

        $this->set_filter_combinations();

    }

    function test_products_list(){

        //user not a translator
        $products = $this->wcml_products_ui->get_products_data();
        $this->assertEquals( 0, $products['products_count'] );

        //make current user as admin
        $this->make_current_user_wcml_admin();

        $products = $this->wcml_products_ui->get_products_data();


        foreach( $products['products'] as $key => $product ){
            //check data language in links to translations
            foreach ( $this->languages as $language ) {
                if( $language != $this->woocommerce_wpml->products->get_original_product_language( $product->ID ) ){
                    $this->assertContains( 'data-language="'.$language.'"', $product->translation_statuses );
                }
            }

            //check products ID's
            $i = 1;
            $dummy_products_array = end( $this->test_data[ 'products' ] );
            while( $dummy_product = prev( $dummy_products_array ) ) {
                $this->assertEquals( $dummy_product['id'], $product->ID );
                if( $i == 20 ) break;
                $i++;
            }
        }

        //test products from filter
        foreach( $this->test_data[ 'filters' ][ 'source_languages' ] as $source_language ){
            foreach( $this->test_data[ 'filters' ][ 'categories' ] as $category ){
                foreach( $this->test_data[ 'filters' ][ 'translation_statuses' ] as $translation_status ){
                    foreach( $this->test_data[ 'filters' ][ 'product_statuses' ] as $product_status ){
                        if( isset( $this->test_data[ 'filters' ][ 'combinations' ][ $source_language.'_'.$category.'_'.$translation_status.'_'.$product_status ] ) ){
                            $_GET['slang'] = $source_language;
                            $_GET['cat'] = $category;
                            $_GET['trst'] = $translation_status;
                            $_GET['st'] = $product_status;
                            $products = $this->wcml_products_ui->get_products_data();
                            $this->assertEquals( $this->test_data[ 'filters' ][ 'combinations' ][ $source_language.'_'.$category.'_'.$translation_status.'_'.$product_status ], count( $products['products'] ) );
                        }
                    }
                }
            }
        }

    }


    function test_wcml_products_get_product_list() {

        $en_products = $this->wcml_products_ui->get_product_list(1, 5, 'en');
        $es_products = $this->wcml_products_ui->get_product_list(3, 4, 'es');

        if($es_products){
            $lang_info = $this->sitepress->get_element_language_details($es_products[0]->ID, 'post_product');
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
            $this->wcml_helper->add_term( 'Categories test '.$key, 'product_cat', 'en', $product->ID );
        }
        $this->assertEquals( 23, count( $this->wcml_products_ui->get_products_categories() ) );

        $products = $this->wcml_products_ui->get_product_list( 1, 10, 'es');

        foreach( $products as $key => $product ){
            $this->wcml_helper->add_term( 'Categories test FR '.$key, 'product_cat', 'fr', $product->ID );
        }
        $this->assertEquals( 10, count( $this->wcml_products_ui->get_products_categories( 'fr' ) ) );
    }

    function test_wcml_get_categories_list(){

        $product = $this->wcml_helper->add_product( 'en' , false, 'Test Product cat' );
        $this->wcml_helper->add_term( 'Category list 1', 'product_cat', 'en', $product->id );
        $this->wcml_helper->add_term( 'Category list 2', 'product_cat', 'en', $product->id );


        $cat_list = $this->wcml_products_ui->get_categories_list( $product->id, $this->wcml_products_ui->get_cat_url() );
        $this->assertEquals( 2, count( $cat_list ) );
        $this->assertEquals( 'Category list 1, ', $cat_list[0]['name'] );
        $this->assertEquals( 'Category list 2', $cat_list[1]['name'] );

    }

    function test_wcml_get_products_count(){
        //we set 110 original products in SetUp method above
        $this->assertEquals( 124, $this->wcml_products_ui->get_products_count( $this->wcml_products_ui->get_source_language() ) );
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
        
        $this->check_filter_drop_downs( $dom );        

        $this->check_products_rows( $dom );

        $this->check_pagination( $dom );       

    }

    private function add_test_products(){

        $args = array();
        //add 100 products in EN with ES translations
        $this->test_data[ 'products_count' ][ 'en' ] = 100;
        $this->test_data[ 'original_products_count' ] += $this->test_data[ 'products_count' ][ 'en' ];

        $args[ 'en' ] = array( 'count' => $this->test_data[ 'products_count' ][ 'en' ], 'translations' => array( 'es' ) );

        //add 10 products in ES with EN translations
        $this->test_data[ 'products_count' ][ 'es' ] = 10;
        $this->test_data[ 'original_products_count' ] += $this->test_data[ 'products_count' ][ 'es' ];
        $args[ 'es' ] = array( 'count' => $this->test_data[ 'products_count' ][ 'es' ], 'translations' => array( 'en' ) );
        $this->test_data[ 'products' ] = $this->wcml_helper->add_products( $args );

    }

    private function add_test_categories(){

        $args = array();
        //add 2 product categories
        $this->test_data[ 'categories_count' ][ 'en' ] = 2;
        $args[ 'en' ] = array( 'count' => $this->test_data[ 'categories_count' ][ 'en' ], 'taxonomy' => 'product_cat', 'translations' => array( 'es' ) );
        $this->test_data[ 'terms' ] = $this->wcml_helper->add_dummy_terms( $args );
    }

    private function add_drafts_products(){
        //add 5 draft products in EN
        $this->test_data[ 'draft_products' ][ 'count' ] = 5;
        $this->test_data[ 'original_products_count' ] += $this->test_data[ 'draft_products' ][ 'count' ];
        for( $i=0; $i<$this->test_data[ 'draft_products' ][ 'count' ]; $i++ ){
            $product = $this->wcml_helper->add_product( 'en', false, 'TEST Draft product '.$i );
            $draft = array(
                'ID'			=> $product->id,
                'post_status'	=> 'draft'
            );
            $this->wcml_helper->update_product( $draft );

            $this->test_data[ 'draft_products' ][ 'ids' ][] = $product->id;
        }
    }

    private function add_products_with_childs(){

        //add 3 products with child's in EN
        $this->test_data[ 'products_with_childs' ][ 'count' ] = 3;
        $this->test_data[ 'original_products_count' ] += $this->test_data[ 'products_with_childs' ][ 'count' ];
        for( $i=0; $i<$this->test_data[ 'products_with_childs' ][ 'count' ]; $i++ ){
            $product = $this->wcml_helper->add_product( 'en', false, 'TEST Product with Child '.$i );
            $this->test_data[ 'products' ][ $product->id ] = array( 'id' => $product->id, 'trid' => $product->trid, 'language' => 'en', 'translations' => array() );
            $child_product = $this->wcml_helper->add_product( 'en', false, 'Child Product for '.$product->id, $product->id );
            $this->test_data[ 'products' ][ $product->id ] = array( 'id' => $child_product->id, 'trid' => $child_product->trid, 'language' => 'en', 'translations' => array() );

            $this->test_data[ 'products_with_childs' ][ 'ids' ][] = $product->id;
        }

    }

    private function add_products_with_categories(){

        //add 3 products with category 1
        $cat = $this->wcml_helper->add_term( 'Category 1', 'product_cat', 'en' );
        $this->test_data[ 'terms' ][ 'product_cat' ][ $cat->term_id ] = array( 'id' => $cat->term_id, 'trid' => $cat->trid, 'language' => 'en' );
        $this->test_data[ 'products_with_categories' ][ $cat->term_id ][ 'count' ] = 3;
        $this->test_data[ 'original_products_count' ] += $this->test_data[ 'products_with_categories' ][ $cat->term_id ][ 'count' ];
        for( $i=0; $i<$this->test_data[ 'products_with_categories' ][ $cat->term_id ][ 'count' ]; $i++ ){
            $product = $this->wcml_helper->add_product( 'en', false, 'TEST Product with Category '.$cat->term_id );
            $this->test_data[ 'products' ][ $product->id ] = array( 'id' => $product->id, 'trid' => $product->trid, 'language' => 'en', 'translations' => array() );
            $this->wcml_helper->add_term( false, 'product_cat', 'en', $product->id, false, $cat->term_id );
            $this->test_data[ 'products_with_categories' ][ $cat->term_id ][ 'ids' ][] = $product->id;
        }

    }

    private function set_filter_values(){
        //set filter values
        $this->test_data[ 'filters' ][ 'source_languages' ] = array_merge( array( 'all' ), $this->languages );
        $this->test_data[ 'filters' ][ 'categories' ] = array_merge( array( 0 ), array_keys( $this->test_data[ 'terms' ][ 'product_cat' ] ) );
        $this->test_data[ 'filters' ][ 'translation_statuses' ] = array( 'all', 'not', 'need_update', 'in_progress', 'complete' );
        $this->test_data[ 'filters' ][ 'product_statuses' ] = array( 'all', 'publish', 'future', 'draft', 'pending', 'private' );
    }

    private function set_filter_combinations(){
        //set filter combinations to check ( key structure - *source_language*_*category*_*translation_status*_*product_status* )
        $this->test_data[ 'filters' ][ 'combinations' ] = array();
        $this->test_data[ 'filters' ][ 'combinations' ][ 'all_all_all_all' ] = $this->test_data[ 'original_products_count' ];
        $this->test_data[ 'filters' ][ 'combinations' ][ 'en_all_all_all' ] = $this->test_data[ 'products_count' ][ 'en' ];
        $this->test_data[ 'filters' ][ 'combinations' ][ 'en_all_all_publish' ] = $this->test_data[ 'original_products_count' ] - $this->test_data[ 'draft_products' ][ 'count' ] - $this->test_data[ 'products_count' ][ 'es' ];
        $this->test_data[ 'filters' ][ 'combinations' ][ 'es_all_all_all' ] = $this->test_data[ 'products_count' ][ 'es' ];
        foreach( $this->test_data[ 'products_with_categories' ] as $cat_id => $cat_wit_products ){
            $this->test_data[ 'filters' ][ 'combinations' ][ 'all_'.$cat_id.'_all_all' ] = $cat_wit_products[ 'count' ];
        }
        $this->test_data[ 'filters' ][ 'combinations' ][ 'all_all_all_draft' ] = $this->test_data[ 'draft_products' ][ 'count' ];
        $this->test_data[ 'filters' ][ 'combinations' ][ 'en_all_all_draft' ] = $this->test_data[ 'draft_products' ][ 'count' ];
        $this->test_data[ 'filters' ][ 'combinations' ][ 'es_all_all_draft' ] = 0;
        $this->test_data[ 'filters' ][ 'combinations' ][ 'all_all_all_publish' ] = $this->test_data[ 'original_products_count' ] - $this->test_data[ 'draft_products' ][ 'count' ];
    }

    //check drop-downs from filter at top
    private function check_filter_drop_downs( $dom ){
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
    }

    private function check_products_rows( $dom ){
        //check products table
        $products  = $dom->getElementsByTagName( 'tr' );
        // 1 tr uses for filter drop-downs and 20 products per page by default
        $this->assertEquals( 21, $products->length );

        $i = 0;
        foreach( $products as $product ){ $i++;
            //skip filters tr
            if( $i == 1 ) continue;

            $cols = $product->getElementsByTagName( 'td' );

            //test products tr row
            $this->check_product_images( $cols );

            $this->check_product_links( $cols );

            $this->check_links_to_translations( $cols );

            //categories list
            $this->assertNotEmpty( $cols->item(3) );
            //product type
            $this->assertNotEmpty( $cols->item(4) );
            //product date
            $this->assertNotEmpty( $cols->item(5) );
        }
    }

    private function check_product_images( $cols ){
        $imgs = $cols->item(0)->getElementsByTagName( 'img' );
        $this->assertNotEmpty( $imgs );
        $this->assertContains( '.png', $imgs->item(0)->getAttribute( 'src' ) );
    }

    private function check_product_links( $cols ){
        //check edit and view links
        $links = $cols->item(1)->getElementsByTagName('a');
        $this->assertNotEmpty( $links );
        //$this->assertContains( 'action=edit', $links->item(1)->getAttribute( 'href' ) );
        $this->assertContains( 'product', $links->item(2)->getAttribute( 'href' ) );
    }

    private function check_links_to_translations( $cols ){
        //links to translation editor pop-up
        $editor_links = $cols->item(2)->getElementsByTagName('a');
        $this->assertNotEmpty( $editor_links );
        // note for original language we don't have a link
        $this->assertEquals( count( $this->languages )-1, $editor_links->length );
        foreach( $editor_links as $editor_link ){
            $this->assertTrue( in_array( $editor_link->getAttribute( 'data-language' ), $this->languages ) );
        }
    }
    
    //check pagination
    private function check_pagination( $dom ){
        $current_page  = $dom->getElementById( 'current-page-selector' );
        $this->assertNotEmpty( $current_page );
        $this->assertEquals( 1, $current_page->getAttribute('value') );
    }
}