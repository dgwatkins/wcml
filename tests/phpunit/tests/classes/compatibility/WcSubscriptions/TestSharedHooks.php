<?php

namespace WCML\Compatibility\WcSubscriptions;
use Mockery;
use WC_Cart;
use WP_Mock;

/**
 * @group compatibility
 * @group wc-subscriptions
 */
class TestSharedHooks extends \OTGS_TestCase {

	private function get_subject() {
		return new SharedHooks();
	}

	/**
	 * @test
	 */
	public function actions_on_init_frontend() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$subject = $this->get_subject();

		WP_Mock::expectActionAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1 );
		WP_Mock::expectActionAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1 );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function actions_on_init_backend() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$subject = $this->get_subject();

		WP_Mock::expectActionNotAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1 );
		WP_Mock::expectActionNotAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1 );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function maybe_backup_recurring_carts() {
		$subject = $this->get_subject();

		$cart                  = Mockery::mock( WC_Cart::class );
		$cart->recurring_carts = rand_str();

		$other_cart                  = Mockery::mock( WC_Cart::class );
		$other_cart->recurring_carts = false;

		$subject->maybe_backup_recurring_carts( $cart );
		$subject->maybe_restore_recurring_carts( $other_cart );

		$this->assertEquals( $cart->recurring_carts, $other_cart->recurring_carts );
	}
}
