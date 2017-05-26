<?php

/**
 * @group refactoring
 */
class Test_WCML_Url_Filters_Redirect_Location extends OTGS_TestCase {
	/** @var WPML_URL_Converter */
	private $wpml_url_converter;

	/** @var WCML_Url_Filters_Redirect_Location */
	private $subject;

	public function setUp() {
		parent::setUp();

		$this->wpml_url_converter = $this->getMockBuilder( 'WPML_URL_Converter' )->setMethods( array( 'convert_url' ) )->getMock();
		$this->subject = new WCML_Url_Filters_Redirect_Location( $this->wpml_url_converter );
	}

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$hooks = array( 'woocommerce_get_checkout_payment_url', 'woocommerce_get_cancel_order_url', 'woocommerce_get_return_url' );
		foreach ( $hooks as $hook ) {
			\WP_Mock::expectFilterAdded( $hook, array( $this->subject, 'filter' ), 10, 1 );
		}

		$this->subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_filters_redirect_location() {
		$input = '/url/some/&quot;aaa&quot;/';
		$expected_output = '/url/some/"aaa"/';

		$this->wpml_url_converter->expects( $this->once() )->method( 'convert_url' )->with( $input )->willReturn( $input );

		$this->assertEquals( $expected_output, $this->subject->filter( $input ) );
	}
}
