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

	/**
	 * @test
	 */
	public function filter_my_account_bookings_tables_by_current_language(){

		$original_language = rand_str();
		$current_language = rand_str();
		$original_booking_id = mt_rand( 1, 100 );
		$translated_booking_id = mt_rand( 101, 200 );
		$original_booking_product_id = mt_rand( 201, 300 );

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_language_for_element' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_current_language' )->willReturn( $current_language );
		$sitepress->expects( $this->once() )->method( 'get_language_for_element' )->with( $original_booking_product_id, 'post_product' )->willReturn( $original_language );

		$original_booking = $this->getMockBuilder( 'WC_Booking' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_id', 'get_product_id' ) )
		                         ->getMock();

		$translated_booking = $this->getMockBuilder( 'WC_Booking' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_id' ) )
		                         ->getMock();

		$original_booking->expects( $this->once() )->method( 'get_id' )->willReturn( $original_booking_id );
		$original_booking->expects( $this->once() )->method( 'get_product_id' )->willReturn( $original_booking_product_id );
		$translated_booking->expects( $this->once() )->method( 'get_id' )->willReturn( $translated_booking_id );

		$subject = $this->get_subject( $sitepress );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $original_booking_id, '_language_code', true ),
			'times'  => 1,
			'return' => ''
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $translated_booking_id, '_language_code', true ),
			'times'  => 1,
			'return' => $current_language
		) );

		$tables = array();
		$bookings = array( $original_booking, $translated_booking );
		$tables[]['bookings'] = $bookings;

		$filtered_tables = $subject->filter_my_account_bookings_tables_by_current_language( $tables );

		$expected_tables = array(
			array(
				'bookings' => array(
					$translated_booking
				)
			)
		);

		$this->assertEquals($expected_tables, $filtered_tables );

	}
}
