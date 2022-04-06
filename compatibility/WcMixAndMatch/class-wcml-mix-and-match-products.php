<?php
/**
 * Mix and Match Products compatibility class.
 *
 * @version 4.13.0
 */
class WCML_Mix_and_Match_Products implements \IWPML_Action {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress        = $sitepress;
	}

	/**
	 * Attach callbacks.
	 * 
	 * @since 4.13.0
	 */
	public function add_hooks() {
		add_action( 'wcml_after_duplicate_product_post_meta', [ $this, 'sync_mnm_data' ], 10, 2 );
		add_filter( 'wcml_cart_contents', [ $this, 'sync_mnm_cart' ], 10, 4 );

		// Currency switching.
		if ( wcml_is_multi_currency_on() ) {
			add_filter( 'wcml_price_custom_fields_filtered', [ $this, 'get_price_custom_fields' ], 10, 2 );
			add_filter( 'wcml_update_custom_prices_values', [ $this, 'update_container_custom_prices_values' ], 10, 2 );
			add_filter( 'wcml_after_save_custom_prices', [ $this, 'update_container_base_price' ], 10, 4 );
		}
		
	}
   
	/**
	 * Sync container data with translated values when the product is duplicated.
	 *
	 * @param int $container_id
	 * @param int $translated_container_id
	 */
	public function sync_mnm_data( $container_id, $translated_container_id ) {
	
		$original_data   = maybe_unserialize( get_post_meta( $container_id, '_mnm_data', true ) );
		$translated_data = [];
		$lang            = $this->sitepress->get_language_for_element( $translated_container_id, 'post_product' );
		
		if ( $original_data ) {
			foreach ( $original_data as $original_id => $data ) {
				
				if ( is_array( $data ) ) {
										
					$translated_child_id = $translated_product_id = apply_filters( 'translate_object_id', $data[ 'product_id' ] , 'product', false, $lang );
					$translated_variation_id = 0;	
					
					if ( ! empty( $data[ 'variation_id' ] ) ) {
						$translated_child_id = $translated_variation_id = apply_filters( 'translate_object_id', $data[ 'variation_id' ] , 'product_variation', false, $lang );			
					}
					
					$translated_data[ $translated_child_id ] = [
						'child_id'     => $translated_child_id,
						'product_id'   => $translated_product_id,
						'variation_id' => $translated_variation_id,
					];
					
				}
				
			}
		}

		update_post_meta( $translated_container_id, '_mnm_data', $translated_data );
    }
    
	/**
	 * Update the cart contents to the new language
	 * 
	 * @since 4.13.0
	 *
	 * @param array  $new_cart_contents
	 * @param array  $cart_contents
	 * @param string $key
	 * @param string $new_key
	 *
	 * @return array
	 */
	public function sync_mnm_cart( $new_cart_contents, $cart_contents, $key, $new_key ) {
		if ( ! function_exists( 'wc_mnm_is_container_cart_item' )
		     || ! function_exists( 'wc_mnm_get_child_cart_items' )
		     || ! function_exists( 'wc_mnm_maybe_is_child_cart_item' )
		     || ! function_exists( 'wc_mnm_get_cart_item_container' )
		) {
			return $new_cart_contents;
		}
		
		$current_language = $this->sitepress->get_current_language();

		// Translate container.
		if ( wc_mnm_is_container_cart_item( $new_cart_contents[ $new_key ] ) ) {
			
			$new_config = [];
					
			// Translate config.
			foreach( $new_cart_contents[ $new_key ][ 'mnm_config'] as $id => $data ) {
				
				$tr_product_id   = apply_filters( 'translate_object_id', $data['product_id'], 'product', false, $current_language );
				$tr_variation_id = 0;
				
				if ( isset( $data['variation_id'] ) && $data['variation_id'] ) {
					$tr_variation_id = apply_filters( 'translate_object_id', $data['variation_id'], 'product_variation', false, $current_language );
				}
				
				$tr_child_id = $tr_variation_id ? intval( $tr_variation_id ) : intval( $tr_product_id );
				
				$new_config[ $tr_child_id ] = [
					'mnm_child_id' => $tr_child_id,
					'product_id'   => intval( $tr_product_id ),
					'variation_id' => intval( $tr_variation_id ),
					'quantity'     => $data[ 'quantity' ],
					'variation'    => $data[ 'variation' ], // @todo: translate attributes
				];
				
			}
			
			if ( ! empty( $new_config ) ) {
				$new_cart_contents[ $new_key ][ 'mnm_config'] = $new_config;
			}
			
			// Find all children and stash new container cart key. Need to direclty manipulate the wc()->cart as $cart_contents isn't persisted.
			foreach ( wc_mnm_get_child_cart_items( $new_cart_contents[ $new_key ] ) as $child_key => $child_item ) {
				WC()->cart->cart_contents[ $child_key ][ 'translated_mnm_container' ] = $new_key;
			}
				
		}
			
		// Translate children.
		if ( wc_mnm_maybe_is_child_cart_item( $new_cart_contents[ $new_key ] ) ) {
					
			// Update the child's container and remove the stashed version.
			$new_cart_contents[ $new_key ][ 'mnm_container'] = $cart_contents[ $key ][ 'translated_mnm_container' ];
			unset( $cart_contents[ $key ][ 'translated_mnm_container'] );
			
			$container_key = wc_mnm_get_cart_item_container( $new_cart_contents[ $new_key ], $new_cart_contents, true );
					
			if ( $container_key ) {	
				
				// Swap keys in container's content array.
				$remove_key = array_search( $key, $new_cart_contents[ $container_key ][ 'mnm_contents'] );		
				unset( $new_cart_contents[ $container_key ][ 'mnm_contents'][ $remove_key ] );		
				$new_cart_contents[ $container_key ][ 'mnm_contents'][] = $new_key;
				
			}
			
		}
	
		return $new_cart_contents;
		
    }

    /**
	 * Add MNM price fields to list to be converted.
	 *
	 * @since 4.13.0
	 *
	 * @param array $custom_fields
     *
	 * @return array
	 */
	public function get_price_custom_fields( $custom_fields ) {

		return array_merge(
			$custom_fields,
			[
				'_mnm_base_regular_price',
				'_mnm_base_sale_price',
				'_mnm_base_price',
				'_mnm_max_price',
				'_mnm_max_regular_price',
			]
		);
	}


    /**
	 * Swap the base price for the custom price in that currency.
	 *
	 * @since 4.13.0
	 *
	 * @param array  $prices
	 * @param string $code
	 *
	 * @return array
	 */
	public function update_container_custom_prices_values( $prices, $code ) {

		foreach ( [
			          '_custom_regular_price' => '_mnm_base_regular_price',
			          '_custom_sale_price'    => '_mnm_base_sale_price',
		          ] as $wc_price => $custom_price ) {
			if ( isset( $_POST[ $wc_price ][ $code ] ) ) {
				$prices[ $custom_price ] = wc_format_decimal( $_POST[ $wc_price ][ $code ] );
			}
		}

		return $prices;

	}


    /**
	 * Save base price per currency.
	 *
	 * @since 4.13.0
	 *
	 * @param int    $post_id
	 * @param string $product_price
	 * @param array  $custom_prices
	 * @param string $code
	 */
	public function update_container_base_price( $post_id, $product_price, $custom_prices, $code ) {

		if ( isset( $custom_prices['_mnm_base_regular_price'] ) ) {
			update_post_meta( $post_id, '_mnm_base_price_' . $code, $product_price );
		}

    }
    
}
