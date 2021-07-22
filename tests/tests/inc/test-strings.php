<?php

class Test_WCML_Strings extends WCML_UnitTestCase {


	function setUp(){
		parent::setUp();
	}

	function test_translate_attribute_labels() {
		global $wp_taxonomies, $WPML_String_Translation, $woocommerce_wpml;

		$WPML_String_Translation->init_active_languages();

		$label = 'Test attr';
		$name = wc_attribute_taxonomy_name( $label );

		add_filter( 'woocommerce_attribute_taxonomies', function() use ( $label ) {
			$slug = wc_sanitize_taxonomy_name( $label );
			return [
				$slug => (object) [
					'attribute_id'    => '123',
					'attribute_label' => $label,
					'attribute_name'  => $slug,
				],
			];
		} );

		add_filter( 'woocommerce_taxonomy_args_' . $name, function( $args ) use ( $woocommerce_wpml, $label ) {
			return $woocommerce_wpml->strings->translate_attribute_labels( $args, $label );
		} );

		$taxonomy_data = array(
			'hierarchical'          => true,
			'update_count_callback' => '_update_post_term_count',
			'labels'                => array(
				'name'              => sprintf( __( 'Product %s', 'woocommerce' ), $label ),
				'singular_name'     => $label,
				'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
				'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
				'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
				'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
				'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
				'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
				'add_new_item'      => sprintf( __( 'Add New %s', 'woocommerce' ), $label ),
				'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label )
			),
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_nav_menus' => false,
			'meta_box_cb'       => false,
			'query_var'         => 1,
			'rewrite'           => false,
			'sort'              => false,
			'public'            => 1,
			'show_in_nav_menus' => 1,
			'capabilities'      => array(
				'manage_terms' => 'manage_product_terms',
				'edit_terms'   => 'edit_product_terms',
				'delete_terms' => 'delete_product_terms',
				'assign_terms' => 'assign_product_terms',
			)
		);

		do_action( 'wpml_register_single_string',  'WordPress', 'taxonomy singular name: ' . $label, $label );
		do_action( 'wpml_register_single_string',  'WordPress', 'taxonomy general name: Product ' . $label, $label );

		$string_id = icl_get_string_id( $label, 'WordPress', 'taxonomy singular name: '.$label );
		icl_add_string_translation( $string_id, 'es', 'Test attr es', ICL_TM_COMPLETE );

		$string_id = icl_get_string_id( $label, 'WordPress', 'taxonomy general name: Product '.$label );
		icl_add_string_translation( $string_id, 'es', 'Product test attr es', ICL_TM_COMPLETE );

		$WPML_String_Translation->clear_string_filter( 'es' );

		$this->sitepress->switch_lang( 'es' );
		register_taxonomy( $name, apply_filters( "woocommerce_taxonomy_objects_{$name}", array( 'product' ) ), apply_filters( "woocommerce_taxonomy_args_{$name}", $taxonomy_data ) );

		$this->assertTrue( (bool) has_filter('wpml_translate_single_string') );
		$this->assertEquals( 'Test attr es', $wp_taxonomies[$name]->labels->singular_name );
		$this->assertEquals( 'Product test attr es', $wp_taxonomies[$name]->labels->name );
	}


}