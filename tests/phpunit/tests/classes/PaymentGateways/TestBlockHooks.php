<?php

namespace WCML\PaymentGateways;

use WPML\LIB\WP\OnActionMock;

/**
 * @group payment-gateways
 */
class TestBlockHooks extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itTranslatesSettings() {
		$settings = [
			'title'       => 'original title',
			'description' => 'original description',
		];

		$expected = [
			'title'       => 'translated title',
			'description' => 'translated description',
		];

		$registry = $this->getMockBuilder( 'PaymentMethodRegistry' )
			->setMethods( [ 'get_all_registered' ] )
			->getMock();

		$registry->method( 'get_all_registered' )
			->willReturn( [ 'cod' => [] ] );

		$woocommerce_wpml = $this->getMockBuilder( '\woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();

		$gateways = $this->getMockBuilder( '\WCML_WC_Gateways' )
			->setMethods( [ 'get_translated_gateway_string' ] )
			->disableOriginalConstructor()
			->getMock();

		$gateways->method( 'get_translated_gateway_string' )
			->willReturnCallback( function( $string, $gatewayId, $field ) use ( $expected ) {
				return $expected[ $field ];
			} );

		$woocommerce_wpml->gateways = $gateways;

		$subject = new BlockHooks( $woocommerce_wpml );
		$subject->add_hooks();

		$this->runAction( 'woocommerce_blocks_payment_method_type_registration', $registry );
		$this->assertSame( $expected, $this->runFilter( 'option_woocommerce_cod_settings', $settings ) );
	}

}
