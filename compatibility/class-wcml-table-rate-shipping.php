<?php

/**
 * Class WCML_Table_Rate_Shipping
 */
class WCML_Table_Rate_Shipping {

	/**
	 * @var SitePress
	 */
	public $sitepress;

	/**
	 * @var woocommerce_wpml
	 */
	public $woocommerce_wpml;

	/**
	 * WCML_Table_Rate_Shipping constructor.
	 *
	 * @param $sitepress
	 * @param $woocommerce_wpml
	 */
	function __construct( &$sitepress, &$woocommerce_wpml ) {
		$this->sitepress = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		add_action( 'init', array( $this, 'init' ), 9 );
		add_filter( 'get_the_terms',array( $this, 'shipping_class_id_in_default_language' ), 10, 3 );

	}

	/**
	 * Register shipping labels for string translation.
	 */
	public function init() {
		// Register shipping label
		if ( isset( $_GET['page'] ) && 'shipping_zones' === $_GET['page'] && isset( $_POST['shipping_label'] ) && isset( $_POST['woocommerce_table_rate_title'] ) ) {
			do_action( 'wpml_register_single_string', 'woocommerce', sanitize_text_field( $_POST['woocommerce_table_rate_title'] ) . '_shipping_method_title', sanitize_text_field( $_POST['woocommerce_table_rate_title'] ) );
			$shipping_labels = array_map( 'woocommerce_clean', $_POST['shipping_label'] );
			foreach ( $shipping_labels as $shipping_label ) {
				do_action( 'wpml_register_single_string', 'woocommerce', $shipping_label . '_shipping_method_title', $shipping_label );
			}
		}
	}

	/**
	 * @param $terms
	 * @param $post_id
	 * @param $taxonomy
	 *
	 * @return mixed
	 */
	public function shipping_class_id_in_default_language( $terms, $post_id, $taxonomy ) {
		global $icl_adjust_id_url_filter_off;
		if ( 'product_shipping_class' === $taxonomy ) {

			foreach ( $terms as $k => $term ) {
				$shipping_class_id = apply_filters( 'translate_object_id', $term->term_id, 'product_shipping_class', false, $this->sitepress->get_default_language() );

				$icl_adjust_id_url_filter = $icl_adjust_id_url_filter_off;
				$icl_adjust_id_url_filter_off = true;

				$terms[ $k ] = get_term( $shipping_class_id,  'product_shipping_class' );

				$icl_adjust_id_url_filter_off = $icl_adjust_id_url_filter;
			}
		}

		return $terms;
	}

}
