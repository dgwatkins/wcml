<?php

namespace WCML\Attributes;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore as ProductAttributesLookupDataStore;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use WPML\FP\Fns;

class LookupTable implements \IWPML_Action {

	/** @var \SitePress $sitepress */
	private $sitepress;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		Hooks::onAction( 'save_post' )
			->then( spreadArgs( [ $this, 'triggerUpdateForTranslations' ] ) );

		// For defered updates, we remove terms filter just before the action scheduler.
		Hooks::onAction( 'woocommerce_run_product_attribute_lookup_update_callback', 5 )
			->then( [ $this, 'removeTermsClausesFilter' ] );

		// When regenerating the table we need all products and all terms.
		Hooks::onFilter( 'woocommerce_attribute_lookup_regeneration_step_size' )
			->then( spreadArgs( Fns::tap( [ $this, 'regenerateTable' ] ) ) );
	}

	/**
	 * @param int $productId
	 */
	public function triggerUpdateForTranslations( $productId ) {
		if (
			'product' === get_post_type( $productId )
			&& 'publish' === get_post_status( $productId )
			&& ! $this->sitepress->is_original_content_filter( false, $productId, 'post_product' )
		) {
			Hooks::onAction( 'shutdown' )
				->then( function() use ( $productId ) {
					// For direct updates, we remove the terms filter just before triggering the update.
					$hasTermsClausesFilter = $this->removeTermsClausesFilter();

					wc_get_container()->get( ProductAttributesLookupDataStore::class )->on_product_changed( $productId );

					if ( $hasTermsClausesFilter ) {
						$this->addTermsClausesFilter();
					}
				} );
		}
	}

	/**
	 * @return bool
	 */
	public function removeTermsClausesFilter() {
		return remove_filter( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ] );
	}

	public function addTermsClausesFilter() {
		add_filter( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ], 10, 3 );
	}

	public function regenerateTable() {
		$this->removeTermsClausesFilter();

		add_filter( 'woocommerce_product_object_query_args', Obj::assoc( 'suppress_filters', true ) );
	}

}
