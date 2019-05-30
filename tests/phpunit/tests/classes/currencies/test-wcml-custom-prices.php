<?php

class Test_WCML_Custom_Prices extends OTGS_TestCase {

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_subject( $woocommerce_wpml = false ) {

		if( !$woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		return new WCML_Custom_Prices( $woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'init', array( $subject, 'custom_prices_init' ) );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function it_should_set_regular_price_as_price_and_update_custom_prices() {

		$post_id = 101;

		$regular_price = 10;
		$sale_price = '';
		$schedule = '';
		$date_from = time();
		$date_to = time();

		$custom_prices = array(
			'_regular_price'         => $regular_price,
			'_sale_price'            => $sale_price,
			'_wcml_schedule'         => $schedule,
			'_sale_price_dates_from' => $date_from,
			'_sale_price_dates_to'   => $date_to
		);
		$code = 'USD';

		\WP_Mock::userFunction( 'current_time', [ 'return' => time() ] );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_regular_price_'.$code, $regular_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_'.$code, $sale_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_wcml_schedule_'.$code, $schedule ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_from_'.$code, $date_from ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_to_'.$code, $date_to ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_price_'.$code, $regular_price ),
			'times' => 1
		) );

		$subject = $this->get_subject();

		$this->assertEquals( $regular_price, $subject->update_custom_prices( $post_id, $custom_prices, $code ) );
	}

	/**
	 * @test
	 */
	public function it_should_set_sale_price_as_price_and_update_custom_prices() {

		$post_id = 101;

		$regular_price = 10;
		$sale_price = 8;
		$schedule = '';
		$date_from = '';
		$date_to = '';

		$custom_prices = array(
			'_regular_price'         => $regular_price,
			'_sale_price'            => $sale_price,
			'_wcml_schedule'         => $schedule,
			'_sale_price_dates_from' => $date_from,
			'_sale_price_dates_to'   => $date_to
		);
		$code = 'USD';

		\WP_Mock::userFunction( 'current_time', [ 'return' => time() ] );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_regular_price_'.$code, $regular_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_'.$code, $sale_price ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_wcml_schedule_'.$code, $schedule ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_from_'.$code, $date_from ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_sale_price_dates_to_'.$code, $date_to ),
			'times' => 1
		) );
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, '_price_'.$code, $sale_price ),
			'times' => 1
		) );

		$subject = $this->get_subject();

		$this->assertEquals( $sale_price, $subject->update_custom_prices( $post_id, $custom_prices, $code ) );
	}

}
