<?php

/**
 * Class TestWCMLGravityForms
 *
 * @group compatibility
 */
class TestWCMLGravityForms extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$sitepress        = \Mockery::mock( 'SitePress' );
		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );

		$subject = new WCML_gravityforms( $sitepress, $woocommerce_wpml );

		\WP_Mock::expectFilterAdded( 'gform_formatted_money', [ $subject, 'wcml_convert_price' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', [ $subject, 'add_ajax_action' ] );

		\WP_Mock::expectActionAdded( 'wcml_after_duplicate_product_post_meta', [ $subject, 'sync_gf_data' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_filters_ajax_action() {
		$sitepress        = \Mockery::mock( 'SitePress' );
		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );

		$subject = new WCML_gravityforms( $sitepress, $woocommerce_wpml );

		$actions  = [ 'some_action' ];
		$expected = array_merge( $actions, [ 'get_updated_price', 'gforms_get_updated_price' ] );

		$this->assertSame( $expected, $subject->add_ajax_action( $actions ) );
	}
}
