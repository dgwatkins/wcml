<?php
/**
 * @group refactoring
 */
class Test_WCML_Tracking_Link extends OTGS_TestCase {

	/**
	 * @dataProvider data_provider
	 */
	public function test_generate( $term, $content, $id, $expected_params ) {
		$link = 'http://some-site.local/';
		$subject = new WCML_Tracking_Link();

		\WP_Mock::wpFunction( 'add_query_arg', array(
			'times'  => 1,
			'args'   => array( $expected_params, $link ),
			'return' => function ( $params, $link ) {
				return $link;
			},
		) );

		$result = $subject->generate( $link, $term, $content, $id );

		if ( $id ) {
			$this->assertEquals( $id, substr( $result, - strlen( $id ) ) );
		}
	}

	public function data_provider() {
		$params = array(
			'utm_source'   => 'wcml-admin',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'WCML',
		);

		return array(
			array( false, false, false, $params + array( 'utm_term' => 'WPML', 'utm_content' => 'required-plugins' ) ),
			array(
				'term_value',
				false,
				false,
				$params + array( 'utm_term' => 'term_value', 'utm_content' => 'required-plugins' ),
			),
			array(
				false,
				'content_value',
				false,
				$params + array( 'utm_term' => 'WPML', 'utm_content' => 'content_value' ),
			),
			array( false, false, 4, $params + array( 'utm_term' => 'WPML', 'utm_content' => 'required-plugins' ) ),
		);
	}
}
