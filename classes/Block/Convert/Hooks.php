<?php

namespace WCML\Block\Convert;

use IWPML_DIC_Action;
use IWPML_Frontend_Action;
use SitePress;

class Hooks implements IWPML_Frontend_Action, IWPML_DIC_Action {

	/** @var SitePress $sitepress */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'render_block_data', [ $this, 'filterAttributeIds' ] );
		add_action( 'parse_query', [ $this, 'addCurrentLangToQueryVars' ] );
	}

	public function filterAttributeIds( array $block ) {
		return Config::get( $block['blockName'] )->convert( $block );
	}

	/**
	 * WC is caching query results in transients which name
	 * is based on the query vars hash.
	 *
	 * @param \WP_Query $query
	 */
	public function addCurrentLangToQueryVars( $query ) {
		if ( $query instanceof \Automattic\WooCommerce\Blocks\Utils\BlocksWpQuery ) {
			$query->query_vars['wpml_language'] = $this->sitepress->get_current_language();
		}
	}
}
