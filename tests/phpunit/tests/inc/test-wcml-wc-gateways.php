<?php

class Test_WCML_WC_Gateways extends OTGS_TestCase {

	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress( $wp_api = null ) {
		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_default_language' ) )
		                  ->getMock();

		return $sitepress;
	}

	/**
	 * @return WCML_WC_Gateways
	 */
	private function get_subject( $woocommerce_wpml = false, $sitepress = false ) {

		if ( ! $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if ( ! $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new WCML_WC_Gateways( $woocommerce_wpml, $sitepress );
	}

	/**
	 * @test
	 */
	public function add_on_init_hooks() {
		global $pagenow;
		$current_pagenow = $pagenow;

		$pagenow         = 'admin.php';
		$_GET['page']    = 'wc-settings';
		$_GET['tab']     = 'checkout';
		$_GET['section'] = 'bacs';
		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_gateway_title', array( $subject, 'translate_gateway_title' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_gateway_description', array(
			$subject,
			'translate_gateway_description'
		), 10, 2 );
		\WP_Mock::expectActionAdded( 'admin_footer', array( $subject, 'show_language_links_for_gateways' ) );
		\WP_Mock::expectActionAdded( 'admin_footer', array(
			$subject,
			'append_currency_selector_to_bacs_account_settings'
		) );
		$subject->on_init_hooks();

		$pagenow = $current_pagenow;
		unset( $_GET['page'], $_GET['tab'], $_GET['section'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_admin_footer_hook_for_bacs_page_when_mc_off() {
		global $pagenow;
		$current_pagenow = $pagenow;

		$pagenow         = 'admin.php';
		$_GET['page']    = 'wc-settings';
		$_GET['tab']     = 'checkout';
		$_GET['section'] = 'bacs';
		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectActionNotAdded( 'admin_footer', array(
			$subject,
			'append_currency_selector_to_bacs_account_settings'
		) );
		$subject->on_init_hooks();

		$pagenow = $current_pagenow;
		unset( $_GET['page'], $_GET['tab'], $_GET['section'] );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_dropdown_if_bacs_settings_not_saved_yet() {

		$bacs_settings            = array();
		$default_currency         = 'USD';
		$bacs_accounts_currencies = array();
		$active_currencies        = array( 'USD', 'EUR' );
		$default_dropdown         = '<select><option>USD</option></select>';

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_bacs_accounts', array() ),
			'return' => $bacs_settings
		) );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $default_currency
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'wcml_bacs_accounts_currencies', array() ),
			'return' => $bacs_accounts_currencies
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_currency_codes' ) )
		                                         ->getMock();
		$woocommerce_wpml->multi_currency->method( 'get_currency_codes' )->willReturn( $active_currencies );


		$currencies_dropdown_ui = $this->getMockBuilder( 'WCML_Currencies_Dropdown_UI' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get' ) )
		                               ->getMock();
		$currencies_dropdown_ui->method( 'get' )->with( $active_currencies, $default_currency )->willReturn( $default_dropdown );

		$subject = $this->get_subject( $woocommerce_wpml );

		$this->assertSame( array(
			$default_dropdown,
			array( $default_dropdown )
		), $subject->get_dropdown( $currencies_dropdown_ui ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_dropdown_for_bacs_settings() {

		$bacs_settings                  = array( 0 => array() );
		$default_currency               = 'USD';
		$this->bacs_accounts_currencies = array( 0 => 'EUR' );
		$active_currencies              = array( 'USD', 'EUR' );
		$this->default_dropdown         = '<select><option>USD</option></select>';
		$this->eur_dropdown             = '<select><option>EUR</option></select>';

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_bacs_accounts', array() ),
			'return' => $bacs_settings
		) );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $default_currency
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'wcml_bacs_accounts_currencies', array() ),
			'return' => $this->bacs_accounts_currencies
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_currency_codes' ) )
		                                         ->getMock();
		$woocommerce_wpml->multi_currency->method( 'get_currency_codes' )->willReturn( $active_currencies );


		$currencies_dropdown_ui = $this->getMockBuilder( 'WCML_Currencies_Dropdown_UI' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get' ) )
		                               ->getMock();

		$that = $this;
		$currencies_dropdown_ui->method( 'get' )->willReturnCallback( function ( $active_currencies, $currency ) use ( $that ) {
			if ( $that->bacs_accounts_currencies[0] === $currency ) {
				return $that->eur_dropdown;
			} else {
				return $that->default_dropdown;
			}
		} );

		$subject = $this->get_subject( $woocommerce_wpml );

		$this->assertSame( array(
			$this->default_dropdown,
			array( $this->eur_dropdown )
		), $subject->get_dropdown( $currencies_dropdown_ui ) );
	}

}
