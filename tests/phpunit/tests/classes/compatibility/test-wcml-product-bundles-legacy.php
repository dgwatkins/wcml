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
			->getMock();

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

		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
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

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function is_bundle_product() {

		$bundle_product_id = mt_rand( 1, 100 );
		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_type' ) )
		                ->getMock();

		$product->method( 'get_type' )->willReturn( 'bundle' );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args' => array( $bundle_product_id ),
			'return' => $product
		) );

		$subject = $this->get_subject();
		$this->assertTrue( $subject->is_bundle_product( $bundle_product_id ) );

	}
}
