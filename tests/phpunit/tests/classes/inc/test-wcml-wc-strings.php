<?php

class Test_WCML_WC_Strings extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp(){
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );
	}

	private function get_subject( ){

		return new WCML_WC_Strings( $this->woocommerce_wpml, $this->sitepress );

	}

	/**
	 * @test
	 */
	public function add_on_init_hooks(){

		$this->sitepress->method( 'get_current_language' )->willReturn( rand_str() );

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$check_wc_version = '3.0.0';
		$wc_version = '2.7.0';
		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );

		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_wc_version, '<' )
			->willReturn( true );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_item_name', array( $subject, 'translated_cart_item_name' ), 10, 3 );
		$subject->add_on_init_hooks();
	}

}
