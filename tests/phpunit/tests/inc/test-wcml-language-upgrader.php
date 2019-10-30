<?php

class Test_WCML_Language_Upgrader extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();
		if ( ! defined( 'WCML_JS_MIN' ) ) {
			define( 'WCML_JS_MIN', 'WCML_JS_MIN' );
		}
		if ( ! defined( 'WCML_VERSION' ) ) {
			define( 'WCML_VERSION', rand_str() );
		}
		if ( ! defined( 'WC_VERSION' ) ) {
			define( 'WC_VERSION', rand_str() );
		}

		\WP_Mock::passthruFunction( 'wp_register_script' );
		\WP_Mock::passthruFunction( 'wp_enqueue_script' );
		\WP_Mock::passthruFunction( 'wp_localize_script' );
	}

	/**
	 * @test
	 */
	public function it_adds_translation_info_in_check_for_update() {
		global $sitepress;
		$languages = [
			'en' => [ 'code' => 'en' ],
			'fr' => [ 'code' => 'fr' ],
		];
		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'get_active_languages' )->andReturn( $languages );
		$locale = 'fr_FR';
		$sitepress->shouldReceive( 'get_locale' )->with( 'fr' )->andReturn( $locale );

		$data = (object) [ 'translations' => [] ];

		$subject = new WCML_Languages_Upgrader();
		$subject->check_for_update( $data );

		unset( $data->translations[0]['updated'] ); // Drop the updated time because we can't compare reliably
		$this->assertEquals(
			[
				[
					'type'       => 'plugin',
					'slug'       => 'woocommerce',
					'language'   => $locale,
					'version'    => WC_VERSION,
					'package'    => 'https://downloads.wordpress.org/translation/plugin/woocommerce/' . WC_VERSION . '/' . $locale . '.zip',
					'autoupdate' => 1
				]
			],
			$data->translations
		);
	}

}
