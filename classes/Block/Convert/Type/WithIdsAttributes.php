<?php

namespace WCML\Block\Convert\Type;

class WithIdsAttributes extends Convertible {

	private $attributesToConvert;

	public function __construct( \SitePress $sitepress, array $attributesToConvert ) {
		parent::__construct( $sitepress );
		$this->attributesToConvert = $attributesToConvert;
	}

	public function convert( array $block ) {
		foreach ( $this->attributesToConvert as $attributeConfig ) {

			if ( isset( $block['attrs'][ $attributeConfig['name'] ] ) ) {
				$block['attrs'][ $attributeConfig['name'] ] = $this->convertIds(
					$block['attrs'][ $attributeConfig['name'] ],
					$attributeConfig['type']
				);
			}
		}

		return $block;
	}
}
