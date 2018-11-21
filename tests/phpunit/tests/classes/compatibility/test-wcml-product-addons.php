<?php

class Test_WCML_Product_Addons extends OTGS_TestCase {

	const ENABLE_MULTI_CURRENCY = 1;
	/** @var Sitepress */
	private $sitepress;

	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->getMock();

	}

	public function get_subject(){

		return new WCML_Product_Addons( $this->sitepress, self::ENABLE_MULTI_CURRENCY );
	}

	/**
	 * @test
	 */
	public function add_admin_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array( $subject, 'replace_tm_editor_custom_fields_with_own_sections' ) );
		\WP_Mock::expectFilterAdded( 'wcml_cart_contents_not_changed', array( $subject, 'filter_booking_addon_product_in_cart_contents'	), 20 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_front_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'translate_addons_strings' ), 10 ,4 );

		$subject->add_hooks();
	}
	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections(){

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_product_addons' ), $fields_to_hide );

	}

	/**
	 * @test
	 */
	public function it_sets_global_ids_without_adjusting_term_ids_in_query_args(){

		$args = array();
		$args['tax_query'] = array();

		$global_addons = array();
		$global_addon = new stdClass();
		$global_addon->ID = mt_rand( 1, 10 );
		$global_addons[] = $global_addon;

		\WP_Mock::wpFunction( 'is_archive', array(
			'return' => false
		) );

		\WP_Mock::wpFunction( 'get_posts', array(
			'args' => array( $args ),
			'return' => $global_addons
		) );

		\WP_Mock::wpFunction( 'wp_list_pluck', array(
			'return' => array( $global_addon->ID )
		) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => true,
			'times' => 3
		) );

		\WP_Mock::expectFilterAdded( 'get_terms_args', array( $this->sitepress, 'get_terms_args_filter' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1 );
		\WP_Mock::expectFilterAdded( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10, 3 );

		$subject = $this->get_subject();
		$filtered_query_args = $subject->set_global_ids_in_query_args( $args );

		$this->assertEquals( array( 'include' => array( $global_addon->ID ) ), $filtered_query_args );

	}
}
