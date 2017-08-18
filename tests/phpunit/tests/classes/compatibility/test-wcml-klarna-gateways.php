<?php

class Test_WCML_Klarna_Gateways extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function add_hooks(){

		$subject = new WCML_Klarna_Gateway();

		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', array( $subject, 'ajax_action_needs_multi_currency' ) );

		$subject->add_hooks();

	}
	
	/**
	 * @test
	 */
	public function ajax_action_needs_multi_currency() {

		$subject = new WCML_Klarna_Gateway();

		$expected_actions_list = array(
			'klarna_checkout_cart_callback_update',
			'klarna_checkout_coupons_callback',
			'klarna_checkout_remove_coupon_callback',
			'klarna_checkout_cart_callback_remove',
			'klarna_checkout_shipping_callback',
			'kco_iframe_shipping_option_change_cb',
			'klarna_checkout_order_note_callback',
			'kco_iframe_change_cb',
			'kco_iframe_shipping_address_change_v2_cb',
			'kco_iframe_shipping_address_change_cb'
		);

		$this->assertEquals( $expected_actions_list, $subject->ajax_action_needs_multi_currency( array() ) );

	}
}
