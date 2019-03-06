<?php

class Test_WCML_Bookings extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	public function setUp()
	{
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();

	}

	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress_mock( $wp_api = null ) {
		$sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api' ) )
			->getMock();

		if( null === $wp_api ){
			$wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();
	}

	/**
	 * @return WPML_Element_Translation_Package
	 */
	private function get_wpml_element_translation_package_mock() {
		return $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_subject( $sitepress = null, $woocommerce_wpml = null, $woocommerce = null, $tp = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if( null === $woocommerce ) {
			$woocommerce = $this->getMockBuilder( 'woocommerce' )
			                    ->disableOriginalConstructor()
			                    ->getMock();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress_mock();
		}

		if( null === $tp ){
			$tp = $this->get_wpml_element_translation_package_mock();
		}

		return new WCML_Bookings( $sitepress, $woocommerce_wpml, $woocommerce, $this->wpdb, $tp );
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		$_GET['post_type'] = 'wc_booking';
		\WP_Mock::wpFunction( 'remove_action', array( 'times' => 1 ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'get_translatable_documents_all', array( $subject, 'filter_translatable_documents' ) );
		\WP_Mock::expectFilterAdded( 'wp_count_posts', array( $subject, 'count_bookings_by_current_language' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'views_edit-wc_booking', array( $subject, 'unset_mine_from_bookings_views' ) );
		$subject->add_hooks();

	}


	/**
	 * @test
	 */
	public function it_should_not_add_hooks_for_not_bookings_listing_page(){

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'remove_action', array( 'times' => 0 ) );
		$_GET['post_type'] = 'not_wc_booking';

		$subject = $this->get_subject();
		\WP_Mock::expectFilterNotAdded( 'wp_count_posts', array( $subject, 'count_bookings_by_current_language' ), 10, 2 );
		\WP_Mock::expectFilterNotAdded( 'views_edit-wc_booking', array( $subject, 'unset_mine_from_bookings_views' ) );
		$subject->add_hooks();

	}


	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections(){

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_resource_base_costs', '_resource_block_costs' ), $fields_to_hide );

	}

	/**
	 * @test
	 */
	public function filter_my_account_bookings_tables_by_current_language(){

		$original_language = rand_str();
		$current_language = rand_str();
		$original_booking_id = mt_rand( 1, 100 );
		$translated_booking_id = mt_rand( 101, 200 );
		$original_booking_product_id = mt_rand( 201, 300 );

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_language_for_element' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_current_language' )->willReturn( $current_language );
		$sitepress->expects( $this->once() )->method( 'get_language_for_element' )->with( $original_booking_product_id, 'post_product' )->willReturn( $original_language );

		$original_booking = $this->getMockBuilder( 'WC_Booking' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_id', 'get_product_id' ) )
		                         ->getMock();

		$translated_booking = $this->getMockBuilder( 'WC_Booking' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_id' ) )
		                         ->getMock();

		$original_booking->expects( $this->once() )->method( 'get_id' )->willReturn( $original_booking_id );
		$original_booking->expects( $this->once() )->method( 'get_product_id' )->willReturn( $original_booking_product_id );
		$translated_booking->expects( $this->once() )->method( 'get_id' )->willReturn( $translated_booking_id );

		$subject = $this->get_subject( $sitepress );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $original_booking_id, '_language_code', true ),
			'times'  => 1,
			'return' => ''
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $translated_booking_id, '_language_code', true ),
			'times'  => 1,
			'return' => $current_language
		) );

		$tables = array();
		$bookings = array( $original_booking, $translated_booking );
		$tables[]['bookings'] = $bookings;

		$filtered_tables = $subject->filter_my_account_bookings_tables_by_current_language( $tables );

		$expected_tables = array(
			array(
				'bookings' => array(
					$translated_booking
				)
			)
		);

		$this->assertEquals($expected_tables, $filtered_tables );

	}

	/**
	 * @test
	 */
	public function it_should_add_bookings_emails_options_to_translate(){

		$subject = $this->get_subject();

		$options = array();
		$options = $subject->emails_options_to_translate( $options );

		$this->assertContains( 'woocommerce_new_booking_settings', $options );
	}

	/**
	 * @test
	 */
	public function it_should_add_bookings_emails_text_keys_to_translate(){

		$subject = $this->get_subject();

		$text_keys = array();
		$text_keys = $subject->emails_text_keys_to_translate( $text_keys );

		$this->assertEquals( array( 'subject_confirmation','heading_confirmation' ), $text_keys );
	}

	/**
	 * @test
	 */
	public function it_should_translate_emails_text_strings(){

		$subject = $this->get_subject();

		$value = rand_str();
		$old_value = rand_str();
		$key = 'subject';

		$object = new stdClass();
		$object->id = 'new_booking';
		$object->$key = rand_str();

		$translated_value = $subject->translate_emails_text_strings( $value, $object , $old_value, $key );

		$this->assertEquals( $object->$key, $translated_value );
	}

	/**
	 * @test
	 */
	public function it_should_return_booking_email_language(){

		$subject = $this->get_subject();

		$current_language = rand_str( 2 );
		$_POST[ 'post_type' ] = 'wc_booking';
		$_POST[ '_booking_order_id' ] = mt_rand( 1, 10 );

		$order_language = rand_str(2);
		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $_POST[ '_booking_order_id' ], 'wpml_language', true ),
			'return' => $order_language
		) );

		$booking_language = $subject->booking_email_language( $current_language );

		$this->assertEquals( $order_language, $booking_language );
	}

	/**
	 * @test
	 */
	public function set_booking_language_if_missing(){

		$booking_id = mt_rand( 1, 10 );
		$current_language = mt_rand();

		\WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $booking_id ),
			'return' => 'wc_booking'
		) );


		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_element_language_details', 'set_element_language_details' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_current_language' )->willReturn( $current_language );
		$sitepress->expects( $this->once() )->method( 'get_element_language_details' )->with( $booking_id, 'post_wc_booking' )->willReturn( array() );
		$sitepress->expects( $this->once() )->method( 'set_element_language_details' )->with( $booking_id, 'post_wc_booking', false, $current_language )->willReturn( true );

		$subject = $this->get_subject( $sitepress );

		$subject->maybe_set_booking_language( $booking_id );
	}

	/**
	 * @test
	 */
	public function it_should_unset_mine_from_bookings_views(){

		$_GET['post_type'] = 'wc_booking';

		$views['mine'] = rand_str();
		$views['unpaid'] = rand_str();

		$expected_views['unpaid'] = $views['unpaid'];

		$subject = $this->get_subject();

		$filtered_views = $subject->unset_mine_from_bookings_views( $views );

		$this->assertEquals( $expected_views, $filtered_views );
	}

	/**
	 * @test
	 */
	public function it_should_remove_language_switcher_from_bookings_admin_page(){

		$_GET['post_type'] = 'wc_booking';

		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'remove_action', array( 'times' => 1 ) );

		$subject->remove_language_switcher();
	}

	/**
	 * @test
	 */
	public function it_should_count_bookings_by_current_language(){

		$_GET['post_type'] = 'wc_booking';
		$type              = 'wc_booking';
		$counts[] = array(
			'post_status' => 'unpaid',
			'num_posts' => 100
		);

		$this->wpdb->method( 'get_results' )->willReturn( $counts );

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_current_language' )->willReturn( 'es' );

		$post_statuses = array( 'unpaid' => 'unpaid' );

		\WP_Mock::userFunction( 'get_post_stati',
			array(
				'return' => $post_statuses,
				'times'  => 1
			)
		);

		$subject = $this->get_subject( $sitepress );

		$expected_counts         = new stdClass();
		$expected_counts->unpaid = 100;

		$filtered_counts = $subject->count_bookings_by_current_language( new stdClass(), $type );

		$this->assertEquals( $expected_counts, $filtered_counts );
	}

}

