<?php

class Test_WCML_url_translation extends OTGS_TestCase {

	private $options;

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction('_x');

	}

	/**
	 * @test
	 */
	public function use_untranslated_default_url_bases(){

		/** @var WooCommerce_WPML|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'WooCommerce_WPML' )->disableOriginalConstructor()->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->getMock();
		/** @var wpdb|PHPUnit_Framework_MockObject_MockObject $wpdb */
		$wpdb = $this->getMockBuilder( 'wpdb' )->disableOriginalConstructor()->getMock();

		$url_translation = new WCML_Url_Translation( $wcml, $sitepress, $wpdb );

		$url_translation->default_product_base = rand_str();
		$url_translation->default_product_category_base = rand_str();
		$url_translation->default_product_tag_base = rand_str();

		// set all
		$permalinks = array(
			'product_base' => '',
			'category_base' => '',
			'tag_base' => '',
		);
		$filtered = $url_translation->use_untranslated_default_url_bases( $permalinks );

		$this->assertEquals( $filtered['product_base'], $url_translation->default_product_base );
		$this->assertEquals( $filtered['category_base'], $url_translation->default_product_category_base );
		$this->assertEquals( $filtered['tag_base'], $url_translation->default_product_tag_base );

		// do not set when already set
		$permalinks = array(
			'product_base' => rand_str(),
		);
		$filtered = $url_translation->use_untranslated_default_url_bases( $permalinks );
		$this->assertNotEquals( $filtered['product_base'], $url_translation->default_product_base );
		$this->assertEquals( $filtered['product_base'], $permalinks['product_base'] );

	}

}