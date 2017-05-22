<?php
/**
 * @author OnTheGo Systems
 * @group factory
 * @group  wcml-1964
 */
class Test_WCML_Factory extends OTGS_TestCase {
	/**
	 * @test
	 */
	function it_creates_and_instance_of_woocommerce_wcml() {
		$subject = new WCML_Factory();

		$this->get_mocked_wp_core_functions()->option();
		WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$this->assertInstanceOf('WooCommerce_WPML', $subject->create());
	}
}