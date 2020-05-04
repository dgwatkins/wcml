<?php

/**
 * Class Test_woocommerce_wcml
 * Doesn't doo much for now before refactoring the woocommerce_wpml class with a proper constructor and dependency injections
 */
class Test_woocommerce_wcml extends OTGS_TestCase {


	public function setUp() {
		parent::setUp();

		if( !defined( 'WCML_MULTI_CURRENCIES_DISABLED' ) ){
			define( 'WCML_MULTI_CURRENCIES_DISABLED', false );
		}
		if( !defined( 'WCML_CART_SYNC' ) ){
			define( 'WCML_CART_SYNC', true );
		}

		$that = $this;

		\WP_Mock::wpFunction( 'get_option', array(
			'args' => array( '_wcml_settings' ),
			'return' => null
		) );

	}

	public function tearDown(){
		parent::tearDown();
		global$sitepress, $woocommerce_wpml, $wpdb;
		unset( $sitepress, $woocommerce_wpml, $wpdb );
	}

	private function get_subject(){
		return new woocommerce_wpml();
	}

	/**
	 * @test
	 */
	public function creating_instance_in_admin(){
		global $sitepress;

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		// Multi-currency ON
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$woocommerce = $this->getMockBuilder( 'woocommerce' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		$woocommerce->version = '3.0.0';
		\WP_Mock::wpFunction( 'WC', array( 'return' => $woocommerce, ) );


		$sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
								->setMethods( array( 'get_settings' ) )
		                        ->getMock();
		$sitepress->method( 'get_settings' )->willReturn( array() );

		$woocommerce_wpml = new woocommerce_wpml();

	}

	/**
	 * @test
	 */
	public function add_hooks(){
		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'wpml_loaded', array( $subject, 'load' ) );
		\WP_Mock::expectActionAdded( 'init', array( $subject, 'init' ), 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function load(){
		$subject = $this->get_subject();

		\WP_Mock::expectAction( 'wcml_loaded' );
		$subject->load();
	}

	/**
	 * @test
	 */
	public function it_should_update_settings_in_settings_array(){
		$subject = $this->get_subject();

		WP_Mock::userFunction( 'update_option', array(
			'args' => array( '_wcml_settings', $subject->settings ),
			'times' => 1,
		));

		$subject->update_setting( 'file_path_sync', 1 );
	}

	/**
	 * @test
	 */
	public function it_should_update_settings_in_options_table(){
		$subject = $this->get_subject();

		WP_Mock::userFunction( 'update_option', array(
			'args' => array( 'wcml_sync_media', true, true ),
			'times' => 1,
		));

		$subject->update_setting( 'sync_media', true, true );
	}


	/**
	 * @test
	 */
	public function it_should_get_setting_from_settings_array(){
		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( 'wcml_file_path_sync', null ),
			'times' => 0,
		));

		$this->assertEquals( $subject->settings[ 'file_path_sync' ], $subject->get_setting( 'file_path_sync' ) );
	}


	/**
	 * @test
	 */
	public function it_should_get_setting_from_options_table(){
		$subject = $this->get_subject();
		$setting_value = false;

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( 'wcml_sync_media', true ),
			'return' => $setting_value,
			'times' => 1,
		));

		$this->assertEquals( $setting_value, $subject->get_setting( 'sync_media', true ) );
	}


}
