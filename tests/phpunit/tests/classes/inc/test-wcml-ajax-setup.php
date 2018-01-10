<?php

class Test_WCML_Ajax_Setup extends OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );
	}


	/**
	 * @return WCML_Ajax_Setup
	 */
	private function get_subject(){
		$subject = new WCML_Ajax_Setup( $this->sitepress);

		return $subject;
	}

	/**
	 * @test
	 */
	public function on_init_hooks_before_wc_3_3()
	{
		\WP_Mock::wpFunction( 'wpml_is_ajax', array(
			'return' => true
		) );

		$check_version = '3.3';
		$wc_version = '2.7.0';

		$this->wp_api->expects( $this->once() )
			->method('constant')
			->with('WC_VERSION')
			->willReturn( $wc_version );
		$this->wp_api->expects($this->once())
			->method('version_compare')
			->with($wc_version, $check_version, '<')
			->willReturn(true);

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_params', array( $subject, 'filter_woocommerce_ajax_params' ) );
		$subject->init();
	}

	/**
	 * @test
	 */
	public function on_init_hooks_from_wc_3_3()
	{
		\WP_Mock::wpFunction( 'wpml_is_ajax', array(
			'return' => true
		) );

		$check_version = '3.3';
		$wc_version = '3.3';
		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );
		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_version, '<' )
			->willReturn( false );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_get_script_data', array( $subject, 'filter_woocommerce_ajax_params' ) );
		$subject->init();
	}

}
