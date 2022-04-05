<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

/**
 * @group multicurrency
 * @group exchange-rate-services
 */
class TestService extends \OTGS_TestCase {

	/** @var array */
	private $settings = [];

	/**
	 * @return Concrete
	 */
	private function getSubject() {

		\WP_Mock::userFunction( 'get_option', [ 'return' => $this->settings ] );
		\WP_Mock::userFunction( 'update_option', [] );

		return new Concrete();
	}

	/**
	 * @test
	 */
	public function getSettings() {
		$this->settings[ 'a_key' ] = 'A value';

		$subject = $this->getSubject();
		$this->assertSame( $this->settings, $subject->getSettings() );
	}

	/**
	 * @test
	 */
	public function saveSetting() {
		$key   = 'setting-key';
		$value = 'Setting value';

		$subject = $this->getSubject();
		$subject->saveSetting( $key, $value );

		$settings = $subject->getSettings();

		$this->assertNotNull( $settings[ $key ] );
		$this->assertSame( $settings[ $key ], $value );
	}

	/**
	 * @test
	 */
	public function getSetting() {
		$key   = 'setting-key';
		$value = 'Setting value';

		$subject = $this->getSubject();
		$subject->saveSetting( $key, $value );

		$this->assertSame( $value, $subject->getSetting( $key ) );
	}

	/**
	 * @test
	 */
	public function saveLastError() {
		$error = 'Random error';

		\WP_Mock::userFunction( 'date_i18n', [ 'return' => date( 'F j, Y g:i a' ) ] );

		$subject = $this->getSubject();
		$subject->saveLastError( $error );

		$setting = $subject->getSetting( 'last_error' );

		$this->assertNotNull( $setting['text'] );
	}

	/**
	 * @test
	 */
	public function getLastError() {
		$error = 'Random error';

		$subject = $this->getSubject();
		$subject->saveLastError( $error );

		$last_error = $subject->getSetting( 'last_error' );

		$this->assertNotNull( $last_error['text'] );
		$this->assertSame( $error, $last_error['text'] );

		$subject->clearLastError();
		$this->assertFalse( $subject->getLastError() );
	}

	/**
	 * @test
	 */
	public function clearLastError() {
		$error = 'Random error';

		$subject = $this->getSubject();
		$subject->saveLastError( $error );

		$subject->clearLastError();
		$this->assertFalse( $subject->getLastError() );
	}

	/**
	 * @test
	 * @dataProvider dp_should_get_formatted_error
	 * @group wcml-3996
	 *
	 * @param \stdClass $response
	 * @param string    $expected_error
	 *
	 * @return void
	 */
	public function it_should_get_formatted_error( $response, $expected_error ) {
		$this->assertEquals( $expected_error, Service::get_formatted_error( $response ) );
	}

	public function dp_should_get_formatted_error() {
		$code_string = 'the code';
		$code_int    = 123;
		$message     = 'the message';

		return [
			'no parsable data' => [
				(object) [ 'foo' => 'bar' ],
				'Cannot get exchange rates. Connection failed.', // default string
			],
			'error as string property' => [
				(object) [ 'error' => $code_string ],
				"error: $code_string",
			],
			'error as int property' => [
				(object) [ 'error' => $code_int ],
				"error: $code_int",
			],
			'error code as string' => [
				(object) [ 'error' => (object) [ 'code' => $code_string ] ],
				"error_code: $code_string",
			],
			'error code as int' => [
				(object) [ 'error' => (object) [ 'code' => $code_int ] ],
				"error_code: $code_int",
			],
			'all properties' => [
				(object) [
					'error'       => (object) [
						'code'    => $code_int,
						'type'    => $code_string,
						'info'    => $message,
						'message' => $message,
					],
					'message'     => $message,
					'description' => $message,
				],
				"error_code: $code_int - error_type: $code_string - error_info: $message - error_message: $message - message: $message - description: $message",
			],
			'strip tags' => [
				(object) [ 'error' => 'Some <b>important</b> content' ],
				'error: Some important content',
			],
		];
	}
}

class Concrete extends Service {

	/**
	 * @return string
	 */
	public function getId() {
		return 'concrete';
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Concrete';
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return 'https://concrete.demo/';
	}

	/**
	 * @return bool
	 */
	public function isKeyRequired() {
		return false;
	}

	/**
	 * @return string
	 */
	public function getApiUrl() {
		return 'http://concrete.demo/api/?source=%s&currencies=%s';
	}

	/**
	 * @return bool 
	 */
	public function is_key_required() {
		return true;
	}


	/**
	 * @param string $from
	 * @param array $tos
	 *
	 * @return mixed
	 */
	public function getRates( $from, $tos ){
		return [];
	}

}
