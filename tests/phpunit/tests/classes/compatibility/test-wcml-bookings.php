<?php

class Test_WCML_Bookings extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	public function setUp()
	{
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();

	}

	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress_mock( $wp_api = null ) {
		$sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api' ) )
			->getMock();

		if( null === $wp_api ){
			$wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();
	}

	/**
	 * @return WPML_Element_Translation_Package
	 */
	private function get_wpml_element_translation_package_mock() {
		return $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_subject( $sitepress = null, $woocommerce_wpml = null, $tp = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress_mock();
		}

		if( null === $tp ){
			$tp = $this->get_wpml_element_translation_package_mock();
		}

		return new WCML_Bookings( $sitepress, $woocommerce_wpml, $this->wpdb, $tp );
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		$sitepress_version = '3.7.0';
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$wp_api = $this->get_wpml_wp_api_mock();
		$wp_api->method( 'constant' )
			->with( 'ICL_SITEPRESS_VERSION' )
			->willReturn( $sitepress_version );

		$sitepress = $this->get_sitepress_mock( $wp_api );

		$subject = $this->get_subject( $sitepress );

		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array( $subject, 'replace_tm_editor_custom_fields_with_own_sections' ) );
		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function add_hooks_after_wpml_3_8(){

		$sitepress_version = '3.8.0';

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$wp_api = $this->get_wpml_wp_api_mock();
		$wp_api->method( 'constant' )
			->with( 'ICL_SITEPRESS_VERSION' )
			->willReturn( $sitepress_version );
		
		$wp_api->method( 'version_compare' )
			->with( $sitepress_version, '3.8.0', '<' )
			->willReturn( false );
	
		$sitepress = $this->get_sitepress_mock( $wp_api );

		$subject = $this->get_subject( $sitepress );

		\WP_Mock::expectFilterAdded( 'get_translatable_documents_all', array( $subject, 'filter_translatable_documents' ) );
		$subject->add_hooks();

	}
	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections(){

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_resource_base_costs', '_resource_block_costs' ), $fields_to_hide );

	}
}
