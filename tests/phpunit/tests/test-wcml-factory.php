<?php
/**
 * @author OnTheGo Systems
 * @group factory
 */
class Test_WCML_Factory extends OTGS_TestCase {
	/**
	 * @test
	 */
	function it_creates_and_instance_of_woocommerce_wcml() {
		$subject = new WCML_Factory();

		$this->assertInstanceOf('WooCommerce_WPML', $subject->create());
	}
}