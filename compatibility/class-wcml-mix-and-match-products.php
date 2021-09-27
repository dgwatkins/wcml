<?php
/**
 * Mix and Match Products compatibility class.
 *
 * @version 4.13.0
 */

class WCML_Mix_and_Match_Products {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * constructor.
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress        = $sitepress;
	}

	/**
	 * Attach callbacks.
	 */
	public function add_hooks() {
		add_action( 'wcml_after_duplicate_product_post_meta', [ $this, 'sync_mnm_data' ], 10, 2 );
		add_filter( 'wcml_cart_contents', [ $this, 'sync_mnm_cart' ], 10, 4 );
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
	 * @param $cart_item array
	 *
	 * @param array $cart_item
	 * @param string $new_key
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
        
}
