<?php

/**
 * @author OnTheGo Systems
 *
 * @group factory
 * @group wcml-1964
 */
class Test_WCML_Factory extends OTGS_TestCase {
	/**
	 * @test
	 */
	function it_creates_and_instance_of_woocommerce_wcml() {

		/** @var WPML_WP_API|PHPUnit_Framework_MockObject_MockObject $wpml_wp_wpi */
		$wpml_wp_wpi = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_wp_api' ) )->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wpml_wp_wpi );

		$subject = new WCML_Factory( $sitepress );

		$this->get_mocked_wp_core_functions()->option();
		WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$this->assertInstanceOf( 'WooCommerce_WPML', $subject->create() );
	}
}