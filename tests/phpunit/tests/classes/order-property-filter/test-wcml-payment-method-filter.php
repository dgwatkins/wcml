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
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'payment_method_string' ), 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_when_meta_key_does_not_match() {
		$title = 'original title';
		$object_id = 12;
		$meta_key = 'my-awesome-meta-key';

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 0 ) );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 0 ) );

		$this->assertEquals( $title, $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_when_a_title_is_empty() {
		$title = '';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 0 ) );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 0 ) );

		$subject = new WCML_Payment_Method_Filter();

		$this->assertEquals( '', $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_when_a_title_is_null() {
		$title = null;
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 0 ) );

		$this->assertNull( $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_when_post_type_does_not_match() {
		$title = 'original title';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 1, 'return' => 'post' ) );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 0 ) );

		$this->assertEquals( $title, $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_blocks_hook_to_avoid_reccurent_calls() {
		$title = 'original title';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'get_post_type', array( 'return' => 'shop_order' ) );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'return' => null ) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times' => 1,
			'args'  => array(
				'get_post_metadata',
				array( $subject, 'payment_method_string' ),
				10,
				3,
			),
		) );
		\WP_Mock::expectFilterAdded('get_post_metadata', array( $subject, 'payment_method_string' ), 10, 3);

		$subject->payment_method_string( $title, $object_id, $meta_key );
	}

	/**
	 * @test
	 */
	public function it_returns_original_value_if_payment_gateway_is_not_defined_for_oders() {
		$title = 'original title';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$subject = new WCML_Payment_Method_Filter();

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 1, 'return' => 'shop_order' ) );
		\WP_Mock::wpFunction( 'remove_filter', array() );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 1, 'return' => null ) );

		\WP_Mock::wpFunction( 'icl_translate', array( 'times' => 0 ) );

		$this->assertEquals( $title, $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_returns_translated_value() {
		$title = 'original title';
		$translated_title = 'translated title';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$subject = new WCML_Payment_Method_Filter();

		$payment_gateway = new \stdClass();
		$payment_gateway->id = 10;
		$payment_gateway->title = 'title value';

		\WP_Mock::wpFunction( 'get_post_type', array( 'return' => 'shop_order' ) );
		\WP_Mock::wpFunction( 'remove_filter', array() );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'return' => $payment_gateway ) );

		\WP_Mock::wpFunction( 'icl_translate', array(
			'times' => 1,
			'return' => $translated_title,
			'args' => array(
				'admin_texts_woocommerce_gateways',
				$payment_gateway->id . '_gateway_title',
				$payment_gateway->title,
			),
		) );

		$this->assertEquals( $translated_title, $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_caches_payment_gateway_and_post_type() {
		$title = 'original title';
		$translated_title = 'translated title';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$repeat = 5;

		$subject = new WCML_Payment_Method_Filter();

		$payment_gateway = new \stdClass();
		$payment_gateway->id = 10;
		$payment_gateway->title = 'title value';

		\WP_Mock::wpFunction( 'get_post_type', array( 'times' => 1, 'return' => 'shop_order' ) );
		\WP_Mock::wpFunction( 'remove_filter', array() );
		\WP_Mock::wpFunction( 'wc_get_payment_gateway_by_order', array( 'times' => 1, 'return' => $payment_gateway ) );

		\WP_Mock::wpFunction( 'icl_translate', array(
			'times'  => $repeat,
			'return' => $translated_title,
		) );

		for ( $i = 0; $i < $repeat; $i ++ ) {
			$subject->payment_method_string( $title, $object_id, $meta_key );
		}
	}

	/**
	 * @test
	 */
	public function get_payment_method_from_Post() {
		$title = 'original title';
		$translated_title = 'translated title';
		$object_id = 12;
		$meta_key = '_payment_method_title';

		$subject = new WCML_Payment_Method_Filter();

		$payment_gateway = new \stdClass();
		$payment_gateway->id = 'gateway';
		$payment_gateway->title = 'title value';

		\WP_Mock::wpFunction( 'get_post_type', array( 'return' => 'shop_order' ) );
		\WP_Mock::wpFunction( 'remove_filter', array() );
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

		\WP_Mock::wpFunction( 'icl_translate', array(
			'args'  => array( 'admin_texts_woocommerce_gateways', $post_payment_gateway->id . '_gateway_title', $post_payment_gateway->title ),
			'return' => $translated_title,
		) );

		$this->assertEquals( $translated_title, $subject->payment_method_string( $title, $object_id, $meta_key ) );
	}
}
