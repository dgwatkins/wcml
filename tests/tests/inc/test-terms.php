<?php

class Test_WCML_Terms extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();

		$args = array();
		//add 2 product categories
		$args[ 'en' ] = array( 'count' => 2, 'taxonomy' => 'product_cat', 'translations' => array( 'es' ) );
		$this->terms = $this->wcml_helper->add_dummy_terms( $args );
	}

	function test_screen_notice(){
		$_GET['taxonomy'] = 'product_cat';
		$_GET['lang'] = 'es';
		ob_start();
		$this->woocommerce_wpml->terms->show_term_translation_screen_notices();
		$html = ob_get_contents();
		ob_end_clean();
		$this->assertContains( 'page=wpml-wcml&tab=product_cat' , $html );

	}

	function test_sync_term_order_globally(){

		$i = 2;
		foreach( $this->terms['product_cat'] as $key => $term ){
			add_woocommerce_term_meta( $term['id'], 'order', $i );
			$this->terms['product_cat'][$key]['order'] = $i;
			$i++;
		}

		$this->woocommerce_wpml->terms->sync_term_order_globally();

		foreach( $this->terms['product_cat'] as $term ){
			$translations = $this->sitepress->get_element_translations( $term['trid'], 'tax_product_cat' );
			foreach( $translations as $translation ){
				if( $translation->original ){
					$term_order = get_woocommerce_term_meta( $translation->term_id,'order' );
					$this->assertEquals( $term['order'], $term_order );
				}
			}
		}

	}

	function test_sync_term_order(){

		$_POST['thetaxonomy'] = 'product_cat';
		$i = 2;
		foreach( $this->terms['product_cat'] as $key => $term ){
			update_term_meta( $term['id'], 'order', $i );

			$translations = $this->sitepress->get_element_translations( $term['trid'], 'tax_product_cat' );
			foreach( $translations as $translation ){
				if( $translation->original ){
					$term_order = get_term_meta( $translation->term_id,'order', true );
					$this->assertEquals( $i, $term_order );
				}
			}

			$i++;
		}

	}

}