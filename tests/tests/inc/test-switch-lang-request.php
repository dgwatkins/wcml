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
	 */
	public function detect_user_switch_language( $first_lang, $second_lang, $doing_ajax ) {
		$this->doing_ajax_mock = $doing_ajax;

		$subject = $this->get_subject();
		$subject->set_requested_lang( $second_lang );
		$this->set_request_cookie( $subject->get_cookie_name(), $first_lang );
		$subject->detect_user_switch_language();

		if ( ! $this->doing_ajax_mock && $first_lang !== $second_lang ) {
			$this->assertSame( 1, did_action( 'wcml_user_switch_language' ) );
		} else {
			$this->assertSame( 0, did_action( 'wcml_user_switch_language' ) );
		}
	}

	public function detect_user_switch_language_data_provider() {
		$active_langs  = $this->active_languages;
		$first_lang    = array_rand( $active_langs );
		unset( $active_langs[ $first_lang ] );
		$second_lang   = array_rand( $active_langs );

		return array (
			'From first lang to first lang'  => array( $first_lang, $first_lang, false ),
			'From first lang to second lang' => array( $first_lang, $second_lang, false ),
			'From first lang to second lang with ajax' => array( $first_lang, $second_lang, true ),
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

	/**
	 * @param string $name
	 * @param string $value
	 */
	private function set_request_cookie( $name, $value ) {
		$_COOKIE[ $name ] = $value;
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
}