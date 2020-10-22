<?php

namespace WCML\Reports\Products;

/**
 * Class TestQuery
 * @package WCML\Reports\Products
 * @group wc-analytics
 */
class TestQuery extends \OTGS_TestCase {
	private function get_subject() {
		return new Query();
	}

	/**
	 * @test
	 */
	public function itRegistersHooks() {
		global $_SERVER;
		$_SERVER['REQUEST_URI'] = 'wc-analytics/';
		$subject = $this->get_subject();

		\WP_Mock::passthruFunction( 'trailingslashit' );
		\WP_Mock::userFunction( 'rest_get_url_prefix', [
			'return' => ''
		] );

		\WP_Mock::expectFilterAdded( 'woocommerce_analytics_products_select_query', [ $subject, 'joinProductTranslations' ], 10 );

		$subject->add_hooks();
	}

	// results data corrupted
	/**
	 * @test
	 *
	 * @dataProvider corruptedResultsData
	 */
	public function itReturnsResultsUnfilteredWhenDataCorrupted( $results ) {
		$subject = $this->get_subject();

		$filtered = $subject->joinProductTranslations( $results );

		$this->assertSame( $filtered, $results );
	}

	public function corruptedResultsData() {
		$results[] = [null];
		$results[] = [false];
		$results[] = [0];
		$results[] = ['foo'];
		$results[] = [ new \stdClass() ];

		$nodata = new \stdClass();
		$nodata->total = 3;
		$results[] = [ $nodata ];

		$dataNotArray = new \stdClass();
		$dataNotArray->data = 'foo';
		$results[] = [ $dataNotArray ];

		$dataEmptyArray = new \stdClass();
		$dataEmptyArray->data = [];
		$results[] = [ $dataEmptyArray ];

		$dataArrayWithNoCorrectFields = new \stdClass();
		$dataArrayWithNoCorrectFields->data = [ 'foo', 'bar' ];
		$results[] = [ $dataArrayWithNoCorrectFields ];

		$dataWithProductIdOnly = new \stdClass();
		$dataWithProductIdOnly->data = [
			[ 'product_id' => 3 ]
			];
		$results[] = [ $dataWithProductIdOnly ];

		return $results;
	}

	// correct results data
	/**
	 * @test
	 */
	public function itReturnsResultsReducedWhenDataCorrect() {
		$subject = $this->get_subject();

		$results = new \stdClass();
		$results->data = [
			[
				'product_id' => 1,
				'items_sold' => 2,
				'net_revenue' => 10.00,
				'orders_count' => 2
			],
			[
				'product_id' => 2,
				'items_sold' => 1,
				'net_revenue' => 5.00,
				'orders_count' => 1
			]
		];

		$expected = new \stdClass();
		$expected->data = [
			[
				'product_id' => 1,
				'items_sold' => 3,
				'net_revenue' => 15.00,
				'orders_count' => 3
			]
		];

		$origDetails = new \stdClass();
		$origDetails->language_code = 'en';
		$origDetails->element_id = 1;
		\WP_Mock::onFilter( 'wpml_element_language_details' )->with( null, [
			'element_id' => 1,
			'element_type' => 'product'
		] )->reply( $origDetails );

		$transDetails = new \stdClass();
		$transDetails->language_code = 'ru';
		$transDetails->element_id = 2;
		\WP_Mock::onFilter( 'wpml_element_language_details' )->with( null, [
			'element_id' => 2,
			'element_type' => 'product'
		] )->reply( $transDetails );

		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'en' );

		$filtered = $subject->joinProductTranslations( $results );

		$this->assertSame( $filtered->data, $results->data );
	}

}