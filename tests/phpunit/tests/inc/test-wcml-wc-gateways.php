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

}
