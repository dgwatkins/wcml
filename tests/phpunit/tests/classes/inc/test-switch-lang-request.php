<?php

class Test_WCML_Switch_Lang_Request extends OTGS_TestCase {

	/** @var WPML_Cookie $cookie */
	private $cookie;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;
	/** @var Sitepress */
	private $sitepress;

	public function setUp(){
		parent::setUp();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant' ) )
			->getMock();

		$this->cookie = $this->getMockBuilder('WPML_Cookie')
			->disableOriginalConstructor()
			->getMock();

		$this->sitepress = $this->getMockBuilder('SitePress')
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_setting', 'get_default_language' ) )
		                        ->getMock();
	}

	private function get_subject( ){

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => false,
			'times'  => 1
		) );

		$this->sitepress->expects( $this->once() )->method( 'get_setting' )->willReturn( rand_str( ) );

		return new WCML_Switch_Lang_Request( $this->cookie, $this->wp_api, $this->sitepress );

	}

	/**
	 * @test
	 */
	public function add_hooks(){

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => false,
			'times'  => 1
		) );

		$this->wp_api->expects( $this->once() )
		             ->method( 'constant' )
		             ->with( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' )
		             ->willReturn( true );

		$this->sitepress->expects( $this->once() )->method( 'get_setting' )->willReturn( true );

		$subject = $this->get_subject( );

		\WP_Mock::expectActionAdded( 'wpml_before_init', array( $subject, 'detect_user_switch_language' ) );

		$subject->add_hooks();
	}

}
