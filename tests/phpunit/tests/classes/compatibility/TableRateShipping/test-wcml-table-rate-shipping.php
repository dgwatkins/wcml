<?php

class Test_WCML_Table_Rate_Shipping extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		$_POST = [];
		parent::tearDown();
	}

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_sitepress(){
		return $this->getMockBuilder( 'SitePress' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_wpdb() {
		return $this->getMockBuilder('wpdb')
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_subject( $sitepress = null, $woocommerce_wpml = null, $wpdb = null ) {
		if ( ! $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		if ( !$woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if ( ! $wpdb ) {
			$wpdb = $this->get_wpdb();
		}

		return new WCML_Table_Rate_Shipping( $sitepress, $woocommerce_wpml, $wpdb );
	}

	/**
	 * @test
	 */
	public function front_adds_hooks(){
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'get_the_terms', [ $subject, 'shipping_class_id_in_default_language' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_table_rate_is_available', [ $subject, 'shipping_table_rate_is_available' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'woocommerce_table_rate_query_rates', [ $subject, 'translate_abort_messages' ] );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function mc_adds_hooks(){
		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		$_POST = [ 'shipping_abort_reason' => 1 ];

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_table_rate_get_shipping_rates', [ $subject, 'register_abort_messages' ] );
		\WP_Mock::expectActionAdded( 'wp_ajax_woocommerce_table_rate_delete', [ $subject, 'unregister_abort_messages_ajax' ], WCML_Table_Rate_Shipping::PRIORITY_BEFORE_DELETE );
		\WP_Mock::expectActionAdded( 'delete_product_shipping_class', [ $subject, 'unregister_abort_messages_shipping_class' ], WCML_Table_Rate_Shipping::PRIORITY_BEFORE_DELETE );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_filter_table_rate_priorities() {

		$class_instance_id = 2;
		$values            = array(
			'class_slug' => $class_instance_id
		);

		$translated_class       = new stdClass();
		$translated_class->slug = 'slug-de';

		WP_Mock::userFunction( 'get_term_by', array(
			'args'   => array( 'slug', 'class_slug', 'product_shipping_class' ),
			'return' => $translated_class
		) );

		$subject         = $this->get_subject();
		$filtered_values = $subject->filter_table_rate_priorities( $values );

		$this->assertEquals( array( $translated_class->slug => $class_instance_id ), $filtered_values );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_table_rate_priorities_with_same_slug() {

		$class_instance_id = 2;
		$values            = array(
			'class_slug' => $class_instance_id
		);

		$translated_class       = new stdClass();
		$translated_class->slug = 'class_slug';

		WP_Mock::userFunction( 'get_term_by', array(
			'args'   => array( 'slug', 'class_slug', 'product_shipping_class' ),
			'return' => $translated_class
		) );

		$subject         = $this->get_subject();
		$filtered_values = $subject->filter_table_rate_priorities( $values );

		$this->assertEquals( array( $translated_class->slug => $class_instance_id ), $filtered_values );
	}

	/**
	 * @test
	 */
	public function it_should_re_check_shipping_table_rate_is_available() {

		\WP_Mock::wpPassthruFunction( 'remove_filter' );
		$available = true;

		$object = $this->getMockBuilder( 'WC_Shipping_Method' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_available' ) )
		                     ->getMock();
		$object->method( 'is_available' )->willReturn( true );

		$object->instance_id = mt_rand( 1, 10 );

		$subject         = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'option_woocommerce_table_rate_priorities_'.$object->instance_id, array( $subject, 'filter_table_rate_priorities' ) );

		$subject->shipping_table_rate_is_available( $available, array(), $object );
	}

	/**
	 * @test
	 * @dataProvider get_new_rates
	 * @param array $post
	 * @param array $rates
	 * @param array $expects
	 */
	public function it_registers_abort_messages( $post, $rates, $expects ) {
		$_POST = $post;

		foreach($expects as $expect) {
			list( $name, $value ) = $expect;
			\WP_Mock::expectAction( 'wpml_register_single_string', WCML_WC_Shipping::STRINGS_CONTEXT, $name, $value );
		}
		$subject = $this->get_subject();
		$subject->register_abort_messages( $rates );
	}

	/**
	 * @test
	 * @dataProvider get_queried_rates
	 * @param array $post
	 * @param array $expected
	 */
	public function it_translates_abort_messages( $rates, $expected ) {
		foreach ( $rates as $rate ) {
			if ( isset( $rate->rate_abort_reason ) ) {
				\WP_Mock::onFilter( 'wpml_translate_single_string' )
					->with(
						$rate->rate_abort_reason,
						WCML_WC_Shipping::STRINGS_CONTEXT,
						'table_rate_shipping_abort_reason_' . $rate->rate_id
					)->reply( 'tr-' . $rate->rate_abort_reason );
			}
		}
		$subject = $this->get_subject();
		$actual = $subject->translate_abort_messages( $rates );
		$this->assertEquals( $actual, $expected );
	}

	/**
	 * @test
	 * @dataProvider get_posted_rate_ids
	 * @param array $post
	 * @param array $expects
	 */
	public function it_unregisters_abort_messages_via_ajax( $post, $expects ) {
		$_POST = $post;

		\WP_Mock::userFunction( 'check_ajax_referer', [
			'args' => [ 'delete-rate', 'security' ],
			'times' => 1
		] );

		foreach ($expects as $expect) {
			\WP_Mock::userFunction( 'icl_unregister_string', [
				'args' => [ WCML_WC_Shipping::STRINGS_CONTEXT, $expect ],
				'times' => 1
			] );
		}

		$subject = $this->get_subject();
		$subject->unregister_abort_messages_ajax();
	}

	/**
	 * @test
	 * @dataProvider get_deleted_term_ids
	 * @param array $term_id
	 * @param array $expects
	 */
	public function it_unregisters_abort_messages_on_delete_shipping_class( $term_id, $rate_ids, $expects ) {
		$wpdb = $this->getMockBuilder('wpdb')
			->disableOriginalConstructor()
			->setMethods( [ 'get_col', 'prepare' ] )
			->getMock();
		$wpdb->prefix = 'wp_';
		$wpdb->expects( $this->exactly(1) )->method( 'get_col' )->willReturn( $rate_ids );
		$wpdb->expects( $this->exactly(1) )->method( 'prepare' )->willReturnArgument( 0 );

		foreach ($expects as $expect) {
			\WP_Mock::userFunction( 'icl_unregister_string', [
				'args' => [ WCML_WC_Shipping::STRINGS_CONTEXT, $expect ],
				'times' => 1
			] );
		}

		$subject = $this->get_subject( null, null, $wpdb );
		$subject->unregister_abort_messages_shipping_class( $term_id );
	}

	/** @return [ $post, $rates, $expects ] */
	public function get_new_rates() {
		return [
			// submitting correctly case
			[
				['shipping_abort_reason' => []],
				[
					[ 'rate_id' => 1, 'rate_abort_reason' => 'reason1' ],
					[ 'rate_id' => 2 ],
					[ 'rate_id' => 3, 'rate_abort_reason' => 'reason3' ],
				],
				[
					[ 'table_rate_shipping_abort_reason_1', 'reason1' ],
					[ 'table_rate_shipping_abort_reason_3', 'reason3' ],
				]
			],
			// not submitting case
			[
				[],
				[
					[ 'rate_id' => 1 ],
					[ 'rate_id' => 2 ],
				],
				[]
			],
		];
	}

	/** @return [ $rates, $expects ] */
	public function get_queried_rates() {
		return [
			[
				[
					(object)[ 'rate_id' => 1, 'rate_abort_reason' => 'reason1' ],
					(object)[ 'rate_id' => 2 ],
					(object)[ 'rate_id' => 3, 'rate_abort_reason' => 'reason3' ],
				],
				[
					(object)[ 'rate_id' => 1, 'rate_abort_reason' => 'tr-reason1' ],
					(object)[ 'rate_id' => 2 ],
					(object)[ 'rate_id' => 3, 'rate_abort_reason' => 'tr-reason3' ],
				],
			],
		];
	}

	/** @return [ $post, $expects ] */
	public function get_posted_rate_ids() {
		return [
			[ [], [] ],
			[ [ 'rate_id' => 1 ], [ 'table_rate_shipping_abort_reason_1' ] ],
			[ [ 'rate_id' => [ 1, 2, 3 ] ], [ 'table_rate_shipping_abort_reason_1', 'table_rate_shipping_abort_reason_2', 'table_rate_shipping_abort_reason_3' ] ],
		];
	}

	/** @return [ $term_id, $rate_ids, $expects ] */
	public function get_deleted_term_ids() {
		return [
			[ 1, [], [] ],
			[ 7, [ 1 ], [ 'table_rate_shipping_abort_reason_1' ] ],
			[ 4, [ 1, 2, 3 ], [ 'table_rate_shipping_abort_reason_1', 'table_rate_shipping_abort_reason_2', 'table_rate_shipping_abort_reason_3' ] ],
		];
	}
}
