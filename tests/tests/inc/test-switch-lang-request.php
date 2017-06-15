<?php

class Test_WCML_Switch_Lang_Request extends WCML_UnitTestCase{

	private $active_languages = array( 'en' => array(), 'de' => array(), 'fr' => array() );
	private $default_language;
	private $cookie_buffer;
	private $doing_ajax_mock;
	private $original_SERVER;
	private $url_with_lang_code = 'http://example.com?lang=ru';

	function setUp() {
		parent::setUp();
		$this->original_SERVER = $_SERVER;
	}

	/**
	 * @test
	 * @dataProvider detect_user_switch_language_data_provider
	 *
	 * @param string $first_lang
	 * @param string $second_lang
	 * @param bool   $doing_ajax
	 * @param string $requested_url
	 */
	public function detect_user_switch_language( $first_lang, $second_lang, $doing_ajax, $requested_url ) {
		$this->doing_ajax_mock = $doing_ajax;

		$subject = $this->get_subject();
		$subject->set_requested_lang( $second_lang );
		$this->set_requested_url( $requested_url );
		$this->set_request_cookie( $subject->get_cookie_name(), $first_lang );
		$this->set_request_cookie( $subject->get_referer_url_cookie_name(), $requested_url );

		$subject->detect_user_switch_language();

		if ( ! $this->doing_ajax_mock ) {
			$this->assertEquals( $requested_url, $this->cookie_buffer[ $subject->get_referer_url_cookie_name() ] );
		} else {
			$this->assertNull( $this->cookie_buffer[ $subject->get_referer_url_cookie_name() ] );
		}
	}

	public function detect_user_switch_language_data_provider() {
		$active_langs  = $this->active_languages;
		$first_lang    = array_rand( $active_langs );
		unset( $active_langs[ $first_lang ] );
		$second_lang   = array_rand( $active_langs );
		$requested_url = 'http://' . rand_str( 12 ) . '/' . rand_str( 17 );

		return array (
			'From first lang to first lang'  => array( $first_lang, $first_lang, false, $requested_url ),
			'From first lang to second lang' => array( $first_lang, $second_lang, false, $requested_url ),
			'From first lang to second lang with ajax' => array( $first_lang, $second_lang, true, $requested_url ),
		);
	}

	/**
	 * @test
	 * @dataProvider get_request_url_data_provider
	 *
	 * @param array  $server_vars
	 * @param string $expected_url
	 */
	public function get_request_url( $server_vars, $expected_url ) {
		$_SERVER = array_merge( $_SERVER, $server_vars );

		$subject = $this->get_subject();
		$request_url = $subject->get_request_url();
		$this->assertEquals( $expected_url, $request_url );
	}

	/**
	 * @return array
	 */
	public function get_request_url_data_provider() {
		return array(
			'URL 1' => array(
				array(
					'HTTP_HOST'   => 'example.com',
					'REQUEST_URI' => '/',
				),
				'http://example.com/'
			),
			'URL 2' => array(
				array(
					'HTTP_HOST'   => 'example.com',
					'REQUEST_URI' => '/some-string-89/foo-12-bar/',
				),
				'http://example.com/some-string-89/foo-12-bar/'
			),
			'URL 3' => array(
				array(
					'HTTP_HOST'   => 'example.com',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
				),
				'http://example.com/some-string-again/?foo=bar&ring=bell'
			),
			'URL 4 with https' => array(
				array(
					'HTTP_HOST'   => 'example.com',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
					'HTTPS'       => 'on',
				),
				'https://example.com/some-string-again/?foo=bar&ring=bell'
			),
			'URL 5 with port 80' => array(
				array(
					'HTTP_HOST'   => 'example.com',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
					'SERVER_PORT' => '80',
				),
				'http://example.com/some-string-again/?foo=bar&ring=bell'
			),
			'URL 6 with port 443' => array(
				array(
					'HTTP_HOST'   => 'example.com',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
					'HTTPS'       => 'on',
					'SERVER_PORT' => '443',
				),
				'https://example.com/some-string-again/?foo=bar&ring=bell'
			),
			'URL 7 with port 1234' => array(
				array(
					'HTTP_HOST'   => 'example.com:1234',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
				),
				'http://example.com:1234/some-string-again/?foo=bar&ring=bell'
			),
			'URL 8 without HTTP_HOST' => array(
				array(
					'HTTP_HOST'   => null,
					'SERVER_NAME' => 'example.com',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
				),
				'http://example.com/some-string-again/?foo=bar&ring=bell'
			),
			'URL 9 without HTTP_HOST, with port 1234' => array(
				array(
					'HTTP_HOST'   => null,
					'SERVER_NAME' => 'example.com',
					'REQUEST_URI' => '/some-string-again/?foo=bar&ring=bell',
					'SERVER_PORT' => '1234',
				),
				'http://example.com:1234/some-string-again/?foo=bar&ring=bell'
			),
		);
	}

	private function get_subject() {
		$this->default_language = array_rand( $this->active_languages );

		$cookie = $this->getMockBuilder( 'WPML_Cookie' )->disableOriginalConstructor()->getMock();
		$cookie->method( 'set_cookie' )->willReturnCallback( array( $this, 'set_cookie_mock' ) );
		$cookie->method( 'get_cookie' )->willReturnCallback( array( $this, 'get_cookie_mock' ) );

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->getMock();
		$wp_api->method( 'constant' )->with( 'DOING_AJAX' )->willReturn( $this->doing_ajax_mock );
		$sitepress = $this->getMockBuilder( 'Sitepress' )->disableOriginalConstructor()->getMock();

		$subject = new WCML_Test_Request( $cookie, $wp_api, $sitepress );

		return $subject;
	}

	private function set_requested_url( $url ) {
		$_SERVER['HTTP_HOST']   = parse_url( $url, PHP_URL_HOST );
		$_SERVER['REQUEST_URI'] = parse_url( $url, PHP_URL_PATH );
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	private function set_request_cookie( $name, $value ) {
		$_COOKIE[ $name ] = $value;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function set_cookie_mock( $name, $value ) {
		$this->cookie_buffer[ $name ] = $value;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_cookie_mock( $name ) {
		return isset( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : '';
	}

	/**
	 * @param string $lang
	 *
	 * @return bool
	 */
	public function is_language_active_mock( $lang ) {
		return isset( $this->active_languages[ $lang ] );
	}

	public function tearDown() {
		$_SERVER = $this->original_SERVER;
		parent::tearDown();
	}
}


class WCML_Test_Request extends WCML_Switch_Lang_Request {

	private $requested_lang;

	/**
	 * @return null
	 */
	public function get_cookie_name() {
		return 'some_name';
	}

	/**
	 * @return null
	 */
	public function get_requested_lang() {
		return $this->requested_lang;
	}

	/**
	 * @param string $lang
	 */
	public function set_requested_lang( $lang ) {
		$this->requested_lang = $lang;
	}

	/**
	 * @return string
	 */
	public function get_referer_url_cookie_name() {
		return 'some-name';
	}

}