<?php

/**
 * Class Test_WCML_Exchange_Rate_Service
 */
class Test_WCML_Exchange_Rate_Service extends OTGS_TestCase {

	/** @var string  */
	private $id;
	/** @var string  */
	private $name;
	/** @var string  */
	private $url;
	/** @var string  */
	private $api_url;

	/** @var array */
	private $settings = [];

	/** @var bool  */
	protected $requires_key = false;

	/**
	 * @return WCML_Exchange_Rate_Service_Concrete
	 */
	private function get_subject(){

		$this->id = rand_str();
		$this->name = rand_str();
		$this->url = rand_str();
		$this->api_url = rand_str();

		\WP_Mock::wpFunction( 'get_option', [ 'return' => $this->settings ] );
		\WP_Mock::wpFunction( 'update_option', [] );

		return new WCML_Exchange_Rate_Service_Concrete( $this->id, $this->name, $this->api_url, $this->url );
	}

	/**
	 * @test
	 */
	public function get_name(){
		$subject = $this->get_subject();
		$this->assertSame( $this->name, $subject->get_name() );
	}

	/**
	 * @test
	 */
	public function get_url(){
		$subject = $this->get_subject();
		$this->assertSame( $this->url, $subject->get_url() );
	}

	/**
	 * @test
	 */
	public function get_settings(){
		$this->settings[ rand_str() ] = rand_str();
		$subject = $this->get_subject();
		$this->assertSame( $this->settings, $subject->get_settings() );
	}

	/**
	 * @test
	 */
	public function save_setting(){

		$key = rand_str();
		$value = rand_str();

		$subject = $this->get_subject();
		$subject->save_setting( $key, $value );

		$settings = $subject->get_settings();

		$this->assertNotNull( $settings[ $key ] );
		$this->assertSame( $settings[ $key ], $value );
	}

	/**
	 * @test
	 */
	public function get_setting(){
		$key = rand_str();
		$value = rand_str();
		$subject = $this->get_subject();
		$subject->save_setting( $key, $value );

		$this->assertSame( $value, $subject->get_setting( $key ) );
	}

	/**
	 * @test
	 */
	public function save_last_error(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'date_i18n', [ 'return' => date( 'F j, Y g:i a' ) ] );
		\WP_Mock::wpFunction( 'current_time', [ 'return' => time() ] );

		$error = rand_str();
		$subject->save_last_error( $error );

		$setting = $subject->get_setting( 'last_error' );

		$this->assertNotNull( $setting['text'] );
	}

	/**
	 * @test
	 */
	public function get_last_error() {
		$subject = $this->get_subject();
		$error = rand_str();
		$subject->save_last_error( $error );

		$last_error = $subject->get_setting( 'last_error' );

		$this->assertNotNull( $last_error['text'] );
		$this->assertSame( $error, $last_error['text'] );

		$subject->clear_last_error();
		$this->assertFalse( $subject->get_last_error() );
	}

	/**
	 * @test
	 */
	public function clear_last_error() {
		$subject = $this->get_subject();

		$error = rand_str();
		$subject->save_last_error( $error );

		$subject->clear_last_error();
		$this->assertFalse( $subject->get_last_error() );
	}

}

class WCML_Exchange_Rate_Service_Concrete extends WCML_Exchange_Rate_Service{

	/**
	 * @param string $from
	 * @param array $tos
	 *
	 * @return mixed
	 */
	public function get_rates( $from, $tos ){
		return [];
	}
}