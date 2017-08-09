<?php

class WCML_Helper {

	private static $sitepress;
	private static $woocommerce_wpml;
	private static $wpdb;

	static function init( &$woocommerce_wpml, &$sitepress, &$wpdb ) {

		self::$sitepress        = $sitepress;
		self::$woocommerce_wpml = $woocommerce_wpml;
		self::$wpdb             = $wpdb;

		wpml_test_reg_custom_post_type( 'product' );
		$settings_helper = wpml_load_settings_helper();

		$settings_helper->set_post_type_translatable( 'product' );
		$settings_helper->set_post_type_translatable( 'product_variation' );
		$settings_helper->set_taxonomy_translatable( 'product_cat' );
		$settings_helper->set_taxonomy_translatable( 'product_tag' );
		$settings_helper->set_taxonomy_translatable( 'product_shipping_class' );

	}

	/*
	 * $arg - array of data to add:
	 * 'count' -> count of products to add, 'translations' - language code for translations to add
	 */
	public function add_products( $args ) {

		$dummy_data = array();

		if ( is_array( $args ) ) {
			$status_helper      = wpml_get_post_status_helper();

			foreach ( $args as $lang_code => $data ) {
				for ( $i = 0; $i < $data['count']; $i ++ ) {
					$product = $this->add_product( $lang_code, false, sprintf( 'Test Product: %d', $i ) );

					$dummy_data[ $product->id ]['id']       = $product->id;
					$dummy_data[ $product->id ]['trid']     = $product->trid;
					$dummy_data[ $product->id ]['language'] = $lang_code;

					if ( isset( $data['translations'] ) ) {
						foreach ( $data['translations'] as $trnsl_lang ) {
							$trnsl_product                              = $this->add_product( $trnsl_lang, $product->trid, sprintf( 'Test Product %s: %d', $trnsl_lang, $i ) );
							$dummy_data[ $product->id ]['translations'] = array( $trnsl_lang => $trnsl_product->id );

							if ( isset( $data['status'] ) ) {
								$status_helper->set_status( $trnsl_product->id, $data['status'] );
							}
						}
					}
				}
			}
		}

		return $dummy_data;
	}

	/*
	* $arg - array of data to add:
	* 'count' -> count of products to add, 'translations' - language code for translations to add
	*/
	public function add_dummy_terms( $args ) {

		$dummy_data = array();

		if ( is_array( $args ) ) {
			foreach ( $args as $lang_code => $data ) {
				for ( $i = 0; $i < $data['count']; $i ++ ) {
					$term = $this->add_term( sprintf( 'Test terms for %s: %d', $data['taxonomy'], $i ), $data['taxonomy'], $lang_code );

					$dummy_data[ $data['taxonomy'] ][ $term->term_id ]['id']       = $term->term_id;
					$dummy_data[ $data['taxonomy'] ][ $term->term_id ]['trid']     = $term->trid;
					$dummy_data[ $data['taxonomy'] ][ $term->term_id ]['language'] = $lang_code;

					if ( isset( $data['translations'] ) ) {
						foreach ( $data['translations'] as $trnsl_lang ) {
							$trnsl_term                                                        = $this->add_term( sprintf( 'Test terms for %s %d: %d', $data['taxonomy'], $trnsl_lang, $i ), $data['taxonomy'], $trnsl_lang, false, $term->trid );
							$dummy_data[ $data['taxonomy'] ][ $term->term_id ]['translations'] = array( $trnsl_lang => $trnsl_term->term_id );
						}
					}
				}
			}
		}

		return $dummy_data;

	}


	public static function add_product( $language, $trid = false, $title = false, $parent = 0, $meta = array() ) {
		global $wpml_post_translations;

		if ( ! $title ) {
			$title = 'Test Product ' . time() . rand( 1000, 9999 );
		}

		$product_id = wpml_test_insert_post( $language, 'product', $trid, $title, $parent );

		$default_meta = array(
			'_price'         => 10,
			'_regular_price' => 10,
			'_sale_price'    => '',
			'_sku'           => 'DUMMY SKU',
			'_manage-stock'  => 'no',
			'_tax_status'    => 'taxable',
			'_downloadable'  => 'no',
			'_virtual'       => 'taxable',
			'_visibility'    => 'visible',
			'_stock_status'  => 'instock'
		);

		foreach ( $default_meta as $key => $value ) {
			update_post_meta( $product_id, $key, isset( $meta[ $key ] ) ? $meta[ $key ] : $default_meta[ $key ] );
		}

		$ret = new stdClass();

		$ret->id   = $product_id;
		$ret->trid = ! $trid ? $wpml_post_translations->get_element_trid( $product_id, 'post_product' ) : $trid;

		return $ret;

	}

	public static function add_variable_product( $variation_data = array(), $trid = false, $language = false ) {
		global $wpml_post_translations;;

		$ret = new stdClass();

		if ( empty( $variation_data ) ) {
			self::register_attribute( 'color' );
			$white          = self::add_attribute_term( 'White', 'color', self::$sitepress->get_default_language() );
			$black          = self::add_attribute_term( 'Black', 'color', self::$sitepress->get_default_language() );
			$variation_data = array(
				'product_title' => 'Dummy Variable Product',
				'attribute'     => array(
					'name' => 'pa_color'
				),
				'variations'    => array(
					'white' => array(
						'price'   => 10,
						'regular' => 10
					),
					'black' => array(
						'price'   => 15,
						'regular' => 15
					)
				)
			);
		}

		if ( ! $language ) {
			$language = self::$sitepress->get_default_language();
		}

		foreach ( $variation_data['variations'] as $vp ) {
			if ( ! isset( $min_variation_price ) || $min_variation_price > $vp['price'] ) {
				$min_variation_price = $vp['price'];
			}
			if ( ! isset( $max_variation_price ) || $max_variation_price < $vp['price'] ) {
				$max_variation_price = $vp['price'];
			}
			if ( ! isset( $min_variation_regular_price ) || $min_variation_regular_price > $vp['regular'] ) {
				$min_variation_regular_price = $vp['regular'];
			}
			if ( ! isset( $max_variation_regular_price ) || $max_variation_regular_price < $vp['regular'] ) {
				$max_variation_regular_price = $vp['regular'];
			}

			if ( isset( $vp['sale'] ) ) {
				if ( ! isset( $min_variation_sale_price ) || $min_variation_sale_price > $vp['sale'] ) {
					$min_variation_sale_price = $vp['sale'];
				}
				if ( ! isset( $max_variation_sale_price ) || $max_variation_sale_price < $vp['sale'] ) {
					$max_variation_sale_price = $vp['sale'];
				}
			}


		}

		// Create the product
		$product_id = wpml_test_insert_post( $language, 'product', $trid, $variation_data['product_title'] );

		// Price related meta
		update_post_meta( $product_id, '_price', $min_variation_price );
		update_post_meta( $product_id, '_min_variation_price', $min_variation_price );
		update_post_meta( $product_id, '_max_variation_price', $max_variation_price );
		update_post_meta( $product_id, '_min_variation_regular_price', $min_variation_regular_price );
		update_post_meta( $product_id, '_max_variation_regular_price', $max_variation_regular_price );
		if ( isset( $min_variation_sale_price ) ) {
			update_post_meta( $product_id, '_min_variation_sale_price', $min_variation_sale_price );
		}
		if ( isset( $max_variation_sale_price ) ) {
			update_post_meta( $product_id, '_max_variation_sale_price', $max_variation_sale_price );
		}

		// General meta
		update_post_meta( $product_id, '_sku', 'DUMMY SKU' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_tax_status', 'taxable' );
		update_post_meta( $product_id, '_downloadable', 'no' );
		update_post_meta( $product_id, '_virtual', 'taxable' );
		update_post_meta( $product_id, '_visibility', 'visible' );
		update_post_meta( $product_id, '_stock_status', 'instock' );

		// Attributes
		update_post_meta( $product_id, '_default_attributes', array() );
		update_post_meta( $product_id, '_product_attributes', array(
			$variation_data['attribute']['name'] => array(
				'name'         => $variation_data['attribute']['name'],
				'value'        => '',
				'position'     => '1',
				'is_visible'   => 0,
				'is_variation' => 1,
				'is_taxonomy'  => 1
			)
		) );

		// Set Product Type
		$return['term_taxonomy_id'] = self::set_product_as_variable( $product_id );

		$ret->variations = array();
		// VARIATIONS
		foreach ( $variation_data['variations'] as $attribute_value => $variation ) {

			//get original variation trid to set relationship
			$var_trid = false;
			if ( $trid && isset( $variation['original_variation_id'] ) ) {
				$var_trid = $wpml_post_translations->get_element_trid( $variation['original_variation_id'], 'product_variation' );
			}

			// Create the variation
			$variation_id                     = wpml_test_insert_post( $language, 'product_variation', $var_trid, 'Variation #' . $attribute_value . ' of Dummy Product', $product_id );
			$ret->variations[ $variation_id ] = array(
				'variation_id' => $variation_id,
				'attr'         => $variation_data['attribute']['name'],
				'attr_value'   => $attribute_value
			);

			// Price related meta
			update_post_meta( $variation_id, '_price', $variation['price'] );
			update_post_meta( $variation_id, '_regular_price', $variation['regular'] );
			if ( isset( $variation['sale'] ) ) {
				update_post_meta( $variation_id, '_sale_price', $variation['sale'] );
			}

			// General meta
			update_post_meta( $variation_id, '_sku', 'DUMMY SKU VARIABLE ' . $attribute_value );
			update_post_meta( $variation_id, '_manage_stock', 'no' );
			update_post_meta( $variation_id, '_downloadable', 'no' );
			update_post_meta( $variation_id, '_virtual', 'taxable' );
			update_post_meta( $variation_id, '_stock_status', 'instock' );

			// Attribute meta
			update_post_meta( $variation_id, 'attribute_' . $variation_data['attribute']['name'], $attribute_value );

			// Add the variation meta to the main product
			if ( $variation['price'] == $max_variation_price ) {
				update_post_meta( $product_id, '_max_price_variation_id', $variation_id );
			} elseif ( $variation['price'] == $min_variation_price ) {
				update_post_meta( $product_id, '_min_price_variation_id', $variation_id );
			}
			if ( $variation['price'] == $max_variation_regular_price ) {
				update_post_meta( $product_id, '_max_regular_price_variation_id', $variation_id );
			} elseif ( $variation['price'] == $min_variation_regular_price ) {
				update_post_meta( $product_id, '_min_regular_price_variation_id', $variation_id );
			}
			if ( isset( $max_variation_sale_price ) && $variation['price'] == $max_variation_sale_price ) {
				update_post_meta( $product_id, '_max_sale_price_variation_id', $variation_id );
			} elseif ( isset( $min_variation_sale_price ) && $variation['price'] == $min_variation_sale_price ) {
				update_post_meta( $product_id, '_min_sale_price_variation_id', $variation_id );
			}

			// Link the product to the attribute
			$attribute_data = get_term_by( 'name', $attribute_value, $variation_data['attribute']['name'] );

			if ( ! is_object( $attribute_data ) ) {
				global $wpdb;
				$attribute_data = $wpdb->get_row( $wpdb->prepare( "
	                SELECT * FROM {$wpdb->terms} t 
	                JOIN {$wpdb->term_taxonomy} x ON t.term_id = x.term_id
	                  WHERE t.name=%s and x.taxonomy=%s
	            ", $attribute_value, $variation_data['attribute']['name'] ) );
			}

			self::$wpdb->insert( self::$wpdb->prefix . 'term_relationships', array(
				'object_id'        => $product_id,
				'term_taxonomy_id' => $attribute_data->term_taxonomy_id,
				'term_order'       => 0
			) );

		}


		$ret->id   = $product_id;
		$ret->trid = ! $trid ? $wpml_post_translations->get_element_trid( $product_id, 'post_product' ) : $trid;

		return $ret;
	}

	public static function set_product_as_variable( $product_id ) {
		$variable_type = get_term_by( 'name', 'variable', 'product_type' );
		self::$wpdb->insert( self::$wpdb->prefix . 'term_relationships', array(
			'object_id'        => $product_id,
			'term_taxonomy_id' => $variable_type->term_taxonomy_id,
			'term_order'       => 0
		) );

		return self::$wpdb->insert_id;
	}

	public static function add_term( $name, $taxonomy, $language, $product_id = false, $trid = false, $term_id = false ) {
		global $wpml_term_translations;

		if ( ! $term_id ) {
			$new_term = wpml_test_insert_term( $language, $taxonomy, $trid, $name );
			$term_id  = $new_term['term_id'];
		}

		$term = get_term( $term_id, $taxonomy );

		if ( $product_id ) {
			wp_set_post_terms(
				$product_id,
				array( $term_id ),
				$taxonomy,
				true
			);
		}

		$term->trid = $wpml_term_translations->get_element_trid( $term->term_taxonomy_id );

		return $term;

	}

	public static function add_product_variation( $language, $trid = false, $product_id = 0 ) {
		global $wpml_post_translations;

		if ( ! $product_id ) {
			$product_id = wpml_test_insert_post( $language, 'product' );
		}

		$product_id = wpml_test_insert_post( $language, 'product_variation', $trid, 'Variation ' . time() . rand( 1000, 9999 ), $product_id );

		$ret = new stdClass();

		$ret->id   = $product_id;
		$ret->trid = $wpml_post_translations->get_element_trid( $product_id, 'post_product' );

		return $ret;

	}

	public static function register_attribute( $name ) {

		$taxonomy = 'pa_' . $name;
		wpml_test_reg_custom_taxonomy( $taxonomy );

		// Create attribute
		$attribute = array(
			'attribute_label'   => $name,
			'attribute_name'    => $name,
			'attribute_type'    => 'select',
			'attribute_orderby' => 'menu_order',
			'attribute_public'  => 0,
		);
		self::$wpdb->insert( self::$wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

		delete_transient( 'wc_attribute_taxonomies' );

		$settings_helper = wpml_load_settings_helper();
		$settings_helper->set_taxonomy_translatable( $taxonomy );
	}

	public static function add_attribute_term( $term, $attr_name, $language, $trid = false ) {
		global $wpml_term_translations;

		$term         = wpml_test_insert_term( $language, 'pa_' . $attr_name, $trid, $term );
		$term['trid'] = $wpml_term_translations->get_element_trid( $term['term_taxonomy_id'] );

		return $term;

	}

	public static function add_local_attribute( $product_id, $name, $values ) {
		$orig_attrs = array(
			sanitize_title( $name ) =>
				array(
					'name'        => $name,
					'value'       => $values,
					'is_taxonomy' => 0
				)
		);
		add_post_meta( $product_id, '_product_attributes', $orig_attrs );


	}

	public static function update_product( $product_data ) {
		global $wpml_post_translations;

		wp_update_post( $product_data );

	}

	public static function icl_clear_and_init_cache( $language ) {
		global $WPML_String_Translation, $st_gettext_hooks;
		$WPML_String_Translation->clear_string_filter( $language );

		icl_cache_clear();
		wp_cache_init();

		if ( null !== $st_gettext_hooks ) {
			$st_gettext_hooks->clear_filters();
		}

	}

	public static function set_custom_field_to_translate( $name ) {

		self::$sitepress->core_tm()->settings['custom_fields_translation'][ $name ] = WPML_TRANSLATE_CUSTOM_FIELD;
		self::$sitepress->core_tm()->save_settings();
	}

	public static function set_custom_field_to_copy( $name ) {

		self::$sitepress->core_tm()->settings['custom_fields_translation'][ $name ] = WPML_COPY_CUSTOM_FIELD;
		self::$sitepress->core_tm()->save_settings();
	}

}