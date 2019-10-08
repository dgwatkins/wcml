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
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'get_order_language' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'woocommerce_booking_reminder_notification', array( $subject, 'translate_notification' ), 9 );
		\WP_Mock::expectFilterAdded( 'woocommerce_booking_confirmed_notification', array( $subject, 'translate_notification' ), 9 );
		\WP_Mock::expectFilterAdded( 'woocommerce_booking_cancelled_notification', array( $subject, 'translate_notification' ), 9 );

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
	public function get_order_language() {

		$booking_id    = mt_rand( 1, 100 );
		$order_item_id = mt_rand( 1, 100 );
		$order_id      = mt_rand( 1, 100 );
		$expected      = rand_str();

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $booking_id ),
			'return' => 'wc_booking',
		) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $booking_id, '_booking_order_item_id', true ),
			'return' => $order_item_id,
		) );
		$sql = 'SELECT order_id FROM wp_woocommerce_order_items WHERE order_item_id = ' . $order_item_id;
		$this->wpdb->method( 'get_var' )->with( $sql )->willReturn( $order_id );
		$this->wpdb->method( 'prepare' )->will( $this->returnCallback( function() {
			return call_user_func_array( 'sprintf', func_get_args() );
		} ) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $expected,
		) );
		\WP_Mock::wpFunction( 'remove_filter' );
		\WP_Mock::wpFunction( 'add_filter' );

		$subject = $this->get_subject();
		$language = $subject->get_order_language( null, $booking_id, 'wpml_language', true );
		$this->assertEquals( $language, $expected );
	}


	/**
	 * @test
	 */
	public function it_should_translate_notifications() {
		$order_id = 123;

		$emails = \Mockery::mock( WCML_Emails::class );
		$emails->shouldReceive( 'refresh_email_lang' )->once()->with( $order_id );

		$woocommerce_wcml = \Mockery::mock( woocommerce_wpml::class );
		$woocommerce_wcml->emails = $emails;

		$subject = $this->get_subject( null, $woocommerce_wcml );
		$subject->translate_notification( $order_id );
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

	private function get_wc_order_mock( $order_id ) {
		$order = $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_id' ) )
		              ->getMock();

		$order->expects( $this->once() )->method( 'get_id' )->willReturn( $order_id );

		return $order;
	}

	private function get_booking_mock( $booking_id, $wc_order = false ) {
		$booking = $this->getMockBuilder( 'WC_Booking' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_order' ) )
		                ->getMock();

		$booking->expects( $this->once() )->method( 'get_order' )->willReturn( $wc_order );

		\WP_Mock::userFunction( 'get_wc_booking', array(
			'args'   => array( $booking_id ),
			'return' => $booking
		) );

		return $booking;
	}

	private function get_woocommerce_with_mailer_mock( $email_class, $params ) {
		$mailer = $this->getMockBuilder( 'WC_Emails' )
		               ->disableOriginalConstructor()
		               ->getMock();

		$wc_mailer_class = $this->getMockBuilder( $email_class )
		                        ->disableOriginalConstructor()
		                        ->getMock();
		foreach ( $params as $key => $value ) {
			$wc_mailer_class->$key = $value;
		}

		$mailer->emails = array( $email_class => $wc_mailer_class );

		$woocommerce = $this->getMockBuilder( 'woocommerce' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'mailer' ) )
		                    ->getMock();
		$woocommerce->method( 'mailer' )->willReturn( $mailer );

		return $woocommerce;
	}

	private function get_woocommerce_wpml_with_translated_strings_mock( $strings ) {

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->emails = $this->getMockBuilder( 'WCML_Emails' )
		                                 ->disableOriginalConstructor()
		                                 ->setMethods( array( 'wcml_get_translated_email_string' ) )
		                                 ->getMock();

		$that          = $this;
		$that->strings = $strings;
		$woocommerce_wpml->emails->method( 'wcml_get_translated_email_string' )->willReturnCallback( function ( $context, $name, $order_id = false, $language_code = null ) use ( $that ) {
			if ( 'heading' === substr( $name, - 7 ) ) {
				return $that->strings['heading'];
			}
			if ( 'subject' === substr( $name, - 7 ) ) {
				return $that->strings['subject'];
			}
			if ( 'heading_confirmation' === substr( $name, - 20 ) ) {
				return $that->strings['heading_confirmation'];
			}
			if ( 'subject_confirmation' === substr( $name, - 20 ) ) {
				return $that->strings['subject_confirmation'];
			}

			return rand_str();
		} );

		return $woocommerce_wpml;
	}

	private function get_sitepress_with_user_language_mock( $user_email, $user_exists = true ) {

		$user                = new stdClass();
		$user->ID            = 1;
		$user_admin_language = 'es';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_user_admin_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_user_admin_language' )->with( $user->ID, true )->willReturn( $user_admin_language );

		\WP_Mock::userFunction( 'get_user_by', array(
			'args'   => array( 'email', $user_email ),
			'return' => $user_exists ? $user : false,
		) );

		return $sitepress;
	}


	/**
	 * @test
	 * @dataProvider customer_emails_classes
	 */
	public function it_should_translate_booking_customer_emails_in_order_language( $email_class, $method_name ) {

		$booking_id         = 11;
		$order_id           = 12;
		$email_heading      = rand_str();
		$translated_heading = rand_str();
		$email_subject      = rand_str();
		$translated_subject = rand_str();

		$booking = $this->get_booking_mock( $booking_id, $this->get_wc_order_mock( $order_id ) );

		$woocommerce = $this->get_woocommerce_with_mailer_mock( $email_class, array(
			'heading' => $email_heading,
			'subject' => $email_subject
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml_with_translated_strings_mock( array(
			'heading' => $translated_heading,
			'subject' => $translated_subject
		) );

		$subject = $this->get_subject( null, $woocommerce_wpml, $woocommerce );

		$subject->$method_name( $booking_id );

		$this->assertEquals( $translated_heading, $woocommerce->mailer()->emails[ $email_class ]->heading );
		$this->assertEquals( $translated_subject, $woocommerce->mailer()->emails[ $email_class ]->subject );
	}


	/**
	 * @test
	 * @dataProvider customer_emails_classes
	 */
	public function it_should_not_translate_booking_customer_emails_if_order_not_set( $email_class, $method_name ) {

		$booking_id    = 11;
		$email_heading = rand_str();
		$email_subject = rand_str();

		$booking = $this->get_booking_mock( $booking_id );

		$woocommerce = $this->get_woocommerce_with_mailer_mock( $email_class, array(
			'heading' => $email_heading,
			'subject' => $email_subject
		) );

		$subject = $this->get_subject( null, null, $woocommerce );

		$subject->$method_name( $booking_id );

		$this->assertEquals( $email_heading, $woocommerce->mailer()->emails[ $email_class ]->heading );
		$this->assertEquals( $email_subject, $woocommerce->mailer()->emails[ $email_class ]->subject );
	}

	public function customer_emails_classes() {

		return array(
			array( 'WC_Email_Booking_Confirmed', 'translate_booking_confirmed_email_texts' ),
			array( 'WC_Email_Booking_Cancelled', 'translate_booking_cancelled_email_texts' ),
			array( 'WC_Email_Booking_Reminder', 'translate_booking_reminder_email_texts' ),
		);
	}


	/**
	 * @test
	 */
	public function it_should_translate_new_booking_email_in_recipient_language() {

		$booking_id                      = 11;
		$user_email                      = 'admin@test.com';
		$email_heading                   = rand_str();
		$translated_heading              = rand_str();
		$email_heading_confirmation      = rand_str();
		$translated_heading_confirmation = rand_str();
		$email_subject                   = rand_str();
		$translated_subject              = rand_str();
		$email_subject_confirmation      = rand_str();
		$translated_subject_confirmation = rand_str();

		$woocommerce = $this->get_woocommerce_with_mailer_mock( 'WC_Email_New_Booking', array(
				'heading'              => $email_heading,
				'heading_confirmation' => $email_heading_confirmation,
				'subject'              => $email_subject,
				'subject_confirmation' => $email_subject_confirmation,
				'recipient'            => $user_email,
			)
		);

		$woocommerce_wpml = $this->get_woocommerce_wpml_with_translated_strings_mock( array(
			'heading'              => $translated_heading,
			'heading_confirmation' => $translated_heading_confirmation,
			'subject'              => $translated_subject,
			'subject_confirmation' => $translated_subject_confirmation,
		) );

		$sitepress = $this->get_sitepress_with_user_language_mock( $user_email );

		$subject = $this->get_subject( $sitepress, $woocommerce_wpml, $woocommerce );

		$subject->translate_new_booking_email_texts( $booking_id );

		$this->assertEquals( $translated_heading, $woocommerce->mailer()->emails['WC_Email_New_Booking']->heading );
		$this->assertEquals( $translated_subject, $woocommerce->mailer()->emails['WC_Email_New_Booking']->subject );
		$this->assertEquals( $translated_heading_confirmation, $woocommerce->mailer()->emails['WC_Email_New_Booking']->heading_confirmation );
		$this->assertEquals( $translated_subject_confirmation, $woocommerce->mailer()->emails['WC_Email_New_Booking']->subject_confirmation );
	}

	/**
	 * @test
	 */
	public function it_should_translate_new_booking_email_in_order_language() {

		$booking_id                      = 11;
		$order_id                        = 12;
		$user_email                      = 'admin@test.com';
		$email_heading                   = rand_str();
		$translated_heading              = rand_str();
		$email_heading_confirmation      = rand_str();
		$translated_heading_confirmation = rand_str();
		$email_subject                   = rand_str();
		$translated_subject              = rand_str();
		$email_subject_confirmation      = rand_str();
		$translated_subject_confirmation = rand_str();

		$booking = $this->get_booking_mock( $booking_id, $this->get_wc_order_mock( $order_id ) );

		$woocommerce = $this->get_woocommerce_with_mailer_mock( 'WC_Email_New_Booking', array(
				'heading'              => $email_heading,
				'heading_confirmation' => $email_heading_confirmation,
				'subject'              => $email_subject,
				'subject_confirmation' => $email_subject_confirmation,
				'recipient'            => $user_email,
			)
		);

		$woocommerce_wpml = $this->get_woocommerce_wpml_with_translated_strings_mock( array(
			'heading'              => $translated_heading,
			'heading_confirmation' => $translated_heading_confirmation,
			'subject'              => $translated_subject,
			'subject_confirmation' => $translated_subject_confirmation,
		) );

		$sitepress = $this->get_sitepress_with_user_language_mock( $user_email, false );

		$subject = $this->get_subject( $sitepress, $woocommerce_wpml, $woocommerce );

		$subject->translate_new_booking_email_texts( $booking_id );

		$this->assertEquals( $translated_heading, $woocommerce->mailer()->emails['WC_Email_New_Booking']->heading );
		$this->assertEquals( $translated_subject, $woocommerce->mailer()->emails['WC_Email_New_Booking']->subject );
		$this->assertEquals( $translated_heading_confirmation, $woocommerce->mailer()->emails['WC_Email_New_Booking']->heading_confirmation );
		$this->assertEquals( $translated_subject_confirmation, $woocommerce->mailer()->emails['WC_Email_New_Booking']->subject_confirmation );
	}

	/**
	 * @test
	 */
	public function it_should_translate_booking_cancelled_email_in_recipient_language() {

		$booking_id         = 11;
		$user_email         = 'admin@test.com';
		$email_heading      = rand_str();
		$translated_heading = rand_str();
		$email_subject      = rand_str();
		$translated_subject = rand_str();

		$woocommerce = $this->get_woocommerce_with_mailer_mock( 'WC_Email_Admin_Booking_Cancelled', array(
				'heading'   => $email_heading,
				'subject'   => $email_subject,
				'recipient' => $user_email,
			)
		);

		$sitepress = $this->get_sitepress_with_user_language_mock( $user_email );


		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml = $this->get_woocommerce_wpml_with_translated_strings_mock( array(
			'heading' => $translated_heading,
			'subject' => $translated_subject,
		) );

		$subject = $this->get_subject( $sitepress, $woocommerce_wpml, $woocommerce );

		$subject->translate_booking_cancelled_admin_email_texts( $booking_id );

		$this->assertEquals( $translated_heading, $woocommerce->mailer()->emails['WC_Email_Admin_Booking_Cancelled']->heading );
		$this->assertEquals( $translated_subject, $woocommerce->mailer()->emails['WC_Email_Admin_Booking_Cancelled']->subject );
	}

	/**
	 * @test
	 */
	public function it_should_translate_booking_cancelled_email_in_order_language() {

		$booking_id         = 11;
		$order_id           = 12;
		$user_email         = 'admin@test.com';
		$email_heading      = rand_str();
		$translated_heading = rand_str();
		$email_subject      = rand_str();
		$translated_subject = rand_str();

		$booking = $this->get_booking_mock( $booking_id, $this->get_wc_order_mock( $order_id ) );

		$woocommerce = $this->get_woocommerce_with_mailer_mock( 'WC_Email_Admin_Booking_Cancelled', array(
				'heading'   => $email_heading,
				'subject'   => $email_subject,
				'recipient' => $user_email,
			)
		);

		$sitepress = $this->get_sitepress_with_user_language_mock( $user_email, false );


		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml = $this->get_woocommerce_wpml_with_translated_strings_mock( array(
			'heading' => $translated_heading,
			'subject' => $translated_subject,
		) );

		$subject = $this->get_subject( $sitepress, $woocommerce_wpml, $woocommerce );

		$subject->translate_booking_cancelled_admin_email_texts( $booking_id );

		$this->assertEquals( $translated_heading, $woocommerce->mailer()->emails['WC_Email_Admin_Booking_Cancelled']->heading );
		$this->assertEquals( $translated_subject, $woocommerce->mailer()->emails['WC_Email_Admin_Booking_Cancelled']->subject );
	}

}

