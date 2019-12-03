<?php

namespace WCML\Block\Convert\Type;

class NonConvertible extends Convert{

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	public function convert( array $block ) {
		return $block;
	}
}
