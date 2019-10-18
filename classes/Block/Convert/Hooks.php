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
		$attributesToConvert = Config::get( $block['blockName'] );

		foreach ( $attributesToConvert as $attributeConfig ) {

			if ( isset( $block['attrs'][ $attributeConfig['name'] ] ) ) {
				$block['attrs'][ $attributeConfig['name'] ] = $this->convertIds(
					$block['attrs'][ $attributeConfig['name'] ],
					$attributeConfig['type']
				);
			}
		}

		return $block;
	}

	/**
	 * @param array|int $ids
	 * @param string    $elementType
	 *
	 * @return array|int
	 */
	private function convertIds( $ids, $elementType ) {
		$getTranslation = function( $id ) use ( $elementType ) {
			return $this->sitepress->get_object_id( $id, $elementType );
		};

		if ( is_array( $ids ) ) {
			return wpml_collect( $ids )->map( $getTranslation )->toArray();
		} else {
			return wpml_collect( [ $ids ] )->map( $getTranslation )->first();
		}
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
