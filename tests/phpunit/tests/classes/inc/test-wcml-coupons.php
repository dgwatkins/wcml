<?php

class Test_WCML_Coupons extends OTGS_TestCase {

	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'get_current_language' ) )
		            ->getMock();
	}

	/**
	 * @return WCML_Coupons
	 */
	private function get_subject( $woocommerce_wpml = null, $sitepress = null ) {

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress_mock();
		}


		return new WCML_Coupons( $woocommerce_wpml, $sitepress );
	}

	/**
	 * @test
	 */
	public function add_hooks() {

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_coupon_loaded', array( $subject, 'wcml_coupon_loaded' ) );
		\WP_Mock::expectActionAdded( 'admin_init', array( $subject, 'icl_adjust_terms_filtering' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_coupon_is_valid_for_product', array(
			$subject,
			'is_valid_for_product'
		), 10, 4 );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function is_valid_for_product() {

		$current_language = rand_str();
		$product_id       = random_int( 1, 100 );
		$translated_id    = random_int( 101, 200 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		$wc          = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.2';
		WP_Mock::userFunction( 'WC', [ 'times' => 1, 'return' => $wc ] );

		$product_mock = $this->getMockBuilder( 'WC_Product' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type', 'get_id' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( false );
		$product_mock->method( 'get_id' )->willReturn( $product_id );

		$coupon_mock = $this->getMockBuilder( 'WC_Coupon' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'is_valid_for_product' ) )
		                    ->getMock();

		$coupon_mock->method( 'is_valid_for_product' )->willReturn( true );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => true
		) );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args'   => $translated_id,
			'return' => true
		) );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( $translated_id );

		$coupon_is_valid = $subject->is_valid_for_product( false, $product_mock, $coupon_mock, array() );

		$this->assertTrue( $coupon_is_valid );

	}

	/**
	 * @test
	 */
	public function is_valid_for_product_pre_wc_3_0() {

		$current_language = rand_str();
		$product_id       = random_int( 1, 100 );
		$translated_id    = random_int( 101, 200 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		$wc          = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '2.6.9';
		WP_Mock::userFunction( 'WC', [ 'times' => 1, 'return' => $wc ] );

		$product_mock = $this->getMockBuilder( 'WC_Product' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type', 'get_id' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( false );
		$product_mock->id = $product_id;

		$coupon_mock = $this->getMockBuilder( 'WC_Coupon' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'is_valid_for_product' ) )
		                    ->getMock();

		$coupon_mock->method( 'is_valid_for_product' )->willReturn( true );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => true
		) );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args'   => $translated_id,
			'return' => true
		) );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( $translated_id );

		$coupon_is_valid = $subject->is_valid_for_product( false, $product_mock, $coupon_mock, array() );

		$this->assertTrue( $coupon_is_valid );

	}

	/**
	 * @test
	 */
	public function original_is_valid_for_product() {

		$current_language = rand_str();
		$product_id       = random_int( 1, 100 );
		$translated_id    = random_int( 101, 200 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$wc          = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '3.2';
		WP_Mock::userFunction( 'WC', [ 'times' => 1, 'return' => $wc ] );

		$subject = $this->get_subject( null, $sitepress );

		$product_mock = $this->getMockBuilder( 'WC_Product_Variation' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type', 'get_parent_id' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( true );
		$product_mock->method( 'get_parent_id' )->willReturn( $product_id );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( $product_id );


		$coupon_mock = $this->getMockBuilder( 'WC_Coupon' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'is_valid_for_product' ) )
		                    ->getMock();

		$coupon_mock->expects( $this->never() )->method( 'is_valid_for_product' )->willReturn( true );

		$coupon_is_valid = $subject->is_valid_for_product( false, $product_mock, $coupon_mock, array() );

		$this->assertFalse( $coupon_is_valid );

	}

	/**
	 * @test
	 */
	public function original_is_valid_for_product_pre_wc_3_0() {

		$current_language = rand_str();
		$product_id       = random_int( 1, 100 );
		$translated_id    = random_int( 101, 200 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$wc          = $this->getMockBuilder( 'woocommerce' )->disableOriginalConstructor()->getMock();
		$wc->version = '2.6.9';
		WP_Mock::userFunction( 'WC', [ 'times' => 1, 'return' => $wc ] );

		$subject = $this->get_subject( null, $sitepress );

		$product_mock = $this->getMockBuilder( 'WC_Product_Variation' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( true );
		$product_mock->parent     = $this->getMockBuilder( 'WC_Product' )
		                                 ->disableOriginalConstructor()
		                                 ->setMethods()
		                                 ->getMock();
		$product_mock->parent->id = $product_id;

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( $product_id );


		$coupon_mock = $this->getMockBuilder( 'WC_Coupon' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'is_valid_for_product' ) )
		                    ->getMock();

		$coupon_mock->expects( $this->never() )->method( 'is_valid_for_product' );

		$coupon_is_valid = $subject->is_valid_for_product( false, $product_mock, $coupon_mock, array() );

		$this->assertFalse( $coupon_is_valid );

	}

}
