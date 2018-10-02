<?php

class Test_WCML_Emails extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WooCommerce */
	private $woocommerce;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp(){
		parent::setUp();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language' ) )
			->getMock();


		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();

		$this->woocommerce = $this->getMockBuilder('WooCommerce')
			->disableOriginalConstructor()
			->setMethods( array( 'mailer', 'load_plugin_textdomain' ) )
			->getMock();

		$this->wp_api->method( 'constant' )->with( 'WPML_ST_VERSION' )->willReturn( '2.5.2' );

		$this->wpdb = $this->stubs->wpdb();
	}

	private function get_subject( ){

		return new WCML_Emails( $this->woocommerce_wpml, $this->sitepress, $this->woocommerce, $this->wpdb );

	}

	/**
	 * @test
	 * @group wcml-2549
	 */
	public function it_should_change_current_language() {
		$lang = 'pt-br';

		$sitepress = $this->getMockBuilder( 'SitePress' )
			->setMethods( array( 'switch_lang', 'get_locale' ) )
			->disableOriginalConstructor()
			->getMock();

		$subject = new WCML_Emails( $this->woocommerce_wpml, $sitepress, $this->woocommerce, $this->wpdb );

		\Mockery::mock( 'overload:WP_Locale' );

		\WP_Mock::userFunction( 'unload_textdomain', array() );
		\WP_Mock::userFunction( 'load_default_textdomain', array() );

		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( $lang );

		$subject->change_email_language( $lang );
	}

	/**
	 * @test
	 * @group wcml-2549
	 */
	public function it_should_not_change_current_language_when_page_is_shop_order() {
		$_POST['post_type'] = 'shop_order';
		$lang = 'pt-br';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->setMethods( array( 'switch_lang', 'get_locale' ) )
		                  ->disableOriginalConstructor()
		                  ->getMock();

		$subject = new WCML_Emails( $this->woocommerce_wpml, $sitepress, $this->woocommerce, $this->wpdb );

		\Mockery::mock( 'overload:WP_Locale' );

		\WP_Mock::userFunction( 'unload_textdomain', array() );
		\WP_Mock::userFunction( 'load_default_textdomain', array() );

		$sitepress->expects( $this->never() )->method( 'switch_lang' );
		$subject->change_email_language( $lang );
		unset( $_POST['post_type'] );
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

		$mailer =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_completed = $this->getMockBuilder( 'WC_Email_Customer_Completed_Order' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'trigger' ) )
		                            ->getMock();
		$wc_mailer_completed->enabled = rand_str();


		$mailer->emails = array( 'WC_Email_Customer_Completed_Order' => $wc_mailer_completed );

		$this->woocommerce->method( 'mailer' )->willReturn( $mailer );

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
	 */
	public function it_should_filter_new_order_email_heading(){


		$mailer =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_new_order = $this->getMockBuilder( 'WC_Email_New_Order' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'format_string' ) )
		                            ->getMock();
		$wc_mailer_new_order->heading = rand_str();

		$mailer->emails = array( 'WC_Email_New_Order' => $wc_mailer_new_order );

		$this->woocommerce->method( 'mailer' )->willReturn( $mailer );

		$translated_formatted_heading = rand_str();

		$wc_mailer_new_order->expects( $this->once() )
		                    ->method( 'format_string' )
		                    ->with( $wc_mailer_new_order->heading )
		                    ->willReturn( $translated_formatted_heading );

		$subject = $this->get_subject();

		$this->assertEquals( $translated_formatted_heading, $subject->new_order_email_heading( rand_str() ) );

	}

	/**
	 * @test
	 */
	public function it_should_filter_new_order_email_subject(){


		$mailer =  $this->getMockBuilder('WC_Emails')
		                ->disableOriginalConstructor()
		                ->getMock();

		$wc_mailer_new_order = $this->getMockBuilder( 'WC_Email_New_Order' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( array( 'format_string' ) )
		                            ->getMock();
		$wc_mailer_new_order->subject = rand_str();

		$mailer->emails = array( 'WC_Email_New_Order' => $wc_mailer_new_order );

		$this->woocommerce->method( 'mailer' )->willReturn( $mailer );

		$translated_formatted_subject = rand_str();

		$wc_mailer_new_order->expects( $this->once() )
		                    ->method( 'format_string' )
		                    ->with( $wc_mailer_new_order->subject )
		                    ->willReturn( $translated_formatted_subject );

		$subject = $this->get_subject();

		$this->assertEquals( $translated_formatted_subject, $subject->new_order_email_subject( rand_str() ) );

	}

}
