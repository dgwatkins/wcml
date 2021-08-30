<?php
/**
 * @group refactoring
 */
class Test_WCML_Tracking_Link extends OTGS_TestCase {

	/**
	 * @dataProvider data_provider
	 *
	 * @param mixed $id
	 */
	public function test_generate( $id ) {
		$link = 'http://some-site.local/';

		$params = [
			'utm_source'   => 'plugin',
			'utm_medium'   => 'gui',
			'utm_campaign' => 'wcml',
		];

		\WP_Mock::userFunction( 'add_query_arg', [
			'times'  => 1,
			'args'   => [ $params, $link ],
			'return' => function ( $params, $link ) {
				return $link;
			},
		] );

		$result = WCML_Tracking_Link::generate( $link, $id );

		if ( $id ) {
			$this->assertEquals( $id, substr( $result, - strlen( $id ) ) );
		}
	}

	public function data_provider() {
		return [
			[ false ],
			[ 4 ],
		];
	}
}
