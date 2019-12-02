<?php

namespace WCML\Block\Convert\Type;

abstract class Convertible implements Type {

	/** @var \SitePress $sitepress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @param array|int $ids
	 * @param string    $elementType
	 *
	 * @return array|int
	 */
	protected function convertIds( $ids, $elementType ) {
		$getTranslation = function( $id ) use ( $elementType ) {
			return (int) $this->sitepress->get_object_id( $id, $elementType );
		};

		if ( is_array( $ids ) ) {
			return wpml_collect( $ids )->map( $getTranslation )->toArray();
		} else {
			return wpml_collect( [ $ids ] )->map( $getTranslation )->first();
		}
	}
}
