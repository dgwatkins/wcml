<?php


class WCML_Custom_Taxonomy_Translation_UI extends WPML_Templates_Factory {

	private $woocommerce_wpml;

	private $custom_taxonomies = array();
	private $product_builtin_taxonomy_names = array( 'product_cat', 'product_tag', 'product_shipping_class', 'product_type' ); //'product_type' is used for tags (?)


	public function __construct( &$woocommerce_wpml ){
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;

		$product_attributes = array();
		$attributes = wc_get_attribute_taxonomies();
		foreach( $attributes as $attribute ){
			$product_attributes[ 'pa_' . $attribute->attribute_name ] = $attribute;
		}

		$product_taxonomies = get_object_taxonomies( 'product', 'objects' );
		foreach( $product_taxonomies as $product_taxonomy_name => $product_taxonomy_object ){
			if(
				!isset( $product_attributes[$product_taxonomy_name] ) &&
				!in_array( $product_taxonomy_name, $this->product_builtin_taxonomy_names ) &&
				is_taxonomy_translated( $product_taxonomy_name )
			){
				$this->custom_taxonomies[$product_taxonomy_name] = $product_taxonomy_object;
			}
		}

	}

	public function get_model() {

		$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : false;

		if( $this->custom_taxonomies ){
			if( !empty($taxonomy) ){
				foreach( $this->custom_taxonomies as $taxonomy_name => $taxonomy_object ){
					if( $taxonomy_name == $taxonomy ){
						$selected_taxonomy = $taxonomy_object;
						break;
					}
				}
			}
			if( empty( $selected_taxonomy ) ){
				$selected_taxonomy = current( $this->custom_taxonomies );
			}
		}else{
			$selected_taxonomy = false;
		}

		$WPML_Translate_Taxonomy =
			new WPML_Taxonomy_Translation(
				isset( $selected_taxonomy->name ) ? $selected_taxonomy->name: '',
				array(
					'taxonomy_selector'=> false
				)
			);
		ob_start();
		$WPML_Translate_Taxonomy->render();
		$translation_ui = ob_get_contents();
		ob_end_clean();

		$model = array(

			'taxonomies' => $this->custom_taxonomies,
			'selected_taxonomy' => $selected_taxonomy,
			'strings' => array(
				'no_taxonomies' => __( 'There are no translatable product custom taxonomies defined', 'woocommerce-multilingual' ),
				'select_label'	=> __('Select the taxonomy to translate: ', 'woocommerce-multilingual'),
				'loading'       => __( 'Loading ...', 'woocommerce-multilingual' )
			),
			'translation_ui' => $translation_ui

		);

		return $model;
	}



	public function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/',
		);
	}

	public function get_template() {
		return 'custom-taxonomy-translation.twig';
	}
}