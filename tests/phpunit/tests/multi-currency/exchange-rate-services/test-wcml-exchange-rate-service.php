<?php

namespace WCML\MultiCurrency\ExchangeRateServices;
/**
 * Class TestService
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
