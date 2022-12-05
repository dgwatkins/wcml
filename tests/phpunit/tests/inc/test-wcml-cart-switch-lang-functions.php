<?php

class Test_WCML_Switch_Lang_Functions extends OTGS_TestCase {

	private function get_subject() {
		return new WCML_Cart_Switch_Lang_Functions();
	}

	/**
	 * @test
	 * @param array  $query
	 * @param string $expected
	 * @dataProvider sample_get_data
	 * @group anas
	 */
	public function test_get_current_url_return_with_custom_parameters( $query, $expected ) {
		WP_Mock::passThruFunction( 'home_url', [ 'times' => 1 ] );

		$_GET = $query;

		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.CSRF.NonceVerification.NoNonceVerification */
		$sanitized_query = array_map( 'WPML\API\Sanitize::string', $_GET );

		WP_Mock::userFunction( 'add_query_arg', [
			'times'  => 1,
			'args'   => [ $sanitized_query ],
			'return' => $expected,
		] );

		$subject     = $this->get_subject();
		$current_url = $subject->get_current_url();

		$this->assertSame( $expected, $current_url, '{ "expected": ' . $expected . ', "actual": ' . $current_url . '}' );

		/* phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected */
		unset( $_GET );
	}

	public function sample_get_data() {
		return [
			[
				'query' => [
					'test' => '1',
				],
				'https://wcml.test.com?test=1',
			],
			[
				'query' => [
					'test'  => '1',
					'testq' => 'value',
				],
				'https://wcml.test.com?test=1&testq=value',
			],
			[
				'query' => [],
				'https://wcml.test.com',
			],
			[
				'query' => [
					'test' => '<a href="https://wcml.test.com">1<a>',
				],
				'https://wcml.test.com?test=1',
			],
		];
	}
}
