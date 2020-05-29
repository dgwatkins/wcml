<?php

/**
 * Class Test_WCML_Multi_Currency_Configuration
 *
 */
class Test_WCML_Multi_Currency_Configuration extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_add_hooks() {
		WP_Mock::userFunction( 'is_ajax', [ 'return' => true ] );

		WP_Mock::expectActionAdded( 'wp_ajax_legacy_update_custom_rates', [
			WCML_Multi_Currency_Configuration::class,
			'legacy_update_custom_rates'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_legacy_remove_custom_rates', [
			WCML_Multi_Currency_Configuration::class,
			'legacy_remove_custom_rates'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_wcml_save_currency', [
			WCML_Multi_Currency_Configuration::class,
			'save_currency'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_wcml_delete_currency', [
			WCML_Multi_Currency_Configuration::class,
			'delete_currency'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_wcml_update_currency_lang', [
			WCML_Multi_Currency_Configuration::class,
			'update_currency_lang'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_wcml_update_default_currency', [
			WCML_Multi_Currency_Configuration::class,
			'update_default_currency_ajax'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_wcml_set_currency_mode', [
			WCML_Multi_Currency_Configuration::class,
			'set_currency_mode'
		] );
		WP_Mock::expectActionAdded( 'wp_ajax_wcml_set_max_mind_key', [
			WCML_Multi_Currency_Configuration::class,
			'set_max_mind_key'
		] );

		WCML_Multi_Currency_Configuration::add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_set_currency_mode() {
		global $sitepress;
		\WP_Mock::userFunction( 'wp_verify_nonce', [ 'return' => true ] );

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->setMethods( [ 'get_settings' ] )
		                  ->disableOriginalConstructor()->getMock();
		$sitepress->expects( $this->once() )->method( 'get_settings' )->willReturn( [] );

		$_POST['data'] = json_encode( [ 'mode' => 'by_language' ] );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'update_settings' ] )
		                         ->getMock();

		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' )->willReturn( true );

		\WP_Mock::wpFunction( 'wp_send_json_success', [
			'return' => true,
			'times'  => 1,
		] );

		$multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                       ->disableOriginalConstructor()
		                       ->getMock();

		WCML_Multi_Currency_Configuration::set_up( $multi_currency, $woocommerce_wpml );
		WCML_Multi_Currency_Configuration::set_currency_mode();

		unset( $sitepress, $_POST );
	}

	/**
	 * @test
	 */
	public function it_should_set_max_mind_key() {
		\WP_Mock::userFunction( 'wp_verify_nonce', [ 'return' => true ] );

		$key           = rand_str();
		$_POST['data'] = json_encode( [ 'MaxMindKey' => $key ] );

		\WP_Mock::wpFunction( 'wp_send_json_success', [
			'return' => true,
			'times'  => 1,
		] );


		$WC = $this->getMockBuilder( 'WC' )
		           ->disableOriginalConstructor()
		           ->getMock();

		\WP_Mock::wpFunction( 'WC', [
			'return' => $WC,
			'times'  => 2,
		] );

		$integrations                        = [];
		$integrations['maxmind_geolocation'] = $this->getMockBuilder( 'WC_Integration_MaxMind_Geolocation' )
		                                            ->disableOriginalConstructor()
		                                            ->setMethods( [ 'validate_license_key_field' ] )
		                                            ->getMock();

		$integrations['maxmind_geolocation']->expects( $this->once() )->method( 'validate_license_key_field' )->with( 'license_key', $key )->willReturn( true );

		$WC->integrations = $this->getMockBuilder( 'WC_Integrations' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_integrations' ] )
		                         ->getMock();

		$WC->integrations->expects( $this->once() )->method( 'get_integrations' )->willReturn( $integrations );

		WCML_Multi_Currency_Configuration::set_max_mind_key();
		unset( $_POST );
	}

}
