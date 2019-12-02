<?php

namespace WCML\Block\Convert\Type;

interface Type {

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	public function convert( array $block );
}
