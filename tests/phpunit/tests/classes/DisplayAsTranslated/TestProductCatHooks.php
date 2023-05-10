<?php

namespace WCML\DisplayAsTranslated;


use WPML\LIB\WP\OnActionMock;

/**
 * @group display-as-translated
 */
class TestProductCatHooks extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldNotFilterTerms() {
		$terms = [
			$this->getWpTerm( 123, 0 ),
			$this->getWpTerm( 456, 3 ),
			$this->getWpTerm( 789, 5 ),
		];

		$this->getSubject()->add_hooks();

		$this->assertSame(
			$terms,
			$this->runFilter( 'get_terms', $terms, 'some_taxonomy', [ 'foo' => 'bar' ] )
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldFilterTermsWithCountZero() {
		$filteredTtId = 123;
		$sourceTtId   = 99;
		$sourceCount  = 7;

		$terms = [
			$this->getWpTerm( $filteredTtId, 0 ),
			$this->getWpTerm( 456, 3 ),
			$this->getWpTerm( 789, 5 ),
		];

		$expectedTerms = [
			$this->getWpTerm( $filteredTtId, $sourceCount ),
			$this->getWpTerm( 456, 3 ),
			$this->getWpTerm( 789, 5 ),
		];

		$elementFactory = $this->getElementFactory( [
			[ $filteredTtId, $this->getTermElement( $filteredTtId, 0, $sourceTtId, $sourceCount ) ],
		] );

		$this->getSubject( $elementFactory )->add_hooks();

		$termQueryVars = $this->runFilter( 'woocommerce_product_subcategories_args', [ 'foo' => 'bar' ] );

		$this->assertEquals(
			$expectedTerms,
			$this->runFilter( 'get_terms', $terms, 'some_taxonomy', $termQueryVars )
		);
	}

	/**
	 * @param \WPML_Translation_Element_Factory&\PHPUnit\Framework\MockObject\MockObject|null $elementFactory
	 *
	 * @return ProductCatHooks
	 */
	private function getSubject( $elementFactory = null ) {
		$elementFactory = $elementFactory ?: $this->getElementFactory();

		return new ProductCatHooks( $elementFactory );
	}

	/**
	 * @param array $returnMap
	 *
	 * @return \WPML_Translation_Element_Factory&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getElementFactory( $returnMap = [] ) {
		$factory = $this->getMockBuilder( \WPML_Translation_Element_Factory::class )
			->setMethods( [ 'create_term' ] )
			->getMock();

		$factory->method( 'create_term' )->willReturnMap( $returnMap );

		return $factory;
	}

	/**
	 * @param int      $ttId
	 * @param int      $count
	 * @param int|null $sourceTtId
	 * @param int|null $sourceCount
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|(\WPML_Term_Element&\PHPUnit\Framework\MockObject\MockObject)
	 */
	private function getTermElement( $ttId, $count, $sourceTtId = null, $sourceCount = null ) {
		$element = $this->getMockBuilder( \WPML_Term_Element::class )
			->setMethods( [ 'get_source_element', 'get_wp_object' ] )
			->getMock();

		if ( $sourceTtId ) {
			$element->method( 'get_source_element' )
				->willReturn( $this->getTermElement( $sourceTtId, $sourceCount ) );
		}

		$element->method( 'get_wp_object' )->willReturn( $this->getWpTerm( $ttId, $count ) );

		return $element;
	}

	/**
	 * @param int $ttId
	 * @param int $count
	 *
	 * @return \WP_Term&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getWpTerm( $ttId, $count ) {
		$wpTerm                   = $this->getMockBuilder( \WP_Term::class )->getMock();
		$wpTerm->term_taxonomy_id = $ttId;
		$wpTerm->count            = $count;

		return $wpTerm;
	}
}
