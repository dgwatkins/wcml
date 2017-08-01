<?php

class Test_WCML_url_translation extends OTGS_TestCase {

	private $options;

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction('_x');

	}

	private function get_woocommerce_wcml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function use_untranslated_default_url_bases(){

		$url_translation = new WCML_Url_Translation(
			$this->get_woocommerce_wcml_mock(),
			$this->get_sitepress_mock()
		);

		$url_translation->default_product_base = rand_str();
		$url_translation->default_product_category_base = rand_str();
		$url_translation->default_product_tag_base = rand_str();

		// set all
		$permalinks = [
			'product_base' => '',
			'category_base' => '',
			'tag_base' => ''
		];
		$filtered = $url_translation->use_untranslated_default_url_bases( $permalinks );

		$this->assertEquals( $filtered['product_base'], $url_translation->default_product_base );
		$this->assertEquals( $filtered['category_base'], $url_translation->default_product_category_base );
		$this->assertEquals( $filtered['tag_base'], $url_translation->default_product_tag_base );

		// do not set when already set
		$permalinks = [
			'product_base' => rand_str()
		];
		$filtered = $url_translation->use_untranslated_default_url_bases( $permalinks );
		$this->assertNotEquals( $filtered['product_base'], $url_translation->default_product_base );
		$this->assertEquals( $filtered['product_base'], $permalinks['product_base'] );

	}

}