<?php

/**
 * @group wcml-2009
 */
class Test_WCML_Payment_Method_Filter extends OTGS_TestCase {
	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$subject = new WCML_Payment_Method_Filter();
		\WP_Mock::expectFilterAdded( 'woocommerce_order_get_payment_method_title', array( $subject, 'payment_method_string' ), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_when_a_title_is_empty() {
		$title = '';
		$object = $this->getMockBuilder( 'WC_Order' )->setMethods( [ 'get_id' ] )->disableOriginalConstructor()->getMock();
		$object->expects( $this->never() )->method( 'get_id' );

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 0 ) );

		$subject = new WCML_Payment_Method_Filter();

		$this->assertEquals( '', $subject->payment_method_string( $title, $object ) );
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_when_a_title_is_null() {
		$title = null;
		$object = $this->getMockBuilder( 'WC_Order' )->setMethods( [ 'get_id' ] )->disableOriginalConstructor()->getMock();
		$object->expects( $this->never() )->method( 'get_id' );

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 0 ) );

		$this->assertNull( $subject->payment_method_string( $title, $object ) );
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_if_payment_gateway_is_not_defined_for_oders() {
		$title = 'original title';
		$object = $this->getMockBuilder( 'WC_Order' )->setMethods( [ 'get_id' ] )->disableOriginalConstructor()->getMock();
		$object->expects( $this->exactly( 2 ) )->method( 'get_id' )->willReturn( 12 );

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 1, 'return' => null ) );

		\WP_Mock::wpFunction( 'icl_translate', array( 'times' => 0 ) );

		$this->assertEquals( $title, $subject->payment_method_string( $title, $object ) );
	}

	/**
	 * @test
	 */
	public function it_returns_translated_value() {
		\WP_Mock::passthruFunction( 'maybe_unserialize' );

		$title = 'original title';
		$translated_title = 'translated title';
		$object = $this->getMockBuilder( 'WC_Order' )->setMethods( [ 'get_id' ] )->disableOriginalConstructor()->getMock();
		$object->expects( $this->exactly( 2 ) )->method( 'get_id' )->willReturn( 12 );

		$subject = new WCML_Payment_Method_Filter();

		$payment_gateway = new \stdClass();
		$payment_gateway->id = 10;
		$payment_gateway->title = 'title value';

		$settings['title'] = $payment_gateway->title;

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'return' => $payment_gateway ) );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'woocommerce_' . $payment_gateway->id . '_settings' ],
			'return' => $settings
		] );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $payment_gateway->title, 'admin_texts_woocommerce_gateways', $payment_gateway->id . '_gateway_title' )
		       ->reply( $translated_title );

		$this->assertEquals( $translated_title, $subject->payment_method_string( $title, $object ) );
	}

	/**
	 * @test
	 */
	public function it_caches_payment_gateway_and_post_type() {
		$title = 'original title';
		$translated_title = 'translated title';
		$repeat = 5;

		$object = $this->getMockBuilder( 'WC_Order' )->setMethods( [ 'get_id' ] )->disableOriginalConstructor()->getMock();
		$object->expects( $this->exactly( $repeat * 2 ) )->method( 'get_id' )->willReturn( 12 );

		$subject = new WCML_Payment_Method_Filter();

		$payment_gateway = new \stdClass();
		$payment_gateway->id = 10;
		$payment_gateway->title = 'title value';

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 1, 'return' => $payment_gateway ) );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $payment_gateway->title, 'admin_texts_woocommerce_gateways', $payment_gateway->id . '_gateway_title' )
		        ->reply( $translated_title );

		for ( $i = 0; $i < $repeat; $i ++ ) {
			$subject->payment_method_string( $title, $object );
		}
	}

	/**
	 * @test
	 */
	public function get_payment_method_from_Post() {
		$title = 'original title';
		$translated_title = 'translated title';
		$object = $this->getMockBuilder( 'WC_Order' )->setMethods( [ 'get_id' ] )->disableOriginalConstructor()->getMock();
		$object->expects( $this->exactly( 2 ) )->method( 'get_id' )->willReturn( 12 );

		$subject = new WCML_Payment_Method_Filter();

		$payment_gateway = new \stdClass();
		$payment_gateway->id = 'gateway';
		$payment_gateway->title = 'title value';

		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'return' => $payment_gateway ) );

		$payment_gateways = array();
		$post_payment_gateway = new \stdClass();
		$post_payment_gateway->id = 'gateway_test';
		$post_payment_gateway->title = 'gateway test title value';

		$_POST['payment_method'] = $post_payment_gateway->id;

		$payment_gateways[ $post_payment_gateway->id ] = $post_payment_gateway;
		$wc = $this->getMockBuilder( 'WooCommerce' )->setMethods( array( 'payment_gateways' ) )->disableOriginalConstructor()->getMock();
		$wc->expects( $this->once() )
		        ->method( 'payment_gateways' )
		        ->willReturn( $payment_gateways );

		$wc->payment_gateways = $this->getMockBuilder( 'WC_Payment_Gateways' )->setMethods( array( 'payment_gateways' ) )->disableOriginalConstructor()->getMock();
		$wc->payment_gateways->expects( $this->once() )
		   ->method( 'payment_gateways' )
		   ->willReturn( $payment_gateways );

		WP_Mock::userFunction( 'WC', array( 'return' => $wc ) );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $post_payment_gateway->title, 'admin_texts_woocommerce_gateways', $post_payment_gateway->id . '_gateway_title' )
		        ->reply( $translated_title );

		$this->assertEquals( $translated_title, $subject->payment_method_string( $title, $object ) );
	}
}
