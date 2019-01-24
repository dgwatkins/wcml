<?php

class Test_WCML_Products extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;

	function setUp() {
		parent::setUp();
		global $wpml_post_translations;

		set_current_screen( 'admin' );

		$this->woocommerce_wpml->sync_product_data =
			new WCML_Synchronize_Product_Data( $this->woocommerce_wpml, $this->sitepress, $wpml_post_translations, $this->wpdb );
		$this->woocommerce_wpml->sync_variations_data =
			new WCML_Synchronize_Variations_Data( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';		

	}

	function test_sync_parent_products_transients() {
		global $pagenow;

		$pagenow = 'post-new.php';

		$parent_product = $this->wcml_helper->add_product( $this->default_language, false, 'Parent Product EN' );
		$child_product = $this->wcml_helper->add_product( $this->default_language, false, 'Child Product EN', $parent_product->id );


		$parent_product_es = $this->wcml_helper->add_product( $this->second_language, $parent_product->trid, 'Parent Product ES' );
		$child_product_es = $this->wcml_helper->add_product( $this->second_language, $child_product->trid, 'Child Product ES', $parent_product_es->id );


		$grouped_es = new WC_Product_Grouped();
		$grouped_es->set_children( $child_product_es->id );
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

		$this->woocommerce_wpml->sync_product_data->sync_linked_products( $child_product->id, $child_product_es->id, $this->second_language );

		$grouped_es = new WC_Product_Grouped( $parent_product_es->id ); //need to reinstantiate

		$this->assertEquals( array(), $grouped_es->get_children() );

	}

}