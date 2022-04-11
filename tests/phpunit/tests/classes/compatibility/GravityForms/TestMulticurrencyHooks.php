<?php

namespace WCML\Compatibility\GravityForms;

/**
 * @group compatibility
 * @group gfml
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'gform_formatted_money', [ $subject, 'wcml_convert_price' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', [ $subject, 'add_ajax_action' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_filters_ajax_action() {
		$subject = $this->getSubject();

		$actions  = [ 'some_action' ];
		$expected = array_merge( $actions, [ 'get_updated_price', 'gforms_get_updated_price' ] );

		$this->assertSame( $expected, $subject->add_ajax_action( $actions ) );
	}

	private function getSubject() {
		return new MulticurrencyHooks();
	}
}
