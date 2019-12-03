<?php

namespace WCML\Block\Convert\Type;

class WithIdTagAttributes extends Convert {

	private $attributesToConvert;

	public function __construct( array $attributesToConvert ) {
		$this->attributesToConvert = $attributesToConvert;
	}

	public function convert( array $block ) {
		$dom = new \DOMDocument();
		$dom->loadHTML( $block['innerHTML'] );
		$xpath = new \DOMXPath( $dom );
		$domHandler = new \WPML\PB\Gutenberg\StringsInBlock\DOMHandler\StandardBlock();

		foreach ( $this->attributesToConvert as $attributeConfig ) {
			$nodes = $xpath->query( $attributeConfig['xpath'] );

			if ( ! $nodes ) {
				continue;
			}

			foreach ( $nodes as $node ) {
				/** @var \DOMNode $node */
				$ids = $this->convertIds( explode( ',', $node->nodeValue ), $attributeConfig['type'] );
				$domHandler->setElementValue( $node, implode( ',', $ids ) );
			}
		}
		list( $block['innerHTML'], ) = $domHandler->getFullInnerHTML( $dom->documentElement );

		return $block;
	}
}
