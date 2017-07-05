<?php

/**
 * Class Test_WCML_Exchange_Rates_YahooFinance

 */
class Test_WCML_Exchange_Rates_YahooFinance extends OTGS_TestCase{

	public function setUp(){
		parent::setUp();
		\WP_Mock::wpPassthruFunction( '__' );
	}

	private function get_subject(){
		\WP_Mock::wpFunction( 'get_option', [ 'return' => [] ] );
		\WP_Mock::wpFunction( 'update_option', [] );

		return new WCML_Exchange_Rates_YahooFinance();
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
	 * @expectedExceptionMessage Error reading the exchange rate for
	 */
	public function get_rates_error_invalid_rate() {
		$subject = $this->get_subject();

		$from = rand_str();
		$to1  = rand_str(3);
		$to2  = rand_str(3);
		$tos  = [ $to1, $to2 ];

		$quotes = [
			$to1 => rand_str(),
			$to2 => rand_str(),
		];

		$expected_rates = [
			$to1 => $quotes[ $to1 ],
			$to2 => $quotes[ $to2 ],
		];

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => false ] );

		$data = [ 'body' => "\"{$to1}\",$quotes[$to1]\n\"{$to2}\",$quotes[$to2]" ];

		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $data ] );
		$rates = $subject->get_rates( $from, $tos );

		$this->assertSame( $expected_rates, $rates );
	}

	/**
	 * @test
	 */
	public function get_rates_with_success() {
		$subject = $this->get_subject();

		$from = rand_str();
		$to1  = rand_str(3);
		$to2  = rand_str(3);
		$to3  = 'N/A';
		$tos  = [ $to1, $to2, $to3 ];

		$quotes = [
			$to1 => (string) ( random_int(1, 1000) / 4 ),
			$to2 => (string) ( random_int(1000, 2000) / 4 ),
			$to3 => (string) ( random_int(2000, 3000) / 4 ),
		];

		$expected_rates = [
			$to1 => $quotes[ $to1 ],
			$to2 => $quotes[ $to2 ],
			$to3 => $quotes[ $to3 ],
		];

		\WP_Mock::wpFunction( 'is_wp_error', [ 'return' => false ] );

		$data = [ 'body' => "\"{$to1}\",$quotes[$to1]\n\"{$to2}\",$quotes[$to2]\nN/A,$quotes[$to3]" ];

		\WP_Mock::wpFunction( 'wp_safe_remote_get', [ 'return' => $data ] );
		$rates = $subject->get_rates( $from, $tos );

		$this->assertSame( $expected_rates, $rates );
	}

}