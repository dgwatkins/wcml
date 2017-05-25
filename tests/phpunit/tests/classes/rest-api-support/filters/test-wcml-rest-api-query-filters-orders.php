<?php
/**
 * Class Test_WCML_REST_API_Query_Filters_Orders
 * @group wcml-1979
 */
class Test_WCML_REST_API_Query_Filters_Orders extends OTGS_TestCase {

	/** @var  wpdb */
	private $wpdb;
	/** @var array  */
	private $order_items = [];

	/** @var  array */
	private $test_data = [];

	function setUp(){
		parent::setUp();

		\WP_Mock::wpFunction( 'get_query_var', array(
			'return' => function ( $var ) {
				return isset( $this->test_data['query_var'][$var] ) ?
					$this->test_data['query_var'][$var] : null;
			},
		) );

		$this->wpdb = $this->getMockBuilder( 'wpdb' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( array( 'get_var', 'prepare' ) )
		                   ->getMock();
		$this->wpdb->method( 'get_var' )->will( $this->returnCallback(
			function( $id ){
				return $this->order_items[ $id ]['translated_id'];
			}
		) );
		$this->wpdb->method( 'prepare' )->will( $this->returnCallback(
			function( $query, $id ){
				return $id;
			}
		) );
		$this->wpdb->prefix = '';

	}

	function tearDown(){
		parent::tearDown();
		unset( $this->wpdb );
	}

	function get_subject(){
		return new WCML_REST_API_Query_Filters_Orders( $this->wpdb );
	}

	/**
	 * @test
	 */
	function test_add_hooks(){

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_shop_order_object_query', array( $subject, 'filter_orders_by_language'), 20, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_shop_order_object', array( $subject, 'filter_order_items_by_language'), 10, 3 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	function filter_orders_by_language(){

		$subject = $this->get_subject();

		$lang = 'ro';

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_param' ) )
		                 ->getMock();
		$request1->method( 'get_param' )->willReturn( $lang );

		$args = [ 'meta_query' => [] ];
		$args_add_expect = [ 'key'=> 'wpml_language', 'value' => $lang ];

		$args_out = $subject->filter_orders_by_language( $args, $request1 );

		$args_add_actual = array_pop( $args_out['meta_query'] );

		$this->assertEquals( $args_add_expect , $args_add_actual );

	}

	/**
	 * @test
	 */
	function filter_order_items_by_language(){

		$subject = $this->get_subject();

		$order = $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array(
			              'get_id'
		              ) )
		              ->getMock();
		$order->ID = rand(1,100);
		$order->method('get_id')->willReturn( $order->ID );

		$test_lang = 'ro';
		$other_lang = 'fr';

		// First translated post
		$post1 = new stdClass();
		$post1->post_title = 'Dummy Product Translated';
		$post1->post_type = 'product';
		$this->test_data['posts'][ 2323 ] = $post1;

		// Second translated post (variation)
		$post2 = new stdClass();
		$post2->post_title = 'Dummy Variation Translated';
		$post2->post_type = 'product_variation';
		$post2->post_parent = 1000;
		$this->test_data['posts'][ 4444 ] = $post2;

		// Parent of variation
		$post3 = new stdClass();
		$post3->post_title = 'Dummy Parent Product Translated';
		$post3->post_title = 'product';
		$this->test_data['posts'][ 1000 ] = $post3;


		$this->order_items = [
			15 =>
				[
					'item_id' => 15,
					'product_id' => 23,
					'product_name' => 'Dummy Product',
					'translated_id' => 2323,
					'translated_name' => $this->test_data['posts'][ 2323 ]->post_title,
				],
			99 =>
				[
					'item_id' => 99,
					'product_id' => 44,
					'product_name' => 'Dummy Parent Product',
					'translated_id' => 4444,
					'translated_name' => $this->test_data['posts'][ 1000 ]->post_title,
				],
		];

		$response = new stdClass();
		$response->data = [
			'line_items' => [
				0 => [
					'id' => $this->order_items['15']['item_id'],
					'product_id' => $this->order_items['15']['product_id'],
					'name' => $this->order_items['15']['product_name']
				],
				1 => [
					'id' => $this->order_items['99']['item_id'],
					'product_id' => $this->order_items['99']['product_id'],
					'name' => $this->order_items['99']['product_name']
				]
			]
		];

		$expected_response = new stdClass();
		$expected_response->data = [
			'line_items' => [
				0 => [
					'id' => $this->order_items['15']['item_id'],
					'product_id' => $this->order_items['15']['translated_id'],
					'name' => $this->order_items['15']['translated_name']
				],
				1 => [
					'id' => $this->order_items['99']['item_id'],
					'product_id' => $this->order_items['99']['translated_id'],
					'name' => $this->order_items['99']['translated_name']
				]
			]
		];

		update_post_meta( $order->ID,  'wpml_language', $other_lang);

		$request = null;


		\WP_Mock::wpFunction( 'get_post', array(
			'return' => function ( $id ) {
				if( isset( $this->test_data['posts'][ $id ] )){
					$post = $this->test_data['posts'][ $id ];
					// exception
					if( $id == 4444){
						$post_parent = get_post( $post->post_parent );
						$post->post_title = $post_parent->post_title;
					}
					return $post;
				}
			},
		) );


		// Another language - no filtering
		$this->test_data['query_var']['lang'] = $other_lang;
		$response_out = $subject->filter_order_items_by_language( $response, $order, $request );
		$this->assertEquals( $response, $response_out );


		// The right language
		$this->test_data['query_var']['lang'] = $test_lang;
		$response_out = $subject->filter_order_items_by_language( $response, $order, $request );
		$this->assertEquals( $expected_response, $response_out );

		// cleanup
		unset($this->test_data['query_var']['lang']);
		unset($this->order_items);

	}

}
