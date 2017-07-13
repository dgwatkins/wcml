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

	/**
	* @test
	*/
	public function update_bundles_custom_prices_values() {

		$prices = array( );
		$code = rand_str();
		$base_regular_price = rand( 1, 100 );
		$base_sale_price = rand( 1, 100 );

		$_POST[ '_custom_regular_price' ][ $code ] = $base_regular_price;
		$_POST[ '_custom_sale_price' ][ $code ] = $base_sale_price;

		$expected_prices_codes = array(
			'_wc_pb_base_regular_price' => $base_regular_price,
			'_wc_pb_base_sale_price' => $base_sale_price
		);

		\WP_Mock::wpFunction( 'wc_format_decimal', array(
				'args' => array( $base_regular_price ),
				'return' => $base_regular_price
			)
		);

		\WP_Mock::wpFunction( 'wc_format_decimal', array(
				'args' => array( $base_sale_price ),
				'return' => $base_sale_price
			)
		);

		$subject = $this->get_subject();
		$filtered_prices = $subject->update_bundles_custom_prices_values( $prices, $code );

		$this->assertEquals( $expected_prices_codes, $filtered_prices );

	}

	/**
	* @test
	*/
	public function update_bundles_base_price() {


		$post_id = rand( 1, 100 );
		$product_price = rand( 1, 100 );
		$custom_prices = array( '_wc_pb_base_regular_price' => rand( 1, 100 ) );
		$code = rand_str();

		\WP_Mock::wpFunction( 'update_post_meta', array(
				'args' => array( $post_id, '_wc_pb_base_price_' . $code, $product_price ),
				'times' => 1
			)
		);

		$subject = $this->get_subject();
		$filtered_prices = $subject->update_bundles_base_price( $post_id, $product_price, $custom_prices, $code );

	}

	/**
	 * @test
	 */
	public function is_bundle_product() {

		$bundle_product_id = mt_rand( 1, 100 );
		$mock             = \Mockery::mock( 'alias:WooCommerce_Functions_Wrapper' );
		$mock->shouldReceive( 'get_product_type' )->with( $bundle_product_id )->andReturn( rand_str() );

		$subject = $this->get_subject();
		$this->assertFalse( $subject->is_bundle_product( $bundle_product_id ) );


		$bundle_product_id = mt_rand( 1, 100 );
		$mock->shouldReceive( 'get_product_type' )->with( $bundle_product_id )->andReturn( 'bundle' );

		$subject = $this->get_subject();
		$this->assertTrue( $subject->is_bundle_product( $bundle_product_id ) );

	}
}
