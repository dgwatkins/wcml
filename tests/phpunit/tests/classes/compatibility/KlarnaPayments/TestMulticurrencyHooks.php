<?php

namespace WCML\Compatibility\KlarnaPayments;

class TestMulticurrencyHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itAddsHooks(){
		$subject = new MulticurrencyHooks();

		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', [ $subject, 'ajax_action_needs_multi_currency' ] );

		$subject->add_hooks();
	}
	
	/**
	 * @test
	 */
	public function itAddsAjaxActions() {
		$subject = new MulticurrencyHooks();

		$expected_actions_list = [
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
		];

		$this->assertEquals( $expected_actions_list, $subject->ajax_action_needs_multi_currency( [] ) );
	}

}
