<?php

/**
 * @group  backwards-compatibility
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_Missing_Functions extends OTGS_TestCase {

	public function test_rest_get_url_prefix(){

		\WP_Mock::userFunction( 'is_admin', array(
			'return' => false
		) );

		// backup
		if ( isset( $GLOBALS['wp_version'] ) ) {
			$wp_version = $GLOBALS['wp_version'];
		}
		$GLOBALS['wp_version'] = '4.3.9';

		$this->assertFalse( function_exists( 'rest_get_url_prefix' ) );

		include_once WCML_PATH . '/inc/missing-php-functions.php';

		$this->assertTrue( function_exists( 'rest_get_url_prefix' ) );

		// restore
		if ( isset( $wp_version ) ) {
			$GLOBALS['wp_version'] = $wp_version;
		}

	}
}
