<?php

namespace WCML\Email\OrderItems;

/**
 * @group email
 * @group email-order-items
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dpShouldAddHooks
	 *
	 * @param array $_get
	 */
	public function itShouldAddHooks( $_get ) {
		$_GET = $_get;

		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'woocommerce_order_items_meta_get_formatted', [ $subject, 'filterFormattedItems' ], 10, 2 );

		$subject->add_hooks();

		unset( $_GET );
	}

	public function dpShouldAddHooks() {
		return [
			'no GET var'                                  => [ [] ],
			'post type not shop_order'                    => [ [ 'post_type' => 'some-post-type' ] ],
			'action not woocommerce_mark_order_status'    => [ [ 'action' => 'some-action' ] ],
			'post type and action not matching blacklist' => [ [ 'post_type' => 'some-post-type', 'action' => 'some-action' ] ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpShouldNotAddHooks
	 *
	 * @param array $_get
	 */
	public function itShouldNotAddHooks( $_get ) {
		$_GET = $_get;

		$subject = $this->getSubject();

		\WP_Mock::expectFilterNotAdded( 'woocommerce_order_items_meta_get_formatted', [ $subject, 'filterFormattedItems' ], 10, 2 );

		$subject->add_hooks();

		unset( $_GET );
	}

	public function dpShouldNotAddHooks() {
		return [
			'post type is shop_order'                     => [ [ 'post_type' => 'shop_order' ] ],
			'action is woocommerce_mark_order_status'     => [ [ 'action' => 'woocommerce_mark_order_status' ] ],
			'post type and action are matching blacklist' => [ [ 'post_type' => 'shop_order', 'action' => 'woocommerce_mark_order_status' ] ],
		];
	}

	/**
	 * @test
	 */
	public function itShouldFilterFormattedItems() {
		$currentLang               = 'fr';
		$productId                 = 123;
		$variationId               = 456;
		$currentProductVariationId = 789;
		$term                      = $this->getWpTerm( 963, 'foo', 'bar' );
		$translatedTerm            = $this->getWpTerm( 1369, 'TRANSLATED foo', 'bar' );
		$customAttrTranslation     = [ 'name' => 'TRANSLATED color' ];

		$formattedMeta = [
			'my-taxonomy'  => [ 'key' => $term->taxonomy, 'value' => $term->name ],
			'my-attribute' => [ 'key' => 'color', 'value' => 'blue' ],
		];

		$expectedFormattedMeta = [
			'my-taxonomy'  => [ 'key' => $term->taxonomy, 'value' => $translatedTerm->name ],
			'my-attribute' => [ 'key' => 'color', 'value' => 'blue', 'label' => $customAttrTranslation['name'] ],
		];

		$object = (object) [
			'product' => (object) [
				'id'           => $productId,
				'variation_id' => $variationId,
			]
		];

		\WP_Mock::userFunction( 'wc_sanitize_taxonomy_name', [
			'times'      => 2,
			'return_arg' => true,
		] );

		\WP_Mock::userFunction( 'taxonomy_exists', [
			'args'   => [ $formattedMeta['my-taxonomy']['key'] ],
			'return' => true,
		] );

		\WP_Mock::userFunction( 'taxonomy_exists', [
			'args'   => [ $formattedMeta['my-attribute']['key'] ],
			'return' => false,
		] );

		\WP_Mock::userFunction( 'get_term_by', [
			'args'   => [ 'name', $term->name, $term->taxonomy ],
			'return' => $term,
		] );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_object_id' )
			->willReturnMap(
				[
					[ $variationId, 'product_variation', $currentProductVariationId ],
					[ $term->term_id, $term->taxonomy, $translatedTerm->term_id ],
				]
			);
		$sitepress->method( 'get_current_language' )->willReturn( $currentLang );

		$wcmlTerms = $this->getWcmlTerms();
		$wcmlTerms->method( 'wcml_get_term_by_id' )
			->with( $translatedTerm->term_id, $formattedMeta['my-taxonomy']['key'] )
			->willReturn( $translatedTerm );

		$wcmlAttributes = $this->getWcmlAttributes();
		$wcmlAttributes->method( 'get_custom_attribute_translation' )
			->with( $object->product->id, $formattedMeta['my-attribute']['key'], [ 'is_taxonomy' => false ], $currentLang )
			->willReturn( $customAttrTranslation );

		$subject = $this->getSubject( $sitepress, $wcmlTerms, $wcmlAttributes );

		$this->assertEquals(
			$expectedFormattedMeta,
			$subject->filterFormattedItems( $formattedMeta, $object )
		);
	}

	private function getSubject( $sitepress = null, $wcmlTerms = null, $wcmlAttributes = null ) {
		$sitepress      = $sitepress ?: $this->getSitepress();
		$wcmlTerms      = $wcmlTerms ?: $this->getWcmlTerms();
		$wcmlAttributes = $wcmlAttributes ?: $this->getWcmlAttributes();

		return new Hooks( $sitepress, $wcmlTerms, $wcmlAttributes );
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'get_object_id', 'get_current_language' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getWcmlTerms() {
		return $this->getMockBuilder( \WCML_Terms::class )
			->setMethods( [ 'wcml_get_term_by_id' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getWcmlAttributes() {
		return $this->getMockBuilder( \WCML_Attributes::class )
			->setMethods( [ 'get_custom_attribute_translation' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getWpTerm( $termId, $name, $taxonomy ) {
		$term = $this->getMockBuilder( '\WP_Term' )
			->disableOriginalConstructor()->getMock();
		$term->term_id  = $termId;
		$term->name     = $name;
		$term->taxonomy = $taxonomy;

		return $term;
	}
}
