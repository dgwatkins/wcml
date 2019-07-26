<?php

/**
 * Class Test_WCML_Coupons
 *
 * @group coupons
 */
class Test_WCML_Coupons extends OTGS_TestCase {

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\woocommerce_wpml
	 */
	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\SitePress
	 */
	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'SitePress' )
					->disableOriginalConstructor()
					->setMethods( array( 'get_current_language', 'get_object_id' ) )
					->getMock();
	}

	/**
	 * @param null|\woocommerce_wpml $woocommerce_wpml
	 * @param null|\SitePress        $sitepress
	 *
	 * @return \WCML_Coupons
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

		WP_Mock::expectActionAdded( 'woocommerce_coupon_loaded', array( $subject, 'wcml_coupon_loaded' ) );
		WP_Mock::expectActionAdded( 'admin_init', array( $subject, 'icl_adjust_terms_filtering' ) );
		WP_Mock::expectFilterAdded( 'woocommerce_coupon_is_valid_for_product', array(
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
		$product_id       = mt_rand( 1, 100 );
		$translated_id    = mt_rand( 101, 200 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		/** @var \WC_Product|\PHPUnit_Framework_MockObject_MockObject $product_mock */
		$product_mock = $this->getMockBuilder( 'WC_Product' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type', 'get_id' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( false );
		$product_mock->method( 'get_id' )->willReturn( $product_id );

		/** @var \PHPUnit_Framework_MockObject_MockObject|\WC_Coupon $coupon_mock */
		$coupon_mock = $this->getMockBuilder( 'WC_Coupon' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'is_valid_for_product' ) )
		                    ->getMock();

		$coupon_mock->method( 'is_valid_for_product' )->willReturn( true );

		WP_Mock::wpFunction( 'remove_filter', array(
			'return' => true
		) );

		WP_Mock::wpFunction( 'wc_get_product', array(
			'args'   => $translated_id,
			'return' => true
		) );

		WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( $translated_id );

		$coupon_is_valid = $subject->is_valid_for_product( false, $product_mock, $coupon_mock, array() );

		$this->assertTrue( $coupon_is_valid );

	}

	/**
	 * @test
	 */
	public function original_is_valid_for_product() {

		$current_language = rand_str();
		$product_id       = mt_rand( 1, 100 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		/** @var \WC_Product|\PHPUnit_Framework_MockObject_MockObject $product_mock */
		$product_mock = $this->getMockBuilder( 'WC_Product_Variation' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type', 'get_parent_id' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( true );
		$product_mock->method( 'get_parent_id' )->willReturn( $product_id );

		WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( $product_id );

		/** @var \PHPUnit_Framework_MockObject_MockObject|\WC_Coupon $coupon_mock */
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
	public function translation_is_null_is_valid_for_product() {


		$current_language = rand_str();
		$product_id       = mt_rand( 1, 100 );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		/** @var \WC_Product|\PHPUnit_Framework_MockObject_MockObject $product_mock */
		$product_mock = $this->getMockBuilder( 'WC_Product_Variation' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'is_type', 'get_parent_id' ) )
		                     ->getMock();

		$product_mock->method( 'is_type' )->with( 'variation' )->willReturn( true );
		$product_mock->method( 'get_parent_id' )->willReturn( $product_id );

		WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $current_language )->reply( null );

		$coupon_is_valid = $subject->is_valid_for_product( false, $product_mock, null, array() );

		$this->assertFalse( $coupon_is_valid );

	}

	/**
	 * @test
	 */
	public function it_should_filter_product_ids_from_coupon_data() {

		$product_id = 1;
		$translated_product_id = 2;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array( $product_id ) );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array() );
		$coupon->method( 'get_product_categories' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array() );

		WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $product_id ),
			'return' => 'product'
		));

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $product_id )->willReturn( $translated_product_id );

		$coupon->expects( $this->once() )->method( 'set_product_ids' )->with( array( $translated_product_id ) )->willReturn( true );

		$subject = $this->get_subject( null, $sitepress );
		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_product_ids_from_coupon_data() {

		$product_id = 1;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array( $product_id ) );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array() );
		$coupon->method( 'get_product_categories' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array() );

		WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $product_id ),
			'return' => 'product'
		));

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $product_id )->willReturn( false );

		$coupon->expects( $this->never() )->method( 'set_product_ids' );

		$subject = $this->get_subject( null, $sitepress );
		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_filter_excluded_product_ids_from_coupon_data() {

		$excluded_product_id = 1;
		$translated_product_id = 2;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array( $excluded_product_id ) );
		$coupon->method( 'get_product_categories' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array() );

		WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $excluded_product_id ),
			'return' => 'product'
		));

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $excluded_product_id )->willReturn( $translated_product_id );

		$coupon->expects( $this->once() )->method( 'set_excluded_product_ids' )->with( array( $translated_product_id ) );

		$subject = $this->get_subject( null, $sitepress );
		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_excluded_product_ids_from_coupon_data() {

		$excluded_product_id = 1;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array( $excluded_product_id ) );
		$coupon->method( 'get_product_categories' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array() );

		WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $excluded_product_id ),
			'return' => 'product'
		));

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $excluded_product_id )->willReturn( false );

		$coupon->expects( $this->never() )->method( 'set_excluded_product_ids' );

		$subject = $this->get_subject( null, $sitepress );
		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_filter_product_categories_ids_from_coupon_data() {

		$category_id = 1;
		$translated_category_id = 2;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array( ) );
		$coupon->method( 'get_product_categories' )->willReturn( array( $category_id ) );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array() );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $category_id )->willReturn( $translated_category_id );

		$coupon->expects( $this->once() )->method( 'set_product_categories' )->with( array( $translated_category_id ) )->willReturn( true );

		$subject = $this->get_subject( null, $sitepress );
		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_product_categories_ids_from_coupon_data() {

		$category_id = 1;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array( ) );
		$coupon->method( 'get_product_categories' )->willReturn( array( $category_id ) );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array() );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $category_id )->willReturn( false );

		$subject = $this->get_subject( null, $sitepress );

		$coupon->expects( $this->never() )->method( 'set_product_categories' );

		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_filter_product_exclude_categories_ids_from_coupon_data() {

		$excluded_category_id = 1;
		$translated_category_id = 2;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array( ) );
		$coupon->method( 'get_product_categories' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array( $excluded_category_id ) );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_object_id' )->with( $excluded_category_id )->willReturn( $translated_category_id );

		$subject = $this->get_subject( null, $sitepress );

		$coupon->expects( $this->once() )->method( 'set_excluded_product_categories' )->with( array( $translated_category_id ) )->willReturn( true );

		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_product_exclude_categories_ids_from_coupon_data() {

		$excluded_category_id = 1;

		$coupon = $this->get_wc_coupon_data_mock();
		$coupon->method( 'get_product_ids' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_ids' )->willReturn( array( ) );
		$coupon->method( 'get_product_categories' )->willReturn( array() );
		$coupon->method( 'get_excluded_product_categories' )->willReturn( array( $excluded_category_id ) );

		WP_Mock::onFilter( 'translate_object_id' )->with( $excluded_category_id, 'product_cat', false )->reply( false );

		$subject = $this->get_subject();

		$coupon->expects( $this->never() )->method( 'set_excluded_product_categories' );

		$subject->wcml_coupon_loaded( $coupon );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WC_Coupon
	 */
	private function get_wc_coupon_data_mock() {
		return $this->getMockBuilder( 'WC_Coupon' )
		            ->disableOriginalConstructor()
		            ->setMethods(
			            array(
				            'get_product_ids',
				            'get_excluded_product_ids',
				            'get_product_categories',
				            'get_excluded_product_categories',
				            'set_product_ids',
				            'set_excluded_product_ids',
				            'set_product_categories',
				            'set_excluded_product_categories'
			            ) )
		            ->getMock();
	}

}
