<?php

class Test_WCML_Currencies extends OTGS_TestCase {


	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_subject( $woocommerce_wpml = null ) {
		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		return new WCML_Currencies( $woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function hooks_are_added() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', [ 'return' => true ] );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$this->expectActionAdded( 'update_option_woocommerce_currency', array(
			$subject,
			'setup_multi_currency_on_currency_update'
		), 10, 2, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function hooks_are_not_added() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', [ 'return' => false ] );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', [ 'return' => false ] );

		$this->expectActionAdded( 'update_option_woocommerce_currency', array(
			$subject,
			'setup_multi_currency_on_currency_update'
		), 10, 2, 0 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_setup_multi_currency_on_currency_update(){
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$subject = $this->get_subject();

		$old_value = rand_str();
		$new_value = rand_str();

		$multi_currency = \Mockery::mock('overload:WCML_Multi_Currency');
		$multi_currency_install = \Mockery::mock('overload:WCML_Multi_Currency_Install');

		$multi_currency_install->shouldReceive('set_default_currencies_languages')
		                       ->with( $old_value, $new_value );

		$subject->setup_multi_currency_on_currency_update( $old_value, $new_value );
	}

	/**
	 * @test
	 *
	 */
	public function it_should_update_default_currency_on_setup_multi_currency() {
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', [ 'return' => false ] );

		$old_value        = rand_str();
		$new_value        = rand_str();
		$currency_options = [ $old_value => [ 'rate' => 0 ] ];
		$expected_options = [ $new_value => [ 'rate' => 0 ] ];

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( [ 'get_setting', 'update_setting' ] )
		                         ->getMock();
		$woocommerce_wpml->expects( $this->once() )->method( 'get_setting' )->with( 'currency_options' )->willReturn( $currency_options );
		$woocommerce_wpml->expects( $this->once() )->method( 'update_setting' )->with( 'currency_options', $expected_options )->willReturn( true );

		$subject = $this->get_subject( $woocommerce_wpml );
		$subject->setup_multi_currency_on_currency_update( $old_value, $new_value );
	}

}
