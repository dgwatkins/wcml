<?php

class Test_WCML_Product_Addons extends OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;

	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->getMock();

	}

	private function get_subject(){
		return new WCML_Product_Addons( $this->sitepress);
	}

	/**
	 * @test
	 */
	public function add_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array( $subject, 'replace_tm_editor_custom_fields_with_own_sections' ) );
		\WP_Mock::expectFilterAdded( 'wcml_cart_contents_not_changed', array( $subject, 'filter_booking_addon_product_in_cart_contents'	), 20 );

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
}
