<?php

class Test_WCML_Emails extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WooCommerce */
	private $woocommerce;
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
			->setMethods( array( 'mailer' ) )
			->getMock();

		$this->wp_api->method( 'constant' )->with( 'WPML_ST_VERSION' )->willReturn( '2.5.2' );
	}

	private function get_subject( ){

		return new WCML_Emails( $this->woocommerce_wpml, $this->sitepress, $this->woocommerce );

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

		$subject = $this->get_subject( );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language_code
		) );
		
		WP_Mock::onFilter( 'wpml_translate_single_string' )
			->with( false, $context, $name , $language_code )
			->reply( $trnsl_name );

		$filtered_name = $subject->wcml_get_translated_email_string( $context, $name, $order_id  );
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

}
