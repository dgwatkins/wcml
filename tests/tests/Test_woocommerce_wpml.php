<?php

class Test_woocommerce_wpml extends WCML_UnitTestCase {

	/**
	 * @test
	 * @group wcml-3178
	 */
	public function it_should_get_multi_currency() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		$this->assertInstanceOf( 'WCML_Multi_Currency', $woocommerce_wpml->get_multi_currency() );
		$this->assertSame( $woocommerce_wpml->get_multi_currency(), $woocommerce_wpml->get_multi_currency() );
	}
}