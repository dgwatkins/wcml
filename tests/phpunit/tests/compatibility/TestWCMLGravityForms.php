<?php

/**
 * Class TestWCMLGravityForms
 *
 * @group compatibility
 */
class TestWCMLGravityForms extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();
		\WP_Mock::passthruFunction( 'maybe_unserialize' );
	}

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'gform_formatted_money', [ $subject, 'wcml_convert_price' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', [ $subject, 'add_ajax_action' ] );

		\WP_Mock::expectActionAdded( 'wcml_after_duplicate_product_post_meta', [ $subject, 'sync_gf_data' ], 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_filters_ajax_action() {
		$subject = $this->getSubject();

		$actions  = [ 'some_action' ];
		$expected = array_merge( $actions, [ 'get_updated_price', 'gforms_get_updated_price' ] );

		$this->assertSame( $expected, $subject->add_ajax_action( $actions ) );
	}

	/**
	 * @test
	 * @group wcml-3248
	 */
	public function it_does_NOT_sync_gf_data_if_not_using_TM_editor() {
		$original_product_id = 123;
		$trnsl_product_id    = 456;

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'is_wpml_prior_4_2' )->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
		        ->with( '_wcml_settings' )
		        ->andReturn( [ 'trnsl_interface' => false ] );

		\WP_Mock::userFunction( 'update_post_meta' )->times( 0 );

		$subject = $this->getSubject( null, $woocommerce_wpml );

		$subject->sync_gf_data( $original_product_id, $trnsl_product_id );
	}

	/**
	 * @test
	 * @group wcml-3248
	 */
	public function it_does_NOT_sync_gf_data_if_original_post_meta_is_empty() {
		$original_product_id = 123;
		$trnsl_product_id    = 456;

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'is_wpml_prior_4_2' )->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
			->with( '_wcml_settings' )
			->andReturn( [ 'trnsl_interface' => true ] );

		\WP_Mock::userFunction( 'get_post_meta' )
			->with( $original_product_id, '_gravity_form_data', true )
			->andReturn( '' );

		\WP_Mock::userFunction( 'update_post_meta' )->times( 0 );

		$subject = $this->getSubject( null, $woocommerce_wpml );

		$subject->sync_gf_data( $original_product_id, $trnsl_product_id );
	}

	/**
	 * @test
	 * @group wcml-3248
	 */
	public function it_syncs_gf_data_if_translated_post_meta_is_empty() {
		$original_product_id = 123;
		$trnsl_product_id    = 456;
		$gf_data             = $this->getGfData( $original_product_id );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'is_wpml_prior_4_2' )->andReturn( [ 'trnsl_interface' => true ] );

		\WP_Mock::userFunction( 'get_option' )
			->with( '_wcml_settings' )
			->andReturn( [ 'trnsl_interface' => true ] );

		\WP_Mock::userFunction( 'get_post_meta' )
			->with( $original_product_id, '_gravity_form_data', true )
			->andReturn( $gf_data );

		\WP_Mock::userFunction( 'get_post_meta' )
		        ->with( $trnsl_product_id, '_gravity_form_data', true )
		        ->andReturn( '' );

		\WP_Mock::userFunction( 'update_post_meta' )
			->times( 1 )
			->with( $trnsl_product_id, '_gravity_form_data', $gf_data );

		$subject = $this->getSubject( null, $woocommerce_wpml );

		$subject->sync_gf_data( $original_product_id, $trnsl_product_id );
	}

	/**
	 * @test
	 * @group wcml-3248
	 */
	public function it_syncs_gf_data_if_translated_post_meta_is_NOT_empty() {
		$original_product_id       = 123;
		$trnsl_product_id          = 456;
		$original_gf_data          = $this->getGfData( $original_product_id );
		$translated_gf_data        = $this->getGfData( $trnsl_product_id );
		$translated_gf_data['foo'] = 'bar'; // Should not be overwritten.

		$expected_translated_gf_data = array_merge( $translated_gf_data, $original_gf_data );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'is_wpml_prior_4_2' )->andReturn( [ 'trnsl_interface' => true ] );

		\WP_Mock::userFunction( 'get_option' )
			->with( '_wcml_settings' )
			->andReturn( [ 'trnsl_interface' => true ] );

		\WP_Mock::userFunction( 'get_post_meta' )
			->with( $original_product_id, '_gravity_form_data', true )
			->andReturn( $original_gf_data );

		\WP_Mock::userFunction( 'get_post_meta' )
		        ->with( $trnsl_product_id, '_gravity_form_data', true )
		        ->andReturn( $translated_gf_data );

		\WP_Mock::userFunction( 'update_post_meta' )
			->times( 1 )
			->with( $trnsl_product_id, '_gravity_form_data', $expected_translated_gf_data );

		$subject = $this->getSubject( null, $woocommerce_wpml );

		$subject->sync_gf_data( $original_product_id, $trnsl_product_id );
	}

	private function getSubject( $sitepress = null, $woocommerce_wpml = null ) {
		$sitepress        = $sitepress ?: \Mockery::mock( 'SitePress' );
		$woocommerce_wpml = $woocommerce_wpml ?: \Mockery::mock( 'woocommerce_wpml' );

		return new WCML_gravityforms( $sitepress, $woocommerce_wpml );
	}

	private function getGfData( $id ) {
		return [
			'id'                        => 'some id for ' . $id,
			'display_title'             => 'some title for ' . $id,
			'display_description'       => 'some desc for ' . $id,
			'disable_woocommerce_price' => 'some price for ' . $id,
			'disable_calculations'      => 'some calculations for ' . $id,
			'disable_label_subtotal'    => 'some label subtotal for ' . $id,
			'disable_label_options'     => 'some label options for ' . $id,
			'disable_label_total'       => 'some label total for ' . $id,
			'disable_anchor'            => 'some anchor for ' . $id,
		];
	}
}
