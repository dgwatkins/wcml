<?php

namespace WCML\Rest\Wrapper;

/**
 * @group rest
 * @group factory
 */
class TestFactory extends \OTGS_TestCase {

	public function setUp() {
		global $woocommerce_wpml, $wpml_post_translations, $wpml_term_translations, $sitepress, $wpml_query_filter, $wpdb;

		parent::setUp();

		$woocommerce_wpml                         = \Mockery::mock( '\woocommerce_wpml' );
		$woocommerce_wpml->attributes             = \Mockery::mock( '\WCML_Attributes' );
		$woocommerce_wpml->media                  = \Mockery::mock( '\WCML\Media\Wrapper\IMedia' );
		$woocommerce_wpml->multi_currency         = \Mockery::mock( '\WCML_Multi_Currency' );
		$woocommerce_wpml->multi_currency->orders = \Mockery::mock( '\WCML_Multi_Currency_Orders' );
		$woocommerce_wpml->products               = \Mockery::mock( '\WCML_Products' );
		$woocommerce_wpml->sync_product_data      = \Mockery::mock( '\WCML_Synchronize_Product_Data' );
		$woocommerce_wpml->terms                  = \Mockery::mock( '\WCML_Terms' );

		$wpml_post_translations = \Mockery::mock( '\WPML_Post_Translation' );
		$wpml_term_translations = \Mockery::mock( '\WPML_Term_Translation' );
		$sitepress              = \Mockery::mock( \WPML\Core\ISitePress::class );
		$sitepress->shouldReceive( 'get_settings' )->andReturn( [] );
		$wpml_query_filter      = \Mockery::mock( '\WPML_Query_Filter' );
		$wpdb                   = \Mockery::mock( '\wpdb' );
	}

	public function tearDown() {
		global $woocommerce_wpml, $wpml_post_translations, $wpml_term_translations, $sitepress, $wpml_query_filter, $wpdb;
		unset( $woocommerce_wpml, $wpml_post_translations, $wpml_term_translations, $sitepress, $wpml_query_filter, $wpdb );

		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpShouldCreateProduct
	 *
	 * @param bool $isMultiCurrencyOn
	 * @param array $wcmlSettings
	 */
	public function itShouldCreateProduct( $isMultiCurrencyOn, $wcmlSettings ) {
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( $isMultiCurrencyOn );
		$this->mockWcmlSettings( $wcmlSettings );

		Factory::create( 'product' );
	}

	public function dpShouldCreateProduct() {
		return [
			'multicurrency off'                         => [ false, [] ],
			'multicurrency on + missing currency order' => [ true, [] ],
			'multicurrency on + null currency order'    => [ true, [ 'currencies_order' => null ] ],
			'multicurrency on + currency order'         => [ true, [ 'currencies_order' => [ 'USD', 'EUR' ] ] ],
		];
	}

	private function mockWcmlSettings( $wcmlSettings = [] ) {
		global $woocommerce_wpml;

		$woocommerce_wpml->settings = $wcmlSettings;
	}
}
