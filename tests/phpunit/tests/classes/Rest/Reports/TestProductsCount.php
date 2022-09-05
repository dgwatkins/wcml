<?php

namespace WCML\Rest\Wrapper;

use WCML\Rest\Wrapper\Reports\ProductsCount;

/**
 * @group rest
 * @group rest-reports
 */
class TestProductsCount extends \OTGS_TestCase {

	/** @var SitePress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( \WPML\Core\ISitePress::class )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'is_active_language'
		                        ] )
		                        ->getMock();

		$this->wpdb = $this->stubs->wpdb();
	}


	function get_subject() {
		return new ProductsCount( $this->sitepress, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function filter_products_count() {

		$term                   = new \stdClass();
		$term->term_taxonomy_id = 2;
		$object                 = new \stdClass();
		$object->slug           = 'simple';
		$count                  = 10;
		$language               = 'es';

		\WP_Mock::userFunction( 'get_term_by', [
			'args'   => [ 'slug', $object->slug, 'product_type' ],
			'return' => $term
		] );

		$query = $this->wpdb->prepare( "SELECT count( p.ID ) FROM {$this->wpdb->posts} as p 
						LEFT JOIN {$this->wpdb->term_relationships} as tr ON p.ID = tr.object_id 
						LEFT JOIN {$this->wpdb->prefix}icl_translations as icl ON p.ID = icl.element_id 
						WHERE tr.term_taxonomy_id = %d AND icl.language_code = %s AND icl.element_type = 'post_product'",
				$term->term_taxonomy_id,
				$language
		);

		$this->wpdb->method( 'get_var' )->with( $query )->willReturn( $count );
		$this->sitepress->method( 'is_active_language' )->with( $language )->willReturn( true );

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => $language ] );

		$response = $this->getMockBuilder( 'WP_REST_Response' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_data', 'set_data' ] )
		                 ->getMock();

		$response->method( 'get_data' )->willReturn( [ 'total' => 5 ] );
		$response->method( 'set_data' )->with( [ 'total' => $count, 'lang' => $language ] )->willReturn( true );

		$this->assertEquals( $response, $subject->prepare( $response, $object, $request ) );
	}

}
