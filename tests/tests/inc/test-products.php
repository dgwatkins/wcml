<?php

class Test_WCML_Products extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
		global $wpml_post_translations, $wpml_term_translations;

		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;

		//add global attribute for tests
		$attr = 'size';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'medium', $attr, 'en' );
		$es_term = $this->wcml_helper->add_attribute_term( 'medio', $attr, 'es', $term['trid'] );

	}

	function test_sync_parent_products_transients(){
		global $pagenow;

		$pagenow = 'post-new.php';

		$parent_product = $this->wcml_helper->add_product( 'en', false, 'Parent Product EN' );
		$child_product = $this->wcml_helper->add_product( 'en', false, 'Child Product EN', $parent_product->id );


		$parent_product_es = $this->wcml_helper->add_product( 'es', $parent_product->trid, 'Parent Product ES' );
		$child_product_es = $this->wcml_helper->add_product( 'es', $child_product->trid, 'Child Product ES', $parent_product_es->id );


		$grouped_es = new WC_Product_Grouped($parent_product_es->id);
		$this->assertEquals(array( $child_product_es->id ), $grouped_es->get_children());


		// Setting the child status to private should reset the children list transient for translated parent
		$child = array(
			'ID'			=> $child_product->id,
			'post_title' 	=> 'Child Product EN MADE PRIVATE',
			'post_status'	=> 'private',
		);
		$this->wcml_helper->update_product( $child );

		// FORCE status on translated child - should be synced autoamtically
		$child_es = array(
			'ID'			=> $child_product_es->id,
			'post_title' 	=> 'Child Product ES MADE PRIVATE',
			'post_status'	=> 'private',
		);
		$this->wcml_helper->update_product( $child_es );

		$this->woocommerce_wpml->sync_product_data->sync_linked_products( $child_product->id, $child_product_es->id, 'es' );

		$grouped_es = new WC_Product_Grouped( $parent_product_es->id ); //need to reinstantiate

		$this->assertEquals( array(), $grouped_es->get_children() );

	}

}