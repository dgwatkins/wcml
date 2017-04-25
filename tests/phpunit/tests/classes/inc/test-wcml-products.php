<?php

class Test_WCML_Products extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	private $default_language = 'en';

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_current_language',
		                        ) )
		                        ->getMock();


		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();
	}

	/**
	 * @return WCML_Products
	 */
	private function get_subject(){
		return new WCML_Products( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function add_lang_to_shortcode_products_query(){

		$this->sitepress->method( 'get_current_language' )
			->wilLReturn( $this->default_language );

		$subject = $this->get_subject();

		$query_args = array();

		$product_query_args = $subject->get_products_from_filter( $query_args );

		$this->assertEquals( $this->default_language, $product_query_args[ 'lang' ] );

	}

}
