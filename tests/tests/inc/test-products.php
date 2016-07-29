<?php

class Test_WCML_Products extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;

	function setUp() {
		parent::setUp();
		global $wpml_post_translations, $wpml_term_translations;

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
		//add product for tests
		$orig_product = $this->wcml_helper->add_product( $this->default_language, false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( $this->second_language, $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;

		//add global attribute for tests
		$attr = 'size';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'medium', $attr, $this->default_language );
		$es_term = $this->wcml_helper->add_attribute_term( 'medio', $attr, $this->second_language, $term['trid'] );

		$this->test_data = array();

	}

	function test_sync_parent_products_transients() {
		global $pagenow;

		$pagenow = 'post-new.php';

		$parent_product = $this->wcml_helper->add_product( $this->default_language, false, 'Parent Product EN' );
		$child_product = $this->wcml_helper->add_product( $this->default_language, false, 'Child Product EN', $parent_product->id );


		$parent_product_es = $this->wcml_helper->add_product( $this->second_language, $parent_product->trid, 'Parent Product ES' );
		$child_product_es = $this->wcml_helper->add_product( $this->second_language, $child_product->trid, 'Child Product ES', $parent_product_es->id );


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

		$this->woocommerce_wpml->sync_product_data->sync_linked_products( $child_product->id, $child_product_es->id, $this->second_language );

		$grouped_es = new WC_Product_Grouped( $parent_product_es->id ); //need to reinstantiate

		$this->assertEquals( array(), $grouped_es->get_children() );

	}

	function test_wc_product_duplication(){
		$wc_duplicate_class = new WC_Admin_Duplicate_Product();

		$this->add_products_to_duplicate();

		foreach( $this->test_data as $product ) {

			$post = get_post($product['id']);
			if (!empty($post)) {
				$new_id = $wc_duplicate_class->duplicate_product($post);
				$duplicated_products = $this->woocommerce_wpml->sync_product_data->woocommerce_duplicate_product($new_id, $post);
				//check duplicated products
				$new_trid = $this->sitepress->get_element_trid($duplicated_products['original'], 'post_' . get_post_type($duplicated_products['original']));
				foreach ($duplicated_products['translations'] as $product_id) {
					$trnsl_trid = $this->sitepress->get_element_trid($product_id, 'post_' . get_post_type($product_id));
					$this->assertEquals($new_trid, $trnsl_trid);
					$this->assertEquals(get_post_status($duplicated_products['original']), get_post_status($product_id));
				}

				//check duplicated variations
				if ($children_products = get_children('post_parent=' . $duplicated_products['original'] . '&post_type=product_variation')) {
					$orig_var_trids = array();

					foreach ($children_products as $child) {
						$var_trid = $this->sitepress->get_element_trid($child->ID, 'post_product_variation');
						if ($var_trid) {
							$orig_var_trids[] = $var_trid;
						}
					}

					foreach ($duplicated_products['translations'] as $product_id) {
						$this->woocommerce_wpml->sync_variations_data->sync_product_variations( $duplicated_products['original'], $product_id, $this->sitepress->get_language_for_element( $product_id, 'post_product' ) );
						if ($children_products = get_children('post_parent=' . $product_id . '&post_type=product_variation')) {
							$transl_trids = array();
							foreach ($children_products as $child) {
								$var_trid = $this->sitepress->get_element_trid($child->ID, 'post_product_variation');

								if ($var_trid) {
									$transl_trids[] = $var_trid;
								}
							}

							$this->assertEquals( $orig_var_trids, $transl_trids );
						}
					}
				}
			}
		}
	}

	private function add_products_to_duplicate(){

		//add simple product
		$product = $this->wcml_helper->add_product( $this->default_language, false, 'TEST simple product' );
		$product_es = $this->wcml_helper->add_product( $this->second_language, $product->trid, 'TEST simple product ES' );
		$this->test_data[ $product->id ][ 'id' ] = $product->id;
		$this->test_data[ $product->id ][ 'trnsl_id' ] = $product_es->id;

		//add draft product
		$product = $this->wcml_helper->add_product( $this->default_language, false, 'TEST Draft product' );
		$draft = array(
			'ID'			=> $product->id,
			'post_status'	=> 'draft'
		);
		$this->wcml_helper->update_product( $draft );
		$product_es = $this->wcml_helper->add_product( $this->second_language, $product->trid, 'TEST Draft product ES' );
		$draft = array(
			'ID'			=> $product_es->id,
			'post_status'	=> 'draft'
		);
		$this->wcml_helper->update_product( $draft );
		$this->test_data[ $product->id ][ 'id' ] = $product->id;
		$this->test_data[ $product->id ][ 'trnsl_id' ] = $product_es->id;

		//product with children
		$parent_product = $this->wcml_helper->add_product( $this->default_language, false, 'Parent Product EN' );
		$child_product = $this->wcml_helper->add_product( $this->default_language, false, 'Child Product EN', $parent_product->id );
		$this->test_data[ $product->id ][ 'id' ] = $parent_product->id;

		//variable product
		$variation_data = array(
			'product_title' => 'Test var product',
			'attribute' => array(
				'name' => 'pa_size'
			),
			'variations' => array(
				'medium' => array(
					'price'     => 10,
					'regular'   => 10
				)
			)
		);
		$product = $this->wcml_helper->add_variable_product( $variation_data, false);
		$variation_data = array(
			'product_title' => 'Test var product es',
			'attribute' => array(
				'name' => 'pa_size'
			),
			'variations' => array(
				'medio' => array(
					'price'     => 10,
					'regular'   => 10
				)
			)
		);
		$product_es = $this->wcml_helper->add_variable_product( $variation_data, $product->trid, $this->second_language );
		$this->test_data[ $product->id ][ 'id' ] = $product->id;
		$this->test_data[ $product->id ][ 'trnsl_id' ] = $product_es->id;

	}

}