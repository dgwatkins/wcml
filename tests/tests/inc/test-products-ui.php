<?php

class Test_WCML_Products_UI extends WCML_UnitTestCase {


    function setUp(){
        parent::setUp();

        global $sitepress;


        $this->wcml_products = new WCML_Products();

        // create 10 dummy products
        for($i = 0; $i < 10; $i++){
            $product = WCML_Helper::add_product(sprintf('Test Product: %d', $i), 'en');
            $trid_map[$i] = $product->trid;
        }

        //add translations
        for($i = 0; $i<10; $i++) {
            $product = WCML_Helper::add_product(sprintf('Test Product ES: %d', $i) , 'es', $trid_map[$i]);
        }

        //add 10 dummy products in ES
        for($i = 0; $i<10; $i++) {
            $product = WCML_Helper::add_product(sprintf('Test Product ES ORIGINAL: %d', $i) , 'es');
        }


    }


    function test_wcml_products_get_product_list() {
        global $sitepress;

        $en_products = $this->wcml_products->get_product_list(1, 5, 'en');
        $es_products = $this->wcml_products->get_product_list(3, 4, 'es');

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



}