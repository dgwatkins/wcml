<?php

/**
 * Class Test_WCML_Exchange_Rates_Currencylayer
 */
class Test_WCML_Exchange_Rates_Currencylayer extends OTGS_TestCase{

	public function setUp(){
		parent::setUp();
		\WP_Mock::wpPassthruFunction( '__' );
	}

	private function get_subject(){
		\WP_Mock::wpFunction( 'get_option', [ 'return' => [] ] );
		\WP_Mock::wpFunction( 'update_option', [] );

		return new WCML_Exchange_Rates_Currencylayer();
	}

	/**
	 * @test
	 *
	 * @expectedException Exception
	 */
	public function get_rates_should_throw_exception(){

		$subject = $this->get_subject();

		$from = rand_str();
		$tos  = [ rand_str(), rand_str() ];

		$error_messages = [ rand_str(), rand_str() ];

		$wp_error = Mockery::mock( 'overload:WP_Error' );
		$wp_error->shouldReceive( 'get_error_messages' )
		         ->once()
		         ->andReturn( $error_messages );

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => true ] );
		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $wp_error ] );
		\WP_Mock::wpFunction( 'date_i18n', [ 'return' => date( 'F j, Y g:i a' ) ] );
		\WP_Mock::wpFunction( 'current_time', [ 'return' => time() ] );

		$subject->get_rates( $from, $tos );
	}

	/**
	 * @test
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage You have entered an incorrect API Access Key
	 */
	public function get_rates_with_json_error_no_api_key() {
		$subject = $this->get_subject();

		$from = rand_str();
		$tos  = [ rand_str(), rand_str() ];

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => false ] );

		$data = [
			'body' => json_encode(
				[
					'success' => false,
					'error' => [
						'info' => 'You have not supplied an API Access Key'
					]
				]
			)
		];
		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $data ] );
		$subject->get_rates( $from, $tos );
	}

	/**
	 * @test
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage Custom Error
	 */
	public function get_rates_with_json_error_custom_error() {
		$subject = $this->get_subject();

		$from = rand_str();
		$tos  = [ rand_str(), rand_str() ];

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => false ] );

		$data = [
			'body' => json_encode(
				[
					'success' => false,
					'error' => [
						'info' => 'Custom Error'
					]
				]
			)
		];
		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $data ] );
		$subject->get_rates( $from, $tos );
	}

	/**
	 * @test
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage Cannot get exchange rates. Connection failed.
	 */
	public function get_rates_with_json_error_connection_error() {
		$subject = $this->get_subject();

		$from = rand_str();
		$tos  = [ rand_str(), rand_str() ];

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => false ] );

		$data = [
			'body' => json_encode(
				[
					'success' => false,
					'error' => []
				]
			)
		];
		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $data ] );
		$subject->get_rates( $from, $tos );
	}

	/**
	 * @test
	 */
	public function get_rates_with_success() {
		$subject = $this->get_subject();

		$from = rand_str();
		$to1  = rand_str();
		$to2  = rand_str();
		$tos  = [ $to1, $to2 ];

		$quotes = [
			$from . $to1 => round ( random_int(1000000, 2000000) / 100000, 4 ),
			$from . $to2 => round ( random_int(2000000, 3000000) / 100000, 4 ),
		];

		$expected_rates = [
			$to1 => $quotes[ $from . $to1 ],
			$to2 => $quotes[ $from . $to2 ],
		];

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => false ] );

		$data = [
			'body' => json_encode(
				[
					'success' => true,
					'quotes' => $quotes
				]
			)
		];

		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $data ] );
		$rates = $subject->get_rates( $from, $tos );
		
		$this->assertSame( $expected_rates, $rates );
	}

}