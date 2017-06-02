<?php

class Test_WCML_Tab_Manager extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var woocommerce */
	private $woocommerce;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;
	/** @var WPML_Element_Translation_Package */
	private $tp;


	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();

		$this->wpdb = $this->stubs->wpdb();

		$this->woocommerce = $this->getMockBuilder( 'WooCommerce' )
			->disableOriginalConstructor()
			->getMock();

		$this->tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			->disableOriginalConstructor()
			->getMock();

	}

	private function get_subject(){
		return new WCML_Tab_Manager( $this->sitepress, $this->woocommerce, $this->woocommerce_wpml, $this->wpdb, $this->tp );
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		$wcml_version = '4.0.0';

		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WCML_VERSION' )
			->willReturn( $wcml_version );

		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array( $subject, 'replace_tm_editor_custom_fields_with_own_sections' ) );
		$subject->add_hooks();

	}
	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections(){

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_product_tabs' ), $fields_to_hide );

	}
}
