<?php

class WCML_Taxonomy_Translation_Link_Filters{

	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;

	public function __construct( $woocommerce_wpml ) {
		$this->woocommerce_wpml =& $woocommerce_wpml;
	}

	public function add_filters(){
		add_filter( 'wpml_taxonomy_term_translation_url', array( $this, 'get_filtered_url' ), 10, 2 );
	}

	/**
	 * @param string $url
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	public function get_filtered_url( $url, $taxonomy = '' ){

		$built_in_taxonomies = array( 'product_cat', 'product_tag', 'product_shipping_class' );
		if( in_array( $taxonomy, $built_in_taxonomies ) ){

			$url = admin_url( 'admin.php?page=wpml-wcml&tab=' . $taxonomy );

		} else {

			$attributes = $this->woocommerce_wpml->attributes->get_translatable_attributes();
			$translatable_attributes = array();
			foreach( $attributes as $attribute ){
				$translatable_attributes[] = 'pa_' . $attribute->attribute_name;
			}

			if( in_array( $taxonomy, $translatable_attributes ) ) {

				$url = admin_url( 'admin.php?page=wpml-wcml&tab=product-attributs&taxonomy=' . $taxonomy );

			}else{

				$custom_taxonomies = get_object_taxonomies( 'product', 'objects' );

				$translatable_taxonomies = array();
				foreach( $custom_taxonomies as $product_taxonomy_name => $product_taxonomy_object ){
					if( is_taxonomy_translated( $product_taxonomy_name ) ){
						$translatable_taxonomies[] = $product_taxonomy_name;
					}
				}

				if( in_array( $taxonomy, $translatable_taxonomies ) ){

					$url = admin_url( 'admin.php?page=wpml-wcml&tab=custom-taxonomies&taxonomy=' . $taxonomy );

				}


			}


		}


		return $url;
	}

}