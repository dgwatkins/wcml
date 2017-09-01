<?php
/**
 * Class WCML_WC_Shortcode_Product_Category
 * @since 4.2.2
 */
class WCML_WC_Shortcode_Product_Category {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WCML_WC_Shortcode_Product_Category constructor.
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'woocommerce_shortcode_products_query', array( $this, 'translate_category' ), 10, 2 );
	}


	/**
	 * @param array $args
	 * @param array $atts
	 *
	 * @return array
	 */
	public function translate_category( $args, $atts ) {
		if ( isset( $atts['category'] ) ) {

			// Get translated category slugs, we need to remove WPML filter.
			$filter_exists = remove_filter( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10 );
			$categories    = get_terms( array( 'slug' => $atts['category'], 'taxonomy' => 'product_cat' ) );
			if ( $filter_exists ) {
				add_filter( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10, 4 );
			}

			// Replace slugs in query arguments.
			$terms = wp_list_pluck( $categories, 'slug' );
			foreach ( $args['tax_query'] as $i => $tax_query ) {
				$args['tax_query'][ $i ] = array();
				if ( ! is_int( key( $tax_query ) ) ) {
					$tax_query = array( $tax_query );
				}
				foreach ( $tax_query as $j => $condition ) {
					if ( 'product_cat' === $condition['taxonomy'] ) {
						$condition['terms'] = $terms;
					}
					$args['tax_query'][ $i ][] = $condition;
				}
			}
		}

		return $args;
	}


}
