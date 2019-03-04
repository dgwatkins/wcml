<?php

/**
 * Class Test_WCML_Switch_Lang_Request
 * @group switch-language
 */
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

	/**
	 * @test
	 * @dataProvider dp_server_data
	 */
	public function it_gets_the_server_host_name( $http_host, $server_name, $server_port, $expected ) {
		unset( $_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'] );

		if ( $http_host ) {
			$_SERVER['HTTP_HOST'] = $http_host;
		}
		if ( $server_name ) {
			$_SERVER['SERVER_NAME'] = $server_name;
		}
		if ( $server_port ) {
			$_SERVER['SERVER_PORT'] = $server_port;
		}

		$subject = new WCML_Switch_Lang_Request( $this->cookie, $this->wp_api, $this->sitepress );

		$actual = $subject->get_server_host_name();

		$this->assertSame( $expected, $actual, json_encode(array($http_host, $server_name, $server_port, $actual)) );

		unset( $_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'] );
	}

	public function dp_server_data() {
		return array(
			'test1' => array(
				'HTTP_HOST'   => null,
				'SERVER_NAME' => 'server_name',
				'SERVER_PORT' => 80,
				'server_name',
			),
			'test2' => array(
				'HTTP_HOST'   => 'host',
				'SERVER_NAME' => 'server_name',
				'SERVER_PORT' => 80,
				'host',
			),
			'test3' => array(
				'HTTP_HOST'   => 'host',
				'SERVER_NAME' => null,
				'SERVER_PORT' => 80,
				'host',
			),
			'test4' => array(
				'HTTP_HOST'   => null,
				'SERVER_NAME' => 'server_name',
				'SERVER_PORT' => 443,
				'server_name',
			),
			'test5' => array(
				'HTTP_HOST'   => null,
				'SERVER_NAME' => 'server_name',
				'SERVER_PORT' => 123,
				'server_name:123',
			),
			'test6' => array(
				'HTTP_HOST'   => null,
				'SERVER_NAME' => null,
				'SERVER_PORT' => 80,
				'',
			),
			'test7' => array(
				'HTTP_HOST'   => null,
				'SERVER_NAME' => null,
				'SERVER_PORT' => null,
				'',
			),
			'test8' => array(
				'HTTP_HOST'   => null,
				'SERVER_NAME' => null,
				'SERVER_PORT' => 123,
				'',
			),
		);
	}

}
