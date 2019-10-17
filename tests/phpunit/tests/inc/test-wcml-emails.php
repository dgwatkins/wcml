<?php

/**
 * @group email
 */
class Test_WCML_Emails extends OTGS_TestCase {

	/** @var WCML_WC_Strings */
	private $wcmlStrings;
	/** @var Sitepress */
	private $sitepress;
	/** @var \WC_Emails $wcEmails */
	private $wcEmails;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp(){
		parent::setUp();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language', 'switch_lang', 'get_locale', 'get_user_admin_language', 'get_default_language' ) )
			->getMock();


		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->wcmlStrings = $this->getMockBuilder( WCML_WC_Strings::class )
			->disableOriginalConstructor()
			->getMock();

		$this->wcEmails = $this->getMockBuilder('WC_Emails')
			->disableOriginalConstructor()
			->getMock();

		$this->wp_api->method( 'constant' )->with( 'WPML_ST_VERSION' )->willReturn( '2.5.2' );

		$this->wpdb = $this->stubs->wpdb();
	}

	private function get_subject( ){

		return new WCML_Emails( $this->wcmlStrings, $this->sitepress, $this->wcEmails, $this->wpdb );

	}

	/**
	 * @test
	 */
	public function add_hooks()
	{
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'is_admin', array(
			'return' => true
		) );

		\WP_Mock::expectActionAdded( 'woocommerce_order_status_pending_to_on-hold_notification', array( $subject, 'email_heading_on_hold' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_failed_to_on-hold_notification', array( $subject, 'email_heading_on_hold' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $subject, 'email_heading_on_hold' ), 9 );
		\WP_Mock::expectFilterAdded( 'woocommerce_email_heading_customer_on_hold_order', array( $subject, 'customer_on_hold_order_heading' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_email_subject_customer_on_hold_order', array( $subject, 'customer_on_hold_order_subject' ) );

		//processing order actions
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_pending_to_processing_notification', array( $subject, 'email_heading_processing' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_failed_to_processing_notification', array( $subject, 'email_heading_processing' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_cancelled_to_processing_notification', array( $subject, 'email_heading_processing' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_on-hold_to_processing_notification', array( $subject, 'email_heading_processing' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_pending_to_processing_notification', array( $subject, 'refresh_email_lang' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_failed_to_processing_notification', array( $subject, 'refresh_email_lang' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_cancelled_to_processing_notification', array( $subject, 'refresh_email_lang' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_on-hold_to_processing_notification', array( $subject, 'refresh_email_lang' ), 9 );
		\WP_Mock::expectFilterAdded( 'woocommerce_email_heading_customer_processing_order', array( $subject, 'customer_processing_order_heading' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_email_subject_customer_processing_order', array( $subject, 'customer_processing_order_subject' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wcml-2549
	 */
	public function it_should_change_current_language() {
		$lang = 'pt-br';
		$user_id = 1;

		$sitepress = $this->getMockBuilder( 'SitePress' )
			->setMethods( array( 'switch_lang', 'get_locale', 'get_user_admin_language' ) )
			->disableOriginalConstructor()
			->getMock();

		$subject = new WCML_Emails( $this->wcmlStrings, $sitepress, $this->wcEmails, $this->wpdb );

		\WP_Mock::userFunction( 'get_current_user_id', array(
			'return' => $user_id,
		) );

		$sitepress->expects( $this->once() )->method( 'get_user_admin_language' )->with()->willReturn( $lang );
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( $lang );

		$subject->change_email_language( $lang );
	}

	/**
	 * @test
	 */
	public function wcml_get_translated_email_string(){

		$context = rand_str();
		$name = rand_str();
		$trnsl_name = rand_str();
		$order_id = rand( 1, 100 );
		$language_code = 'fr';
		$result = rand_str();

		$this->wpdb->method( 'get_var' )->willReturn( $result );

		$subject = $this->get_subject( );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language_code
		) );
		
		WP_Mock::onFilter( 'wpml_translate_single_string' )
			->with( $result, $context, $name , $language_code )
			->reply( $trnsl_name );

		$filtered_name = $subject->wcml_get_translated_email_string( $context, $name, $order_id  );
		$this->assertEquals( $trnsl_name, $filtered_name );
	}

	/**
	 * @test
	 */
	public function wcml_get_translated_email_string_with_language_code(){

		$context = rand_str();
		$name = rand_str();
		$trnsl_name = rand_str();
		$order_id = rand( 1, 100 );
		$language_code = rand_str();
		$result = rand_str();

		$this->wpdb->method( 'get_var' )->willReturn( $result );

		$subject = $this->get_subject();

		WP_Mock::onFilter( 'wpml_translate_single_string' )
			->with( $result, $context, $name , $language_code )
			->reply( $trnsl_name );

		$filtered_name = $subject->wcml_get_translated_email_string( $context, $name, $order_id, $language_code );
		$this->assertEquals( $trnsl_name, $filtered_name );
	}

	/**
	 * @test
	 */
	public function email_refresh_in_ajax(){

		$order_id = $_GET['order_id'] = mt_rand( 1, 100 );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => false
		) );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->email_refresh_in_ajax() );

	}

	/**
	 * @test
	 */
	public function refresh_in_ajax_completed(){

		$order_id = $_GET['order_id'] = mt_rand( 1, 100 );
		$_GET['status'] = 'completed';

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => false
		) );

		$this->wcEmails =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_completed = $this->getMockBuilder( 'WC_Email_Customer_Completed_Order' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'trigger' ) )
		                            ->getMock();
		$wc_mailer_completed->enabled = rand_str();


		$this->wcEmails->emails = array( 'WC_Email_Customer_Completed_Order' => $wc_mailer_completed );

		$wc_mailer_completed->expects( $this->once() )
		                    ->method( 'trigger' )
		                    ->with( $order_id )
		                    ->willReturn( true );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->email_refresh_in_ajax() );

	}

	/**
	 * @test
	 */
	public function filter_refund_emails_strings(){

		$order_id = mt_rand( 1, 100 );
		$language = rand_str();
		$context = 'admin_texts_woocommerce_customer_refunded_order_settings';
		$key = 'subject_full';
		$name = '[woocommerce_customer_refunded_order_settings]'.$key;
		$translated_value = rand_str();

		$object = $this->getMockBuilder('WC_Emails')
		               ->disableOriginalConstructor()
		               ->getMock();

		$object->object = $this->getMockBuilder('WC_Order')
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_id' ) )
		               ->getMock();

		$object->object->expects( $this->once() )
		                    ->method( 'get_id' )
		                    ->willReturn( $order_id );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language
		) );
		$result = rand_str();

		$this->wpdb->method( 'get_var' )->willReturn( $result );

		\WP_Mock::onFilter( 'wpml_translate_single_string')->with( $result, $context, $name, $language )->reply( $translated_value );

		$subject = $this->get_subject();

		$this->assertEquals( $translated_value, $subject->filter_refund_emails_strings( rand_str(), $object, rand_str(), $key ) );

	}

	/**
	 * @test
	 */
	public function filter_refund_emails_strings_key_not_matched(){

		$key = rand_str();
		$value = rand_str();

		$object = $this->getMockBuilder('WC_Emails')
		               ->disableOriginalConstructor()
		               ->getMock();

		$object->object = $this->getMockBuilder('WC_Order')
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_id' ) )
		               ->getMock();


		$subject = $this->get_subject();

		$this->assertEquals( $value, $subject->filter_refund_emails_strings( $value, $object, $value, $key ) );

	}

	/**
	 * @test
	 * @dataProvider email_heading_classes_provider
	 */
	public function it_should_filter_order_email_heading( $class_name, $method ){


		$this->wcEmails =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_class = $this->getMockBuilder( $class_name )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'format_string' ) )
		                            ->getMock();
		$wc_mailer_class->heading = rand_str();

		$this->wcEmails->emails = array( $class_name => $wc_mailer_class );

		$translated_formatted_heading = rand_str();

		$wc_mailer_class->expects( $this->once() )
		                    ->method( 'format_string' )
		                    ->with( $wc_mailer_class->heading )
		                    ->willReturn( $translated_formatted_heading );

		$subject = $this->get_subject();

		$this->assertEquals( $translated_formatted_heading, $subject->$method( rand_str() ) );

	}

	public function email_heading_classes_provider(){

		return array(
			array( 'WC_Email_New_Order', 'new_order_email_heading' ),
			array( 'WC_Email_Customer_On_Hold_Order', 'customer_on_hold_order_heading' ),
			array( 'WC_Email_Customer_Processing_Order', 'customer_processing_order_heading' ),
		);
	}

	/**
	 * @test
	 * @dataProvider email_subject_classes_provider
	 */
	public function it_should_filter_order_email_subject( $class_name, $method ){

		$this->wcEmails =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_class = $this->getMockBuilder( $class_name )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'format_string' ) )
		                            ->getMock();
		$wc_mailer_class->subject = rand_str();

		$this->wcEmails->emails = array( $class_name => $wc_mailer_class );

		$translated_formatted_subject = rand_str();

		$wc_mailer_class->expects( $this->once() )
		                    ->method( 'format_string' )
		                    ->with( $wc_mailer_class->subject )
		                    ->willReturn( $translated_formatted_subject );

		$subject = $this->get_subject();

		$this->assertEquals( $translated_formatted_subject, $subject->$method( rand_str() ) );

	}

	public function email_subject_classes_provider(){

		return array(
			array( 'WC_Email_New_Order', 'new_order_email_subject' ),
			array( 'WC_Email_Customer_On_Hold_Order', 'customer_on_hold_order_subject' ),
			array( 'WC_Email_Customer_Processing_Order', 'customer_processing_order_subject' ),
		);
	}


	/**
	 * @test
	 * @dataProvider email_headings_classes_provider
	 */
	public function it_should_filter_email_headings( $class_name, $method ){

		$order_id = 10;
		$this->wcEmails =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_class = $this->getMockBuilder( $class_name )
		                                ->disableOriginalConstructor()
		                                ->setMethods( array( 'trigger' ) )
		                                ->getMock();
		$wc_mailer_class->subject = rand_str();
		$wc_mailer_class->heading = rand_str();
		$wc_mailer_class->enabled = true;

		$this->wcEmails->emails = array( $class_name => $wc_mailer_class );

		$wc_mailer_class->expects( $this->once() )
		                        ->method( 'trigger' )
		                        ->willReturn( true );

		$subject = $this->get_subject();
		$subject->$method( $order_id );
	}


	public function email_headings_classes_provider(){

		return array(
			array( 'WC_Email_Customer_On_Hold_Order', 'email_heading_on_hold' ),
			array( 'WC_Email_Customer_Processing_Order', 'email_heading_processing' ),
			array( 'WC_Email_Customer_Processing_Order', 'email_heading_processing' ),
			array( 'WC_Email_Customer_Note', 'email_heading_note' ),
		);
	}


	/**
	 * @test
	 */
	public function it_should_get_email_language_from_admin_user_settings_for_new_order_admin_email(){

		$order_id = 101;
		$recipient = 'admin@test.com';

		$user = new stdClass();
		$user->ID = 1;
		$user_language = 'en';

		WP_Mock::userFunction( 'get_user_by', array(
			'args' => array( 'email', $recipient ),
			'return' => $user
		));

		WP_Mock::userFunction( 'get_current_user_id', array(
			'return' => $user->ID
		));

		$this->wcEmails =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_new_order = $this->getMockBuilder( 'WC_Email_New_Order' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'trigger', 'get_recipient' ) )
		                            ->getMock();
		$wc_mailer_new_order->enabled = rand_str();

		$wc_mailer_new_order->expects( $this->once() )
		                    ->method( 'get_recipient' )
		                    ->willReturn( $recipient );

		$wc_mailer_new_order->expects( $this->once() )
		                    ->method( 'trigger' )
		                    ->with( $order_id )
		                    ->willReturn( true );


		$this->wcEmails->emails = array( 'WC_Email_New_Order' => $wc_mailer_new_order );

		$this->sitepress->method( 'get_user_admin_language' )->with( $user->ID, true )->willReturn( $user_language );
		$this->sitepress->expects( $this->once() )->method( 'switch_lang' )->with( $user_language );

		$subject = $this->get_subject();

		$subject->new_order_admin_email( $order_id );
	}

	/**
	 * @test
	 */
	public function it_should_use_default_language_if_admin_user_not_exists_for_new_order_admin_email(){

		$order_id = 102;
		$recipient = 'test@test.com';

		$default_language = 'en';

		WP_Mock::userFunction( 'get_user_by', array(
			'args' => array( 'email', $recipient ),
			'return' => false
		));

		WP_Mock::userFunction( 'get_current_user_id', array(
			'return' => 0
		));

		$this->wcEmails =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_new_order = $this->getMockBuilder( 'WC_Email_New_Order' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'trigger', 'get_recipient' ) )
		                            ->getMock();
		$wc_mailer_new_order->enabled = rand_str();

		$wc_mailer_new_order->expects( $this->once() )
		                    ->method( 'get_recipient' )
		                    ->willReturn( $recipient );

		$wc_mailer_new_order->expects( $this->once() )
		                    ->method( 'trigger' )
		                    ->with( $order_id )
		                    ->willReturn( true );


		$this->wcEmails->emails = array( 'WC_Email_New_Order' => $wc_mailer_new_order );

		$this->sitepress->method( 'get_default_language' )->willReturn( $default_language );
		$this->sitepress->expects( $this->once() )->method( 'switch_lang' )->with( $default_language );

		$subject = $this->get_subject();

		$subject->new_order_admin_email( $order_id );
	}
}
