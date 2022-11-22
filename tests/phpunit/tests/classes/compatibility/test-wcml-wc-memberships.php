<?php

/**
 * Class Test_WCML_WC_Memberships
 */
class Test_WCML_WC_Memberships extends OTGS_TestCase {

	public function tearDown() {
		global $post;
		unset( $post );
	}

	private function get_subject( $wp_api = null ) {
		if ( null === $wp_api ) {
			$wp_api = $this->get_wp_api();
		}

		return new WCML_WC_Memberships( $wp_api );
	}

	private function get_wp_api() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'constant' ] )
		            ->getMock();;
	}

	/**
	 * @test
	 */
	public function add_hooks() {

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'parse_request', array( $subject, 'adjust_query_vars' ) );
		\WP_Mock::expectFilterAdded( 'wcml_register_endpoints_query_vars', array(
			$subject,
			'register_endpoints_query_vars'
		), 10, 3 );
		\WP_Mock::expectFilterAdded( 'wcml_endpoint_permalink_filter', array(
			$subject,
			'endpoint_permalink_filter'
		), 10, 2 );
		\WP_Mock::expectFilterAdded( 'wc_memberships_members_area_my-memberships_actions', array(
			$subject,
			'filter_actions_links'
		) );

		\WP_Mock::expectFilterAdded( 'wpml_pre_parse_query', array( $subject, 'save_post_parent' ) );
		\WP_Mock::expectFilterAdded( 'wpml_post_parse_query', array( $subject, 'restore_post_parent' ) );
		\WP_Mock::expectFilterAdded( 'wc_memberships_rule_object_ids', array( $subject, 'add_translated_object_ids' ) );
		\WP_Mock::expectActionAdded( 'wp_enqueue_scripts', array( $subject, 'load_assets' ) );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function register_endpoints_query_vars() {
		$subject = $this->get_subject();

		$query_vars           = [];
		$wc_vars              = [];
		$endpoint             = rand_str( 32 );
		$endpoint_translation = rand_str( 32 );

		$wcml_endpoints = $this->getMockBuilder( 'WCML_Endpoints' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'get_endpoint_translation' ] )
		                       ->getMock();
		$wcml_endpoints->method( 'get_endpoint_translation' )
		               ->with( 'members_area', $endpoint )
		               ->willReturn( $endpoint_translation );
		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ 'woocommerce_myaccount_members_area_endpoint', 'members-area' ],
			'return' => $endpoint
		] );

		$query_vars = $subject->register_endpoints_query_vars( $query_vars, $wc_vars, $wcml_endpoints );
		$this->assertSame( $endpoint_translation, $query_vars['members_area'] );

	}

	/**
	 * @test
	 */
	public function get_translated_endpoint() {

		$endpoint            = 'members-area';
		$translated_endpoint = rand_str();

		$subject = $this->get_subject();

		$endpoints_object = $this->getMockBuilder( 'WCML_Endpoints' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array(
			                         'get_endpoint_translation'
		                         ) )
		                         ->getMock();
		$endpoints_object->method( 'get_endpoint_translation' )->willReturn( $translated_endpoint );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_myaccount_members_area_endpoint', $endpoint ),
			'return' => $endpoint,
			'times'  => 1,
		) );

		$endpoint_translation = $subject->get_translated_endpoint( $endpoints_object );

		$this->assertEquals( $translated_endpoint, $endpoint_translation );

	}

	/**
	 * @test
	 */
	public function adjust_query_vars() {
		$subject = $this->get_subject();

		$q = $this->getMockBuilder( 'WP_Query' )
		          ->disableOriginalConstructor()
		          ->getMock();

		$q->query_vars['members_area'] = rand_str( 32 );

		$q = $subject->adjust_query_vars( $q );

		$this->assertSame( $q->query_vars['members_area'], $q->query_vars['members-area'] );

	}

	/**
	 * @test
	 */
	public function endpoint_permalink_filter() {

		$subject = $this->get_subject();

		$endpoint = rand_str();

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_myaccount_members_area_endpoint', 'members-area' ),
			'return' => $endpoint,
			'times'  => 1,
		) );

		$filtered_endpoint = $subject->endpoint_permalink_filter( rand_str(), 'members_area' );

		$this->assertEquals( $endpoint, $filtered_endpoint );

	}


	/**
	 * @test
	 */
	public function filter_actions_links() {

		$subject             = $this->get_subject();
		$original_endpoint   = rand_str();
		$translated_endpoint = rand_str();

		$actions = array(
			'view' => array(
				'url' => $original_endpoint
			)
		);

		$expected_actions = array(
			'view' => array(
				'url' => $translated_endpoint
			)
		);

		$endpoint = rand_str();

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_myaccount_members_area_endpoint', 'members-area' ),
			'return' => $original_endpoint,
			'times'  => 1,
		) );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $original_endpoint, 'WooCommerce Endpoints', 'members_area' )
		       ->reply( $translated_endpoint );


		$filtered_actions = $subject->filter_actions_links( $actions );

		$this->assertEquals( $expected_actions, $filtered_actions );
	}

	/**
	 * @test
	 * @dataProvider dp_queries
	 */
	public function it_saves_and_restores_post_parent( $post_parent, $post_types ) {
		$q = $this->getMockBuilder( 'WP_Query' )
			->disableOriginalConstructor()
			->getMock();

		$q->post_parent = $post_parent;
		$q->query_vars = [ 'post_type' => $post_types ];

		$subject = $this->get_subject();

		$result = $subject->save_post_parent( $q );
		$this->assertEquals( $q, $result );

		unset( $result->post_parent );
		$this->assertEquals( $q, $subject->restore_post_parent( $result ) );
	}

	public function dp_queries() {
		return [
			[ 10, [ 'post', 'wc_user_membership' ] ],
			[ 10, [ 'post' ] ],
			[ 10, 'wc_user_membership' ],
			[ null, 'wc_user_membership' ],
		];
	}

	/**
	 * @test
	 */
	public function it_adds_translated_object_ids() {
		$ids          = [ 1, 2, 3 ];
		$trids        = [ 51, 52, 53 ];
		$trans_ids    = [ 101, 102, 103 ];
		$expected     = [ 1, 101, 2, 102, 3, 103 ];
		$post_type    = 'a_post_type';
		$element_type = 'element_type';

		WP_Mock::userFunction( 'get_post_type', [
			'return' => $post_type,
		] );

		WP_Mock::onFilter( 'wpml_element_type' )
			->with( $post_type )
			->reply( $element_type );

		for ( $i = 0; $i < 3; $i ++ ) {
			WP_Mock::onFilter( 'wpml_element_trid' )
				->with( null, $ids[ $i ], $element_type )
				->reply( $trids[ $i ] );

			$element1 = (object) [ 'element_id' => $ids[ $i ] ];
			$element2 = (object) [ 'element_id' => $trans_ids[ $i ] ];

			WP_Mock::onFilter( 'wpml_get_element_translations' )
				->with( [], $trids[ $i ], $element_type )
				->reply( [ $element1, $element2 ] );

			WP_Mock::userFunction( 'wp_list_pluck', [
				'args'   => [ [ $element1, $element2 ], 'element_id' ],
				'return' => [ $ids[ $i ], $trans_ids[ $i ] ],
			] );
		}

		$subject = $this->get_subject();
		$this->assertEquals( $expected, $subject->add_translated_object_ids( $ids ) );
	}

	/**
	 * @test
	 */
	public function load_assets() {
		$wp_api  = $this->get_wp_api();
		$subject = $this->get_subject( $wp_api );

		$wcml_plugin_url = rand_str( 32 );
		$wcml_version    = rand_str( 32 );
		$wp_api->method( 'constant' )
		       ->will(
			       $this->returnValueMap( [
				       [ 'WCML_PLUGIN_URL', $wcml_plugin_url ],
				       [ 'WCML_VERSION', $wcml_version ]
			       ] )
		       );

		$post_id = random_int( 1, 1000 );

		\WP_Mock::userFunction( 'get_the_ID', array(
			'return' => $post_id
		) );


		\WP_Mock::userFunction( 'wc_get_page_id', array(
			'args'   => [ 'myaccount' ],
			'return' => $post_id
		) );

		\WP_Mock::userFunction( 'wp_register_script', array(
			'args'  => [
				'wcml-members-js',
				$wcml_plugin_url . '/compatibility/res/js/wcml-members.js',
				array( 'jquery' ),
				$wcml_version,
				true
			],
			'times' => 1
		) );

		\WP_Mock::userFunction( 'wp_enqueue_script', array(
			'args'  => [ 'wcml-members-js' ],
			'times' => 1
		) );

		\WP_Mock::userFunction( 'wp_localize_script', array(
			'args'  => [ 'wcml-members-js', 'wc_memberships_memebers_area_endpoint', Mockery::type( 'array' ) ],
			'times' => 1
		) );


		$subject->load_assets();

	}
}
