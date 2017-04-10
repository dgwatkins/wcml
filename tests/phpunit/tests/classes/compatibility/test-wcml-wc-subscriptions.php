<?php

class Test_WCML_WC_Subscriptions extends OTGS_TestCase {
	/**
	 * @test
	 */
	public function actions_on_init_front_end() {
		\WP_Mock::wpFunction(
			'is_admin',
			array(
				'return' => false,
			)
		);

		$subject = new WCML_WC_Subscriptions();

		$this->expectActionAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1, 1 );
		$this->expectActionAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1, 1 );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function actions_on_init_back_end() {
		\WP_Mock::wpFunction(
			'is_admin',
			array(
				'return' => true,
			)
		);

		$subject = new WCML_WC_Subscriptions();

		$this->expectActionAdded( 'woocommerce_before_calculate_totals', array( $subject, 'maybe_backup_recurring_carts' ), 1, 1, 0 );
		$this->expectActionAdded( 'woocommerce_after_calculate_totals', array( $subject, 'maybe_restore_recurring_carts' ), 200, 1, 0 );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function maybe_backup_recurring_carts() {

		$subject = new WCML_WC_Subscriptions();

		$cart                  = new stdClass();
		$cart->recurring_carts = rand_str();

		$other_cart                  = new stdClass();
		$other_cart->recurring_carts = false;

		$subject->maybe_backup_recurring_carts( $cart );

		$subject->maybe_restore_recurring_carts( $other_cart );

		$this->assertEquals( $cart->recurring_carts, $other_cart->recurring_carts );
	}

	function expectActionAdded( $action_name, callable $callback, $priority, $args = 1, $times = null ) {
		$intercept = \Mockery::mock( 'intercept' );

		if ( null !== $times ) {
			$intercept->shouldReceive( 'intercepted' )->times( $times );
		} else {
			$intercept->shouldReceive( 'intercepted' )->atLeast()->once();
		}
		/** @var WP_Mock\HookedCallbackResponder $responder */
		$responder = \WP_Mock::onHookAdded( $action_name, 'action' )->with( $callback, $priority, $args );
		$responder->perform( array( $intercept, 'intercepted' ) );
	}
}
