<?php
/**
 * Mix and Match Products compatibility class.
 *
 * @version 5.0.0
 */
class WCML_Mix_And_Match_Products implements \IWPML_Action {

    /**
	 * An array of translated cart keys.
	 *
	 * @var array
	 */
	private $translated_cart_keys;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Attach callbacks.
	 *
	 * @since 5.0.0
	 */
	public function add_hooks() {
		// Support MNM 2.0 custom tables, cart syncing.
		if ( is_callable( [ 'WC_MNM_Compatibility', 'is_db_version_gte' ] ) && WC_MNM_Compatibility::is_db_version_gte( '2.0' ) ) {
			add_action( 'wcml_after_sync_product_data', [ $this, 'sync_allowed_contents' ], 10, 2 );
			add_filter( 'wcml_translate_cart_item', [ $this, 'translate_cart_item' ], 10, 2 );
            add_action( 'wcml_translated_cart_item', array( $this, 'translated_cart_item' ), 10, 2 );
            add_filter( 'wcml_translate_cart_contents', [ $this, 'sync_cart' ], 10, 2 );
		} else {
			add_action( 'updated_post_meta', [ $this, 'sync_mnm_data' ], 10, 4 );
		}
	}

	/**
	 * Translate container data with translated values when the product is duplicated.
	 *
	 * Handles translating source products to the MNM custom table.
	 * Handles translating source categories as meta.
	 *
	 * @param int $container_id
	 * @param int $translated_container_id
	 */
	public function sync_allowed_contents( $container_id, $translated_container_id ) {

		if ( has_term( 'mix-and-match', 'product_type', $container_id ) ) {

			$translated_child_items = [];
			$lang                   = $this->sitepress->get_language_for_element( $translated_container_id, 'post_product' );

			$original_product   = wc_get_product( $container_id );
			$translated_product = wc_get_product( $translated_container_id );

			if ( $original_product ) {

				$original_child_items = $original_product->get_child_items( 'edit' );

				$translated_child_items = [];

				// Translate child items.
				if ( ! empty( $original_child_items ) ) {

					foreach ( $original_child_items as $item_key => $original_child_item ) {

						$translated_child_items[] = [
							'product_id'   => apply_filters( 'wpml_object_id', $original_child_item->get_product_id(), 'product', false, $lang ),
							'variation_id' => apply_filters( 'wpml_object_id', $original_child_item->get_variation_id(), 'product_variation', false, $lang ),
						];

					}
				}

				if ( $translated_product && ! empty( $translated_child_items ) ) {
					$translated_product->set_child_items( $translated_child_items );
				}

				// Translate child source categories.
				$original_child_cat_ids   = $original_product->get_child_category_ids( 'edit' );
				$translated_child_cat_ids = [];

				foreach ( $original_child_cat_ids as $original_cat_id ) {
					$translated_child_cat_ids[] = apply_filters( 'wpml_object_id', $original_cat_id, 'product_cat', true, $lang );
				}

				if ( $translated_product && ! empty( $translated_child_cat_ids ) ) {
					$translated_product->set_child_category_ids( $translated_child_cat_ids );
				}

				// Save product.
				$translated_product->save();

			}
		}

	}

	/**
	 * Update the container config to the new language
	 *
	 * @since 5.1.0
	 *
	 * @param array  $cart_item Cart item.
     * @param string $current_language Language code.
	 *
	 * @return array
	 */
	public function translate_cart_item( $cart_item, $current_language ) {

		// Translate container.
		if ( wc_mnm_is_container_cart_item( $cart_item ) ) {

			$new_config = [];

			// Translate config.
			foreach ( $cart_item['mnm_config'] as $id => $data ) {

				$tr_product_id   = apply_filters( 'wpml_object_id', $data['product_id'], 'product', false, $current_language );
				$tr_variation_id = 0;

				if ( ! empty( $data['variation_id'] ) ) {
					$tr_variation_id = apply_filters( 'wpml_object_id', $data['variation_id'], 'product_variation', false, $current_language );
				}

				$tr_child_id = $tr_variation_id ? intval( $tr_variation_id ) : intval( $tr_product_id );

				$new_config[ $tr_child_id ] = [
					'mnm_child_id' => $tr_child_id,
					'product_id'   => intval( $tr_product_id ),
					'variation_id' => intval( $tr_variation_id ),
					'quantity'     => $data['quantity'],
					'variation'    => $data['variation'], // @todo: translate attributes
				];

			}

			if ( ! empty( $new_config ) ) {
				$cart_item['mnm_config'] = $new_config;
			}

            // Stash the content keys for later. Cannot translate now as the child products have not yet been translated.
            if ( isset( $cart_item['mnm_contents'] ) ) {
                $cart_item['mnm_contents_tr'] = $cart_item['mnm_contents'];
                $cart_item['mnm_contents']    = array();
            }

		} else if ( wc_mnm_maybe_is_child_cart_item( $cart_item ) ) {
            if ( isset( $cart_item['mnm_container'], $this->translated_cart_keys[ $cart_item[ 'mnm_container' ] ] ) ) {
                $cart_item['mnm_container'] = $this->translated_cart_keys[ $cart_item[ 'mnm_container' ] ];
            }
        }

		return $cart_item;
	}


    /**
	 * Stores new cart keys as function of previous values.
	 * Later needed to restore the relationship between the Mix and Match product and contained products.
	 * Hooked to the action 'pllwc_translated_cart_item'.
	 *
	 * @since 5.1
	 *
	 * @param array  $item Cart item.
	 * @param string $key  Previous cart item key. The new key can be found in $item['key'].
	 * @return void
	 */
	public function translated_cart_item( $item, $key ) {
		$this->translated_cart_keys[ $key ] = $item['key'];
	}


	/**
	 * Re-sync the parent/child relationships
	 *
	 * @since 5.0.0
	 *
	 * @param array  $cart_contents Cart items.
     * @param string $current_language Language code.
	 *
	 * @return array
	 */
	public function sync_cart( $cart_contents, $current_language ) {

        if ( is_array( $this->translated_cart_keys ) && ! empty( $this->translated_cart_keys ) ) {

            foreach ( $cart_contents as $key => $cart_item ) {

                // Translate container.
                if ( wc_mnm_is_container_cart_item( $cart_item ) && ! empty( $cart_item[ 'mnm_contents_tr' ] ) ) {
                    $tr_contents = array_unique( array_keys( array_intersect( array_flip( $this->translated_cart_keys ), $cart_item[ 'mnm_contents_tr' ] ) ) );
                    $cart_contents[ $key ][ 'mnm_contents' ] = $tr_contents;
                    unset( $cart_contents[ $key ][ 'mnm_contents_tr' ] );
                }

            }

        }

		return $cart_contents;
	}

    /**
	 * Allows WooCommerce Mix and Match to filter the cart prices after the cart has been translated.
	 * We need to do it here as WooCommerce Mix and Match directly access to WC()->cart->cart_contents.
	 * Hooked to the action 'woocommerce_cart_loaded_from_session'.
	 *
	 * @since 5.1.0
	 */
	public function cart_loaded_from_session() {
		foreach ( WC()->cart->cart_contents as $key => $cart_item ) {
			if ( ! empty( $cart_item['data'] ) ) {
				WC()->cart->cart_contents[ $key ] = WC_Mix_and_Match()->cart->add_cart_item_filter( $cart_item, $key );
			}
		}
	}

	/**
	 * Translate the _mnm_data meta of child products.
	 *
	 * For Mix and Match 1.x data.
	 *
	 * @param string $meta_id
	 * @param int    $post_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function sync_mnm_data( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( '_mnm_data' !== $meta_key ) {
			return;
		}

		global $sitepress, $woocommerce_wpml;

		$post = get_post( $post_id );

		// Skip auto-drafts, skip autosave.
		if ( 'auto-draft' === $post->post_status || isset( $_POST['autosave'] ) ) {
			return;
		}

		if ( 'product' === $post->post_type ) {
			remove_action( 'updated_post_meta', [ $this, 'sync_mnm_data' ], 10 );

			if ( $woocommerce_wpml->products->is_original_product( $post_id ) ) {
				$original_product_id = $post_id;
			} else {
				$original_product_id = $woocommerce_wpml->products->get_original_product_id( $post_id );
			}

			$mnm_data             = maybe_unserialize( get_post_meta( $original_product_id, '_mnm_data', true ) );
			$product_trid         = $sitepress->get_element_trid( $original_product_id, 'post_product' );
			$product_translations = $sitepress->get_element_translations( $product_trid, 'post_product' );

			foreach ( $product_translations as $product_translation ) {
				if ( empty( $product_translation->original ) ) {
					foreach ( $mnm_data as $key => $mnm_element ) {

                        if ( empty( $mnm_element ) ) {
                            
                            $mnm_data[ $tr_id ] = apply_filters( 'translate_object_id', $key, 'product', true, $product_translation->language_code );
                        
                        } else {
                            $trnsl_prod_id = ! empty( $mnm_element[ 'product_id' ] ) ? apply_filters( 'translate_object_id', $mnm_element[ 'product_id' ], 'product', true, $product_translation->language_code ) : 0;

                            $trsnl_var_id = ! empty( $mnm_element[ 'variation_id' ] ) ? apply_filters( 'translate_object_id', $mnm_element[ 'variation_id' ], 'product_variation', true, $product_translation->language_code ) : 0;

                            $mnm_element[ 'child_id' ]     = $trsnl_var_id ? $trsnl_var_id : $trnsl_prod;
                            $mnm_element[ 'product_id' ]   = $trnsl_prod;
                            $mnm_element[ 'variation_id' ] = $trnsl_prod;

                            
                            $mnm_data[ $mnm_element[ 'child_id' ] ]   = $mnm_element;

                        }

						unset( $mnm_data[ $key ] );
					}

					update_post_meta( $product_translation->element_id, '_mnm_data', $mnm_data );
				}
			}

			add_action( 'updated_post_meta', [ $this, 'sync_mnm_data' ], 10, 4 );
		}
	}

}
