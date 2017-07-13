<?php

/**
 * Class Test_WCML_Admin_Cookie
 */
class Test_WCML_Admin_Cookie extends OTGS_TestCase {

	public function tearDown(){
		unset( $_COOKIE );
		parent::tearDown();
	}

	private function get_subject( $name ){
		return new WCML_Admin_Cookie( $name );
	}

	/**
	 * @test
	 */
	public function set_value_without_explicit_expiration(){
		$name = rand_str();
		$subject = $this->get_subject( $name );

		$value = rand_str();
		\WP_Mock::wpFunction( 'wc_setcookie', [
			'args' => [ $name, $value, \Mockery::type('int') ],
			'return' => function( $name, $value ) {
				$_COOKIE[ $name ] = $value;
			}
		] );

		$subject->set_value( $value );

		$this->assertSame( $value, $subject->get_value() );
	}

	/**
	 * @test
	 */
	public function set_value_with_explicit_expiration(){
		$name = rand_str();
		$subject = $this->get_subject( $name );

		$value = rand_str();
		$expiration = random_int(1, 1000000);

		\WP_Mock::wpFunction( 'wc_setcookie', [
			'args' => [ $name, $value, $expiration ],
			'return' => function( $name, $value ) {
				$_COOKIE[ $name ] = $value;
			}

		] );

		$subject->set_value( $value, $expiration );

		$this->assertSame( $value, $subject->get_value() );
	}

	/**
	 * @test
	 */
	public function get_value(){
		$name = rand_str();
		$subject = $this->get_subject( $name );

		$value = rand_str();
		\WP_Mock::wpFunction( 'wc_setcookie', [
			'return' => function( $name, $value ) {
				$_COOKIE[ $name ] = $value;
			}
		] );

		$subject->set_value( $value );

		$this->assertSame( $value, $subject->get_value() );
	}

}