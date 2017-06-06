<?php

/**
 * @group  backwards-compatibility
 */
class Test_Missing_Functions extends OTGS_TestCase {

	public function test_rest_get_url_prefix(){

		// backup
		if ( isset( $GLOBALS['wp_version'] ) ) {
			$wp_version = $GLOBALS['wp_version'];
		}
		$GLOBALS['wp_version'] = '4.3.9';

		include_once WCML_PATH . '/inc/missing-php-functions.php';

		$this->assertTrue( function_exists( 'rest_get_url_prefix' ) );

		// restore
		if ( isset( $wp_version ) ) {
			$GLOBALS['wp_version'] = $wp_version;
		}

	}
}
