<?php

namespace WCML\Block\Convert\Type;

class NonConvertible implements Type {

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	public function convert( array $block ) {
		return $block;
	}
}
