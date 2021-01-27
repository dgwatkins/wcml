<?php

namespace WCML\Rest\Wrapper\Orders;

use stdClass;

/**
 * @group rest
 * @group rest-orders
 */
class TestLanguages extends \OTGS_TestCase {


	function get_subject() {
		return new Languages();
	}


	/**
	 * @test
	 */
	function filter_orders_by_language() {

		$subject = $this->get_subject();

		$lang = 'ro';

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_param' ] )
		                ->getMock();
		$request->method( 'get_param' )->willReturn( $lang );

		$args            = [ 'meta_query' => [] ];
		$args_add_expect = [ 'key' => 'wpml_language', 'value' => $lang ];

		$args_out = $subject->query( $args, $request );

		$args_add_actual = array_pop( $args_out['meta_query'] );

		$this->assertEquals( $args_add_expect, $args_add_actual );

	}

	/**
	 * @test
	 * @dataProvider OrderData
	 */
	function filter_order_items_by_language( $object ) {

		$subject = $this->get_subject();

		$test_lang  = 'ro';
		$other_lang = 'fr';

		// First translated post
		$post1                          = new stdClass();
		$post1->post_title              = 'Dummy Product Translated';
		$post1->post_type               = 'product';
		$this->test_data['posts'][2323] = $post1;

		// Second translated post (variation)
		$post2                          = new stdClass();
		$post2->post_title              = 'Dummy Variation Translated';
		$post2->post_type               = 'product_variation';
		$post2->post_parent             = 1000;
		$this->test_data['posts'][4444] = $post2;

		// Parent of variation
		$post3                          = new stdClass();
		$post3->post_title              = 'Dummy Parent Product Translated';
		$post3->post_title              = 'product';
		$this->test_data['posts'][1000] = $post3;


		$this->order_items = [
			15 =>
				[
					'item_id'         => 15,
					'product_id'      => 23,
					'product_name'    => 'Dummy Product',
					'translated_id'   => 2323,
					'translated_name' => $this->test_data['posts'][2323]->post_title,
				],
			99 =>
				[
					'item_id'         => 99,
					'product_id'      => 44,
					'product_name'    => 'Dummy Parent Product',
					'translated_id'   => 4444,
					'translated_name' => $this->test_data['posts'][1000]->post_title,
				],
		];

		$response       = $this->getMockBuilder( 'WP_REST_Response' )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$response->data = [
			'line_items' => [
				0 => [
					'id'         => $this->order_items['15']['item_id'],
					'product_id' => $this->order_items['15']['product_id'],
					'name'       => $this->order_items['15']['product_name']
				],
				1 => [
					'id'         => $this->order_items['99']['item_id'],
					'product_id' => $this->order_items['99']['product_id'],
					'name'       => $this->order_items['99']['product_name']
				]
			]
		];

		$expected_response       = $this->getMockBuilder( 'WP_REST_Response' )
		                                ->disableOriginalConstructor()
		                                ->getMock();
		$expected_response->data = [
			'line_items' => [
				0 => [
					'id'         => $this->order_items['15']['item_id'],
					'product_id' => $this->order_items['15']['translated_id'],
					'name'       => $this->order_items['15']['translated_name']
				],
				1 => [
					'id'         => $this->order_items['99']['item_id'],
					'product_id' => $this->order_items['99']['translated_id'],
					'name'       => $this->order_items['99']['translated_name']
				]
			]
		];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 10, 'wpml_language', true ],
			'return' => $other_lang
		] );


		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->getMock();

		\WP_Mock::userFunction( 'get_post', [
			'return' => function ( $id ) {
				if ( isset( $this->test_data['posts'][ $id ] ) ) {
					$post = $this->test_data['posts'][ $id ];
					// exception
					if ( $id == 4444 ) {
						$post_parent      = get_post( $post->post_parent );
						$post->post_title = $post_parent->post_title;
					}

					return $post;
				}
			}
		] );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'return' => function ( $id ) {
				foreach ( $this->order_items as $item ) {
					if ( $id === $item['product_id'] ) {
						return $item['translated_id'];
					}
				}
			}
		] );

		\WP_Mock::userFunction( 'get_query_var', [
			'return' => function ( $var ) {
				return isset( $this->test_data['query_var'][ $var ] ) ?
					$this->test_data['query_var'][ $var ] : null;
			}
		] );


		// Another language - no filtering
		$this->test_data['query_var']['lang'] = $other_lang;
		$response_out                         = $subject->prepare( $response, $object, $request );
		$this->assertEquals( $response, $response_out );


		// The right language
		$this->test_data['query_var']['lang'] = $test_lang;
		$response_out                         = $subject->prepare( $response, $object, $request );
		$this->assertEquals( $expected_response, $response_out );

		// cleanup
		unset( $this->test_data['query_var']['lang'] );
		unset( $this->order_items );

	}

	public function OrderData() {
		$WC_Order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->setMethods( [
				'get_id'
			] )
			->getMock();
		$WC_Order->method( 'get_id' )->willReturn( 10 );

		$WP_Post = $this->getMockBuilder( 'WP_Post' )
			->disableOriginalConstructor()
			->getMock();
		$WP_Post->ID = 10;

		return [
			[ $WC_Order ],
			[ $WP_Post ]
		];
	}

	/**
	 * @test
	 */
	public function prepare_throws_exception_when_can_NOT_get_order_id() {
		\WP_Mock::userFunction( 'get_query_var', [
			'return' => function ( $var ) {
				return isset( $this->test_data['query_var'][ $var ] ) ?
					$this->test_data['query_var'][ $var ] : null;
			}
		] );

		$subject = $this->get_subject();
		$order = 'foo';
		$this->expectExceptionMessage( 'Order has no ID set.' );
		$response_out = $subject->prepare( null, $order, null );
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	function set_order_language_exception() {
		$subject = $this->get_subject();

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => 'de'
		] );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, 'de' )->reply( false );

		$subject->insert( $post, $request1, true );

	}

	/**
	 * @test
	 */
	function set_order_language() {

		$expected_language = 'ro';
		$request1          = $this->getMockBuilder( 'WP_REST_Request' )
		                          ->disableOriginalConstructor()
		                          ->setMethods( [ 'get_params' ] )
		                          ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => $expected_language
		] );

		$post = $this->getMockBuilder( 'WC_Order' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'   => [ $post->ID, 'wpml_language', $expected_language ],
			'times'  => 1,
			'return' => true,
		] );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, $expected_language )->reply( true );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );
	}

}
