<?php

namespace WCML\Block\Convert\Type;

abstract class Convert {

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	abstract public function convert( array $block );

	/**
	 * @param array|int $ids
	 * @param string    $elementType
	 *
	 * @return array|int
	 */
	protected function convertIds( $ids, $elementType ) {
		$getTranslation = function( $id ) use ( $elementType ) {
			return (int) wpml_object_id_filter( $id, $elementType );
		};

		if ( is_array( $ids ) ) {
			return wpml_collect( $ids )->map( $getTranslation )->toArray();
		} else {
			return wpml_collect( [ $ids ] )->map( $getTranslation )->first();
		}
	}
}
