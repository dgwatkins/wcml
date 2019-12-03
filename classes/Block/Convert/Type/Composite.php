<?php

namespace WCML\Block\Convert\Type;

class Composite extends Convert {

	/** @var Convert[] $types */
	private $types;

	public function __construct( $types ) {
		$this->types = $types;
	}

	public function convert( array $block ) {
		foreach ( $this->types as $type ) {
			$block = $type->convert( $block );
		}

		return $block;
	}
}
