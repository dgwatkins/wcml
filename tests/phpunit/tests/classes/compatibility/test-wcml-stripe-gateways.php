<?php

use WCML\Compatibility\Stripe_Gateway;

class Test_WCML_Stripe_Gateways extends OTGS_TestCase {

	private function get_subject() {
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();

		$woocommerce_wpml->multi_currency = (object) [
			'orders' => 'foo'
		];

		return new Stripe_Gateway( $woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_admin_order_totals_after_total', [ $subject, 'deregister_currency_symbol_filter'], 1 );
		\WP_Mock::expectActionAdded( 'woocommerce_admin_order_totals_after_total', [ $subject, 'register_currency_symbol_filter'], 100 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function deregister_and_register_currency_symbol() {
		$subject = $this->get_subject();

		WP_Mock::userFunction( 'remove_filter', [
			'return' => true,
			'times' => 1
		] );

		WP_Mock::userFunction( 'add_filter' );

		$subject->deregister_currency_symbol_filter();

		$subject->register_currency_symbol_filter();

	}

}