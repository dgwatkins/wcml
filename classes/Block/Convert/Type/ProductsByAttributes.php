<?php

namespace WCML\Block\Convert\Type;

class ProductsByAttributes extends \WPML\PB\Gutenberg\ConvertIdsInBlock\Base {

	public function convert( array $block ) {
		if ( ! isset( $block['attrs']['attributes'] ) ) {
			return $block;
		}

		foreach ( $block['attrs']['attributes'] as $key => $attribute ) {
			if ( ! isset( $attribute['id'], $attribute['attr_slug'] ) ) {
				continue;
			}

			$block['attrs']['attributes'][ $key ]['id'] = $this->convertIds( $attribute['id'], $attribute['attr_slug'] );
		}

		return $block;
	}
}
