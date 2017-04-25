<?php

class Test_WCML_Emails extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
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

	}

	private function get_subject( ){

		return new WCML_Emails( $this->woocommerce_wpml, $this->sitepress );

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

		$this->wp_api->method( 'constant' )->with( 'WPML_ST_VERSION' )->willReturn( '2.5.2' );
		
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language_code,
			'times'  => 1,
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
	function test_filter_payment_method_string(){

		$title = rand_str();
		$translated_title = rand_str();
		$object_id = rand( 1, 100 );
		$meta_key = '_payment_method_title';
		$single = true;
		$language_code = 'fr';

		$payment_gateway = new stdClass();
		$payment_gateway->title = rand_str();
		$payment_gateway->id = rand( 1, 100 );

		$this->woocommerce_wpml->gateways = $this->getMockBuilder('WCML_WC_Gateways')
			->disableOriginalConstructor()
			->setMethods( array( 'translate_gateway_title' ) )
			->getMock();

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array(
			'args'   => array( $object_id ),
			'return' => $payment_gateway
		) );

		$this->sitepress->method( 'get_current_language' )->willReturn( $language_code );

		$this->woocommerce_wpml->gateways->method( 'translate_gateway_title' )
			->with( $payment_gateway->title, $payment_gateway->id, $language_code )
			->willReturn( $translated_title );

		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 2, 'return' => true ) );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'filter_payment_method_string' ), 10, 4 );

		$filtered_payment_method_string = $subject->filter_payment_method_string( $title, $object_id, $meta_key, $single );

		$this->assertEquals( $translated_title, $filtered_payment_method_string );

		$object_id = rand( 1, 100 );

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array(
			'args'   => array( $object_id ),
			'return' => false
		) );

		$filtered_payment_method_string = $subject->filter_payment_method_string( $title, $object_id, $meta_key, $single );

		$this->assertEquals( $title, $filtered_payment_method_string );
	}
}
