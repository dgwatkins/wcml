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
			->setMethods( array( 'get_wp_api' ) )
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
}
