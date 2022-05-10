<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * @group install
 */
class Test_WCML_Install extends OTGS_TestCase {

	/** @var woocommerce_wpml $woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;

	public function setUp(){
		global $wpdb;

		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->setMethods( [ 'is_wpml_prior_4_2', 'update_settings' ] )
			->getMock();

		$this->sitepress = $this->getMockBuilder( \WPML\Core\ISitePress::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_default_language', 'is_translated_taxonomy' ] )
			->getMock();

		$wpdb = $this->getMockBuilder( 'wpdb' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_results', 'get_col' ] )
			->getMock();

		$wpdb->prefix        = 'wp_';
		$wpdb->posts         = 'wp_posts';
		$wpdb->postmeta      = 'wp_postmeta';
		$wpdb->term_taxonomy = 'wp_term_taxonomy';

		$wpdb->method( 'get_results' )
			->willReturn( [] );

		$wpdb->method( 'get_col' )
			->willReturn( [] );

		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );
	}

	public function test_initialize() {
		$this->sitepress->method( 'get_default_language' )
			->willReturn( 'en' );

		$this->woocommerce_wpml->method( 'is_wpml_prior_4_2' )
			->willReturn( true );

		$this->woocommerce_wpml->settings = [
			'set_up'                         => 0,
			'is_term_order_synced'           => 'yes',
			'wc_admin_options_saved'         => 1,
			'sync_taxonomies_checked'        => 1,
			'downloaded_translations_for_wc' => 1,
			'rewrite_rules_flashed'          => 1,
		];

		WP_Mock::userFunction( 'is_admin', [
			'return' => true,
		] );

		WP_Mock::userFunction( 'get_role', [
			'return' => false,
		] );

		$currentUser = $this->getMockBuilder( \WP_User::class )
			->setMethods( [ 'get_role_caps' ] )
			->getMock();
		$currentUser->expects( $this->once() )->method( 'get_role_caps' );

		WP_Mock::userFunction( 'wp_get_current_user', [
			'return' => $currentUser,
		] );

		WP_Mock::userFunction( 'is_multisite', [
			'return' => false,
		] );

		WP_Mock::userFunction( 'current_user_can', [
			'return' => false,
		] );

		WP_Mock::userFunction( 'get_transient', [
			'return' => false,
		] );

		WP_Mock::passthruFunction( 'set_transient' );
		WP_Mock::passthruFunction( 'admin_url' );

		$time = 1628248596;
		FunctionMocker::replace( 'time', $time );

		WP_Mock::userFunction( 'wp_schedule_single_event', [
			'args'  => [ $time + 10, 'generate_category_lookup_table' ],
			'times' => 1,
		] );


		$subject = new WCML_Install();
		$subject->initialize( $this->woocommerce_wpml, $this->sitepress );

		$this->assertTrue( true );
	}

	public function tearDown() {
		global $wpdb;

		parent::tearDown();

		unset( $wpdb );
	}

}
