<?php

class Test_WCML_Product_Bundles extends OTGS_TestCase {

	/** @var  woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var  SitePress */
	private $sitepress;
	/** @var  WCML_WC_Product_Bundles_Items */
	private $product_bundles_items;

	function setUp(){
		parent::setUp();

		$this->woocommerce_wpml      = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$this->sitepress             = $this->getMockBuilder( 'Sitepress' )->disableOriginalConstructor()->getMock();
		$this->product_bundles_items = $this->getMockBuilder( 'WCML_WC_Product_Bundles_Items' )->disableOriginalConstructor()->getMock();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => false ) );

	}

	private function get_subject() {
		return new WCML_Product_Bundles( $this->woocommerce_wpml, $this->sitepress, $this->product_bundles_items );
	}

	/**
	 * @test
	 * @group wcml-1934
	 */
	public function test_translate_allowed_variations() {

		$variations_with_translation = [
			0 => [ 'original' => rand(200, 300), 'translation' => rand( 300, 400) ],
			1 => [ 'original' => rand(400, 500), 'translation' => rand( 600, 700) ],
		];

		$lang = 'fr';

		$subject = $this->get_subject();

		$allowed_variations = [
			$variations_with_translation[0]['original'],
			$variations_with_translation[1]['original'],
		];

		$allowed_variations_expected = [
			$variations_with_translation[0]['translation'],
			$variations_with_translation[1]['translation'],
		];

		for( $i = 0; $i < 2; $i++ ){
			\WP_Mock::onFilter( 'translate_object_id' )
			        ->with( $variations_with_translation[$i]['original'], 'product_variation', true, $lang )
			        ->reply( $variations_with_translation[$i]['translation'] );
		}

		$allowed_variations_translated = $subject->translate_allowed_variations( $allowed_variations, $lang );
		$this->assertEquals( $allowed_variations_expected, $allowed_variations_translated );

	}

}
