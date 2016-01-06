<?php

class Test_WCML_Strings extends WCML_UnitTestCase {


	function setUp(){
		parent::setUp();
		global $woocommerce_wpml;

		require_once WCML_PLUGIN_PATH . '/inc/wc-strings.class.php';
		$woocommerce_wpml->strings           = new WCML_WC_Strings;

	}

	function test_translate_attributes_label_in_wp_taxonomies() {
		global $wp_taxonomies, $sitepress, $WPML_String_Translation;
		$WPML_String_Translation->init_active_languages();

		$label = 'Test attr';
		$name = wc_attribute_taxonomy_name( $label );

		$taxonomy_data                  = array(
			'hierarchical'          => true,
			'update_count_callback' => '_update_post_term_count',
			'labels'                => array(
				'name'              => $label,
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


		$taxonomy_data['rewrite'] = array(
			'slug'         => empty( $permalinks['attribute_base'] ) ? '' : trailingslashit( $permalinks['attribute_base'] ) . sanitize_title( $label ),
			'with_front'   => false,
			'hierarchical' => true
		);

		do_action( 'wpml_register_single_string',  'WordPress', 'taxonomy singular name: '.$label, $label );

		$string_id = icl_get_string_id( $label, 'WordPress', 'taxonomy singular name: '.$label );

		icl_add_string_translation( $string_id, 'es', 'Test attr es', ICL_STRING_TRANSLATION_COMPLETE );

		$sitepress->switch_lang( 'es' );
		register_taxonomy( $name, apply_filters( "woocommerce_taxonomy_objects_{$name}", array( 'product' ) ), apply_filters( "woocommerce_taxonomy_args_{$name}", $taxonomy_data ) );

		$this->assertTrue( (bool) has_filter('wpml_translate_single_string') );
		$this->assertEquals( 'Test attr es', $wp_taxonomies[$name]->labels->name );
	}


}