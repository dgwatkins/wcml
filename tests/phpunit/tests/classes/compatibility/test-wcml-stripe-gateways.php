<?php

namespace WCML\Compatibility\StripePayments;

class Test_WCML_Stripe_Gateways extends \OTGS_TestCase {

	private function get_orders() {
		return $this->getMockBuilder( 'WCML_Multi_Currency_Orders' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function get_subject() {
		return new MulticurrencyHooks( $this->get_orders() );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_admin_order_totals_after_total', [ $subject, 'suspendCurrencySymbolFilter'], 9 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function deregister_and_register_currency_symbol() {
		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'remove_filter', [
			'return' => true,
			'times' => 1
		] );

		\WP_Mock::userFunction( 'add_filter' );

		$subject->suspendCurrencySymbolFilter();
	}

}