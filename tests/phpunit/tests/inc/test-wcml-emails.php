<?php

/**
 * @group email
 */
class Test_WCML_Emails extends OTGS_TestCase {

	/** @var WCML_WC_Strings */
	private $wcmlStrings;
	/** @var SitePress */
	private $sitepress;
	/** @var WooCommerce */
	private $woocommerce;
	/** @var WC_Emails */
	private $wcEmails;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API */
	private $wp_api;

	public function setUp(){
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( SitePress::class )
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language', 'switch_lang', 'get_locale', 'get_user_admin_language', 'get_default_language' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->wcmlStrings = $this->getMockBuilder( WCML_WC_Strings::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_translated_string_by_name_and_context' ] )
			->getMock();

		$this->woocommerce = $this->getMockBuilder('woocommerce')
			->disableOriginalConstructor()
			->setMethods( array( 'mailer' ) )
			->getMock();

		$this->wp_api->method( 'constant' )->with( 'WPML_ST_VERSION' )->willReturn( '2.5.2' );

		$this->wpdb = $this->stubs->wpdb();
	}

	private function get_subject( ){

		$this->woocommerce->method( 'mailer' )->willReturn( $this->wcEmails );

		return new WCML_Emails( $this->wcmlStrings, $this->sitepress, $this->woocommerce, $this->wpdb );

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

		\WP_Mock::expectActionAdded( 'woocommerce_low_stock_notification', array( $subject, 'low_stock_admin_notification' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_no_stock_notification', array( $subject, 'no_stock_admin_notification' ), 9 );

		//comments language actions
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_pending', [ $subject, 'comments_language' ], 11 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_processing', [ $subject, 'comments_language' ], 11 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_on-hold', [ $subject, 'comments_language' ], 11 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_completed', [ $subject, 'comments_language' ], 11 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_cancelled', [ $subject, 'comments_language' ], 11 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_refunded', [ $subject, 'comments_language' ], 11 );
		\WP_Mock::expectActionAdded( 'woocommerce_order_status_failed', [ $subject, 'comments_language' ], 11 );

		\WP_Mock::expectFilterAdded( 'woocommerce_email_get_option', array( $subject, 'filter_emails_strings' ), 10, 4 );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_pre_insert_shop_order_object', [ $subject, 'set_rest_language' ], 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wcml-2549
	 */
	public function it_should_change_current_language() {
		$lang = 'pt-br';
		$user_id = 1;

		$sitepress = $this->getMockBuilder( SitePress::class )
			->setMethods( array( 'switch_lang', 'get_locale', 'get_user_admin_language' ) )
			->disableOriginalConstructor()
			->getMock();

		$subject = new WCML_Emails( $this->wcmlStrings, $sitepress, $this->woocommerce, $this->wpdb );

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

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( $context, $name, $language_code )->willReturn( $trnsl_name );

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

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( $context, $name, $language_code )->willReturn( $trnsl_name );

		$filtered_name = $subject->wcml_get_translated_email_string( $context, $name, $order_id, $language_code );
		$this->assertEquals( $trnsl_name, $filtered_name );
	}

	/**
	 * @test
	 * @group wcml-3428
	 */
	public function itShouldGetStringTranslationFromAdminString() {
		$domain          = 'my-context';
		$name            = 'my-name';
		$translatedValue = 'The translation';
		$lang            = 'fr';

		$subject = $this->get_subject();

		$this->wcmlStrings
			->method( 'get_translated_string_by_name_and_context' )
			->with( $domain, $name, $lang )
			->willReturn( $translatedValue );

		$this->assertEquals(
			$translatedValue,
			$subject->getStringTranslation( $domain, $name, $lang )
		);
	}

	/**
	 * @test
	 * @group wcml-3428
	 */
	public function itShouldGetStringTranslationFromGettext() {
		$domain          = 'my-context';
		$name            = 'my-name';
		$originalValue   = 'The string';
		$originalDomain  = 'woocommerce-super-addon';
		$translatedValue = 'The translation';
		$lang            = 'fr';

		$switchLang = \Mockery::mock( 'overload:WPML_Temporary_Switch_Language' );
		$switchLang->shouldReceive( 'restore_lang' )->once();

		$subject = $this->get_subject();

		$this->wcmlStrings
			->method( 'get_translated_string_by_name_and_context' )
			->with( $domain, $name, $lang )
			->willReturn( false );

		\WP_Mock::userFunction( '__' )
			->with( $originalValue, $originalDomain )
			->andReturn( $translatedValue );

		$this->assertEquals(
			$translatedValue,
			$subject->getStringTranslation( $domain, $name, $lang, $originalValue, $originalDomain )
		);
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
	 * @group wcml-3545
	 */
	public function it_should_get_email_language_from_admin_user_settings_for_new_order_admin_email(){

		$order_id = 101;
		$recipient = 'admin@test.com';

		$user = new stdClass();
		$user->ID = 1;
		$user_language = 'en';

		WP_Mock::expectFilterAdded( 'woocommerce_new_order_email_allows_resend', function() {}, 20 );

		WP_Mock::userFunction( 'get_user_by', array(
			'args' => array( 'email', $recipient ),
			'return' => $user
		));

		WP_Mock::userFunction( 'get_current_user_id', array(
			'return' => $user->ID
		));

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => false
		) );

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

		\WP_Mock::expectFilterAdded(
			'woocommerce_email_enabled_new_order',
			WCML_Emails::getPreventDuplicatedNewOrderEmail( $order_id ),
			PHP_INT_MAX,
			2
		);

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

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => false
		) );

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

		\WP_Mock::expectFilterAdded(
			'woocommerce_email_enabled_new_order',
			WCML_Emails::getPreventDuplicatedNewOrderEmail( $order_id ),
			PHP_INT_MAX,
			2
		);

		$subject = $this->get_subject();

		$subject->new_order_admin_email( $order_id );
	}

	/**
	 * @test
	 * @group wcml-2381
	 */
	public function itShouldPreventDuplicatedNewOrderEmail() {
		$orderId = 123;

		$isEmailEnabled = WCML_Emails::getPreventDuplicatedNewOrderEmail( $orderId );

		$order = Mockery::mock( WC_Order::class, [ 'get_id' => $orderId ] );
		$this->assertFalse( $isEmailEnabled( true, $order ) );

		$anotherOrder = Mockery::mock( WC_Order::class, [ 'get_id' => 999 ] );
		$this->assertFalse( $isEmailEnabled( false, $anotherOrder ) );
		$this->assertTrue( $isEmailEnabled( true, $anotherOrder ) );

	}

	/**
	 * @test
	 */
	public function it_should_filter_low_stock_admin_notification() {

		$recipient     = 'admin@test.com';
		$user          = new stdClass();
		$user->ID      = 1;
		$user_language = 'es';

		$order_product_id          = 12;
		$product_id_admin_language = 14;
		$product                   = $this->getMockBuilder( 'WC_Product' )
		                                  ->disableOriginalConstructor()
		                                  ->setMethods( [ 'get_id' ] )
		                                  ->getMock();
		$product->method( 'get_id' )->willReturn( $order_product_id );

		WP_Mock::userFunction( 'get_user_by', [
			'args'   => [ 'email', $recipient ],
			'return' => $user
		] );

		WP_Mock::userFunction( 'get_option', [
			'args'   => [ 'woocommerce_stock_email_recipient' ],
			'return' => $recipient
		] );

		WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $order_product_id, 'product', true, $user_language ],
			'return' => $product_id_admin_language
		] );

		$product_in_admin_language = $this->getMockBuilder( 'WC_Product' )
		                                  ->disableOriginalConstructor()
		                                  ->getMock();

		WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $product_id_admin_language ],
			'return' => $product_in_admin_language
		] );

		$this->wcEmails = $this->getMockBuilder( 'WC_Emails' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'low_stock' ] )
		                       ->getMock();

		$this->wcEmails->expects( $this->once() )->method( 'low_stock' )->with( $product_in_admin_language );

		WP_Mock::userFunction( 'remove_action', [
			'args'   => [ 'woocommerce_low_stock_notification', [ $this->wcEmails, 'low_stock' ] ],
			'return' => $product_in_admin_language
		] );

		$this->sitepress->method( 'get_user_admin_language' )->with( $user->ID, true )->willReturn( $user_language );

		$subject = $this->get_subject();

		$subject->low_stock_admin_notification( $product );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_low_stock_admin_notification() {

		$this->wcEmails = $this->getMockBuilder( 'WC_Emails' )
		                       ->disableOriginalConstructor()
		                       ->getMock();

		$subject = $this->get_subject();

		$subject->low_stock_admin_notification( new stdClass() );
	}


	/**
	 * @test
	 */
	public function it_should_filter_no_stock_admin_notification() {

		$recipient     = 'admin@test.com';
		$user          = new stdClass();
		$user->ID      = 1;
		$user_language = 'es';

		$order_product_id          = 12;
		$product_id_admin_language = 14;
		$product                   = $this->getMockBuilder( 'WC_Product' )
		                                  ->disableOriginalConstructor()
		                                  ->setMethods( [ 'get_id' ] )
		                                  ->getMock();
		$product->method( 'get_id' )->willReturn( $order_product_id );

		WP_Mock::userFunction( 'get_user_by', [
			'args'   => [ 'email', $recipient ],
			'return' => $user
		] );

		WP_Mock::userFunction( 'get_option', [
			'args'   => [ 'woocommerce_stock_email_recipient' ],
			'return' => $recipient
		] );

		WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $order_product_id, 'product', true, $user_language ],
			'return' => $product_id_admin_language
		] );

		$product_in_admin_language = $this->getMockBuilder( 'WC_Product' )
		                                  ->disableOriginalConstructor()
		                                  ->getMock();

		WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $product_id_admin_language ],
			'return' => $product_in_admin_language
		] );

		$this->wcEmails = $this->getMockBuilder( 'WC_Emails' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'no_stock' ] )
		                       ->getMock();

		$this->wcEmails->expects( $this->once() )->method( 'no_stock' )->with( $product_in_admin_language );

		WP_Mock::userFunction( 'remove_action', [
			'args'   => [ 'woocommerce_no_stock_notification', [ $this->wcEmails, 'no_stock' ] ],
			'return' => $product_in_admin_language
		] );

		$this->sitepress->method( 'get_user_admin_language' )->with( $user->ID, true )->willReturn( $user_language );

		$subject = $this->get_subject();

		$subject->no_stock_admin_notification( $product );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_no_stock_admin_notification() {

		$this->wcEmails = $this->getMockBuilder( 'WC_Emails' )
		                       ->disableOriginalConstructor()
		                       ->getMock();

		$subject = $this->get_subject();

		$subject->no_stock_admin_notification( new stdClass() );
	}

	/**
	 * @test
	 * @dataProvider email_string_key
	 */
	public function it_should_filter_emails_strings_order_is_object( $emailOption, $textKey ) {

		$value            = rand_str();
		$old_value        = '';
		$translated_value = rand_str();
		$language         = 'es';
		$order_id         = 12;

		$order_object = $this->getMockBuilder( 'WC_Order' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_id' ) )
		               ->getMock();

		$order_object->method( 'get_id' )
		       ->willReturn( $order_id );

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => $language
		] );

		list( $wc_email_object, $language ) = $this->get_wc_email_mock( $order_object, $emailOption, $language );

		$subject = $this->get_subject();

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_' . $emailOption . '_settings', '[woocommerce_' . $emailOption . '_settings]' . $textKey, $language )->willReturn( $translated_value );

		$translated_value = $subject->filter_emails_strings( $value, $wc_email_object, $old_value, $textKey );
	}

	/**
	 * @test
	 * @dataProvider email_string_key
	 */
	public function it_should_filter_emails_strings_order_is_object_without_id( $emailOption, $textKey ) {

		$value            = rand_str();
		$old_value        = '';
		$translated_value = rand_str();
		$language         = null;

		$order_object = $this->getMockBuilder( 'WC_Order' )
		                     ->disableOriginalConstructor()
		                     ->getMock();

		list( $wc_email_object, $language ) = $this->get_wc_email_mock( $order_object, $emailOption, $language );

		$subject = $this->get_subject();

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_' . $emailOption . '_settings', '[woocommerce_' . $emailOption . '_settings]' . $textKey, $language )->willReturn( $translated_value );

		$translated_value = $subject->filter_emails_strings( $value, $wc_email_object, $old_value, $textKey );
	}

	/**
	 * @test
	 * @dataProvider email_string_key
	 */
	public function it_should_filter_emails_strings_order_is_array( $emailOption, $textKey ) {

		$value            = rand_str();
		$old_value        = '';
		$translated_value = rand_str();
		$language         = 'es';
		$order_id         = 12;

		$order_object = [ 'ID' => $order_id ];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => $language
		] );

		list( $wc_email_object, $language ) = $this->get_wc_email_mock( $order_object, $emailOption, $language );

		$subject = $this->get_subject();

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_' . $emailOption . '_settings', '[woocommerce_' . $emailOption . '_settings]' . $textKey, $language )->willReturn( $translated_value );

		$translated_value = $subject->filter_emails_strings( $value, $wc_email_object, $old_value, $textKey );
	}

	/**
	 * @test
	 * @dataProvider email_string_key
	 */
	public function it_should_filter_emails_strings_order_is_array_without_id( $emailOption, $textKey ) {

		$value            = rand_str();
		$old_value        = '';
		$translated_value = rand_str();
		$language         = null;

		$order_object = [];

		list( $wc_email_object, $language ) = $this->get_wc_email_mock( $order_object, $emailOption, $language );

		$subject = $this->get_subject();

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_' . $emailOption . '_settings', '[woocommerce_' . $emailOption . '_settings]' . $textKey, $language )->willReturn( $translated_value );

		$translated_value = $subject->filter_emails_strings( $value, $wc_email_object, $old_value, $textKey );
	}

	/**
	 * @test
	 * @dataProvider email_string_key
	 */
	public function it_should_filter_emails_strings_order_is_false( $emailOption, $textKey ) {

		$value            = rand_str();
		$old_value        = '';
		$translated_value = rand_str();
		$language         = null;

		list( $wc_email_object, $language ) = $this->get_wc_email_mock( false, $emailOption, $language );

		$subject = $this->get_subject();

		$this->wcmlStrings->method( 'get_translated_string_by_name_and_context' )->with( 'admin_texts_woocommerce_' . $emailOption . '_settings', '[woocommerce_' . $emailOption . '_settings]' . $textKey, $language )->willReturn( $translated_value );

		$translated_value = $subject->filter_emails_strings( $value, $wc_email_object, $old_value, $textKey );
	}

	private function get_wc_email_mock( $order_object, $emailOption, $language ){

		$object         = $this->getMockBuilder( 'WC_Email' )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$object->id     = $emailOption;

		$adminEmails = wpml_collect([
			'new_order',
			'cancelled_order',
			'failed_order',
		]);

		$object->object = $order_object;

		if ( $adminEmails->contains( $object->id ) ) {
			$user     = new stdClass();
			$user->ID = 1;
			$language = 'en';

			$object->recipient = 'admin@test.com';

			WP_Mock::userFunction( 'get_user_by', [
				'args'   => [ 'email', $object->recipient ],
				'return' => $user
			] );

			$this->sitepress->expects( $this->once() )->method( 'get_user_admin_language' )->with( $user->ID, true )->willReturn( $language );
		}

		return [ $object, $language ];
	}

	public function email_string_key() {

		$emailOptions = wpml_collect( [
			'new_order',
			'cancelled_order',
			'failed_order',
			'customer_on_hold_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_refunded_order',
			'customer_invoice',
			'customer_note',
			'customer_reset_password',
			'customer_new_account'
		] );

		$textKeys = wpml_collect( [
			'subject',
			'subject_downloadable',
			'subject_partial',
			'subject_full',
			'subject_paid',
			'heading',
			'heading_paid',
			'heading_downloadable',
			'heading_partial',
			'heading_full',
			'additional_content'
		] );

		$data = [];

		$emailOptions->each( function ( $emailOption ) use ( $textKeys, &$data ) {
			$textKeys->each( function ( $textKey ) use ( $emailOption, &$data ) {
				$data[] = [ $emailOption, $textKey ];
			} );
		} );

		return $data;
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_emails_strings_for_key_not_matched(){

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

		$this->assertEquals( $value, $subject->filter_emails_strings( $value, $object, $value, $key ) );

	}

	/**
	 * @test
	 */
	public function it_should_get_email_context_and_name() {

		$object     = $this->getMockBuilder( 'WC_Email_Customer_Completed_Order' )
		                   ->disableOriginalConstructor()
		                   ->getMock();
		$object->id = 'customer_completed_order';

		$subject = $this->get_subject();

		list( $context, $name ) = $subject->get_email_context_and_name( $object );

		$this->assertEquals( 'admin_texts_woocommerce_customer_completed_order_settings', $context );
		$this->assertEquals( '[woocommerce_customer_completed_order_settings]', $name );
	}

	/**
	 * @test
	 */
	public function it_should_get_main_email_context_and_name_for_partial_refunded_order() {

		$object     = $this->getMockBuilder( 'WC_Email_Customer_Refunded_Order' )
		                   ->disableOriginalConstructor()
		                   ->getMock();
		$object->id = 'customer_partial_refunded_order';

		$subject = $this->get_subject();

		list( $context, $name ) = $subject->get_email_context_and_name( $object );

		$this->assertEquals( 'admin_texts_woocommerce_customer_refunded_order_settings', $context );
		$this->assertEquals( '[woocommerce_customer_refunded_order_settings]', $name );
	}

	/**
	 * @test
	 */
	public function it_should_get_order_language_from_order_meta() {

		$order_id = 10;
		$language = 'es';

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => $language
		] );

		$subject = $this->get_subject();

		$this->assertEquals( $language, $subject->get_order_language( $order_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_order_language_from_order_if_it_passed_as_array() {

		$order_id = [ 'order_id' => 11 ];
		$language = 'es';

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $order_id['order_id'], 'wpml_language', true ],
			'return' => $language
		] );

		$subject = $this->get_subject();

		$this->assertEquals( $language, $subject->get_order_language( $order_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_order_language_from_rest_api() {

		$object          = $this->getMockBuilder( 'WC_Data' )
		                        ->disableOriginalConstructor()
		                        ->getMock();
		$order_id        = 12;
		$language        = 'es';
		$request['lang'] = $language;

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => false
		] );

		$subject = $this->get_subject();
		$subject->set_rest_language( $object, $request );

		$this->assertEquals( $language, $subject->get_order_language( $order_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_force_translating_admin_texts_when_refreshing_language() {
		$order_id = 123;
		$options  = [
			'woocommerce_checkout_privacy_policy_text',
			'woocommerce_email_footer_text',
			'woocommerce_email_from_address',
			'woocommerce_email_from_name',
			'woocommerce_price_decimal_sep',
			'woocommerce_price_thousand_sep',
			'woocommerce_registration_privacy_policy_text',
		];

		WP_Mock::expectAction( 'wpml_st_force_translate_admin_options', $options );

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $order_id, 'wpml_language', true ],
			'return' => 'en'
		] );

		WP_Mock::userFunction( 'get_current_user_id', [
			'return' => 321
		] );

		$subject = $this->get_subject();
		$subject->refresh_email_lang( $order_id );
	}

}
