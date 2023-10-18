<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WCML_Bookings
 *
 * @group compatibility
 * @group wc-bookings
 * @group wcml-2957
 */
class Test_WCML_Bookings extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	public function setUp() {
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();
	}

	public function tearDown() {
		unset( $_COOKIE['_wcml_booking_currency'] );
		parent::tearDown();
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
	private function get_sitepress_mock() {
		return $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
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
	/**
	 * @return WPML_Element_Translation_Package
	 */
	private function get_wpml_post_translations_mock() {
		return $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_subject( $sitepress = null, $woocommerce_wpml = null, $tp = null, $wpml_post_translations = null  ) {

		if( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if( null === $sitepress ) {
			$sitepress = $this->get_sitepress_mock();
		}

		if( null === $tp ) {
			$tp = $this->get_wpml_element_translation_package_mock();
		}

		if( null === $wpml_post_translations ) {
			$wpml_post_translations = $this->get_wpml_post_translations_mock();
		}

		return new WCML_Bookings( $sitepress, $woocommerce_wpml, $this->wpdb, $tp, $wpml_post_translations );
	}

	/**
	 * @test
	 * @group wcml-3268
	 */
	public function add_hooks() {
		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		$_GET['post_type'] = 'wc_booking';
		\WP_Mock::userFunction( 'remove_action', array( 'times' => 1 ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_order_id_for_language', array( $subject, 'order_id_for_language' ) );
		\WP_Mock::expectFilterAdded( 'get_translatable_documents_all', array( $subject, 'filter_translatable_documents' ) );
		\WP_Mock::expectFilterAdded( 'wp_count_posts', array( $subject, 'count_bookings_by_current_language' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'views_edit-wc_booking', array( $subject, 'unset_mine_from_bookings_views' ) );
		\WP_Mock::expectFilterAdded( 'schedule_event', [ $subject, 'prevent_events_on_duplicates' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_not_add_hooks_for_not_bookings_listing_page() {

		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::userFunction( 'remove_action', array( 'times' => 0 ) );
		$_GET['post_type'] = 'not_wc_booking';

		$subject = $this->get_subject();
		\WP_Mock::expectFilterNotAdded( 'wp_count_posts', array( $subject, 'count_bookings_by_current_language' ), 10, 2 );
		\WP_Mock::expectFilterNotAdded( 'views_edit-wc_booking', array( $subject, 'unset_mine_from_bookings_views' ) );
		$subject->add_hooks();

	}


	/**
	 * @test
	 */
	public function order_id_for_language() {

		$booking_id = 1234;
		$order_id   = 5678;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ $booking_id ],
			'return' => WCML_Bookings::POST_TYPE,
		] );

		\WP_Mock::userFunction( 'wp_get_post_parent_id', [
			'args'   => [ $booking_id ],
			'return' => $order_id,
		] );

		$subject = $this->get_subject();
		$result = $subject->order_id_for_language( $booking_id );
		$this->assertEquals( $result, $order_id );
	}

	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections() {

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_resource_base_costs', '_resource_block_costs' ), $fields_to_hide );

	}

	/**
	 * @test
	 */
	public function it_should_return_booking_email_language() {

		$subject = $this->get_subject();

		$current_language = 'en';
		$_POST[ 'post_type' ] = 'wc_booking';
		$_POST[ '_booking_order_id' ] = '456';

		$order_language = 'fr';
		FunctionMocker::replace( 'WCML_Orders::getLanguage', function( $orderId ) use ( $order_language ) {
			return ( (int) $_POST['_booking_order_id'] === $orderId ) ? $order_language : false;
		} );

		$booking_language = $subject->booking_email_language( $current_language );

		$this->assertEquals( $order_language, $booking_language );

		$_POST = [];
	}

	/**
	 * @test
	 */
	public function it_should_return_current_booking_email_language() {

		$subject = $this->get_subject();

		$current_language = 'es';
		$_POST[ 'post_type' ] = 'wc_booking';

		$booking_language = $subject->booking_email_language( $current_language );

		$this->assertEquals( $current_language, $booking_language );

		$_POST = [];
	}

	/**
	 * @test
	 */
	public function set_booking_language_if_missing() {

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
	public function it_should_unset_mine_from_bookings_views() {

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
	public function it_should_remove_language_switcher_from_bookings_admin_page() {

		$_GET['post_type'] = 'wc_booking';

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'remove_action', array( 'times' => 1 ) );

		$subject->remove_language_switcher();
	}

	/**
	 * @test
	 */
	public function it_should_count_bookings_by_current_language() {

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
		                                 ->setMethods( array( 'getStringTranslation' ) )
		                                 ->getMock();

		$that          = $this;
		$that->strings = $strings;
		$woocommerce_wpml->emails->method( 'getStringTranslation' )->willReturnCallback( function ( $context, $name, $language_code, $originalValue, $originalDomain ) use ( $that ) {
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
	 */
	public function it_should_filter_is_translated_post_type_for_bookings_listing_page() {

		$_GET['post_type'] = 'wc_booking';

		$subject = $this->get_subject();
		$this->assertNull( $subject->filter_is_translated_post_type( true ) );
		unset( $_GET['post_type'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_is_translated_post_type_for_new_booking_page() {

		$_GET['post_type'] = 'wc_booking';
		$_GET['page']      = 'create_booking';

		$subject = $this->get_subject();
		$this->assertTrue( $subject->filter_is_translated_post_type( true ) );
		unset( $_GET['post_type'], $_GET['page'] );
	}

	/**
	 * @test
	 * @dataProvider dp_should_not_alter_cron_event
	 * @group wcml-3267
	 *
	 * @param mixed $event
	 */
	public function it_should_not_alter_cron_event( $event ) {
		\WP_Mock::userFunction( 'get_post_meta' )->andReturn( false );
		$this->get_subject()->prevent_events_on_duplicates( $event );
	}

	public function dp_should_not_alter_cron_event() {
		return [
			'falsy event'           => [ false ],
			'empty event object'    => [ (object) [] ],
			'missing hook property' => [ (object) [ 'args' => [ 123 ] ] ],
			'missing args property' => [ (object) [ 'hook' => 'something' ] ],
			'not matching event'    => [ (object) [ 'hook' => 'something', 'args' => [ 123 ] ] ],
			'not a duplicate'       => [ (object) [ 'hook' => 'wc-booking-reminder', 'args' => [ 123 ] ] ],
		];
	}

	/**
	 * @test
	 * @group wcml-3267
	 */
	public function it_should_alter_cron_event_and_return_false_on_duplicated_bookings() {
		$original_booking_id = 123;
		$booking_id          = 456;

		$event = (object) [
			'hook' => 'wc-booking-reminder',
			'args' => [ $booking_id ],
		];

		\WP_Mock::userFunction( 'get_post_meta' )
		        ->with( $booking_id, '_booking_duplicate_of', true )
		        ->andReturn( $original_booking_id );

		$this->get_subject()->prevent_events_on_duplicates( $event );
	}

	public function dp_should_alter_cron_event_and_return_false_on_duplicated_bookings() {
		return [
			[ 'wc-booking-reminder' ],
			[ 'wc-booking-complete' ],
		];
	}

	/**
	 * @param int    $order_id
	 * @param string $order_lang
	 */
	private function mockOrderGetLanguageCallable( $order_id, $order_lang ) {
		FunctionMocker::replace(
			WCML_Orders::class . '::getLanguage',
			function() use ( $order_id, $order_lang ) {
				return function ( $orderIdArg ) use ( $order_id, $order_lang ) {
					return $orderIdArg === $order_id ? $order_lang : null;
				};
			}
		);
	}
}

