<?php

class Test_WCML_WC_Memberships extends OTGS_TestCase {

	private function get_subject(){

		return new WCML_WC_Memberships();

	}

	/**
	 * @test
	 */
	public function load_assets(){

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'wp_enqueue_scripts', array( $subject, 'load_assets' ) );

		$subject->add_hooks();

	}


	/**
	 * @test
	 */
	public function get_translated_endpoint(){

		$endpoint = 'members-area';
		$translated_endpoint = rand_str();
		
		$subject = $this->get_subject();

		$endpoints_object = $this->getMockBuilder( 'WCML_Endpoints' )
			->disableOriginalConstructor()
			->setMethods( array(
				'get_endpoint_translation'
			) )
			->getMock();
		$endpoints_object->method( 'get_endpoint_translation' )->willReturn( $translated_endpoint );

		\WP_Mock::wpFunction( 'get_option', array(
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
	public function endpoint_permalink_filter(){

		$subject = $this->get_subject();

		$endpoint = rand_str();

		\WP_Mock::wpFunction( 'get_option', array(
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
	public function filter_actions_links(){

		$subject = $this->get_subject();
		$original_endpoint = rand_str();
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

		\WP_Mock::wpFunction( 'get_option', array(
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
	
}
