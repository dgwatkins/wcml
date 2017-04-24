<?php

class Test_WCML_Product_Bundles_Legacy extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
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

		$this->tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			->disableOriginalConstructor()
			->getMock();

	}

	private function get_subject(){
		return new WCML_Product_Bundles_Legacy( $this->sitepress, $this->woocommerce_wpml, $this->tp );
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
		$this->assertEquals( array( '_bundle_data' ), $fields_to_hide );

	}
}
