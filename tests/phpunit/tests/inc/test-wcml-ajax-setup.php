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
			->setMethods( array( 'get_wp_api', 'get_current_language', 'get_default_language', 'switch_lang' ) )
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
	public function add_hooks()
	{
		\WP_Mock::wpFunction( 'wpml_is_ajax', array(
			'return' => true
		) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_get_script_data', array( $subject, 'add_language_parameter_to_ajax_url' ) );
		$subject->init();
	}

	/**
	 * @test
	 */
	public function it_adds_language_parameter_to_ajax_url()
	{
		$woocommerce_params['ajax_url'] = rand_str();
		$expected_ajax_url = rand_str();

		$this->wp_api->expects( $this->once() )
		             ->method('constant')
		             ->with('ICL_LANGUAGE_CODE')
		             ->willReturn( rand_str( 2 ) );

		$this->sitepress->method( 'get_current_language' )->willReturn( rand_str( 2 ) );
		$this->sitepress->method( 'get_default_language' )->willReturn( rand_str( 2 ) );

		\WP_Mock::wpFunction( 'add_query_arg', array(
			'times' => 1,
			'return' => $expected_ajax_url
		) );

		$subject = $this->get_subject();
		$filtered_params = $subject->add_language_parameter_to_ajax_url( $woocommerce_params );

		$this->assertEquals( array( 'ajax_url' => $expected_ajax_url ), $filtered_params );
	}

	/**
	 * @test
	 */
	public function it_does_not_add_language_parameter_to_ajax_url_for_default_language()
	{
		$woocommerce_params['ajax_url'] = rand_str();
		$lang_code = rand_str( 2 );

		$this->sitepress->method( 'get_current_language' )->willReturn( $lang_code );
		$this->sitepress->method( 'get_default_language' )->willReturn( $lang_code );

		$subject = $this->get_subject();
		$filtered_params = $subject->add_language_parameter_to_ajax_url( $woocommerce_params );

		$this->assertEquals( $woocommerce_params, $filtered_params );
	}

	/**
	 * @test
	 */
	public function it_does_not_add_language_parameter_to_ajax_url()
	{
		$woocommerce_params = array();

		$subject = $this->get_subject();
		$filtered_params = $subject->add_language_parameter_to_ajax_url( $woocommerce_params );

		$this->assertEquals( $woocommerce_params, $filtered_params );
	}

}
