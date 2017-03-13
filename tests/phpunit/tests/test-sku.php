<?php

/**
 * @author OnTheGo Systems
 * @group  sku
 */
class Test_SKU extends OTGS_TestCase {
	/**
	 * @return array
	 */
	private function get_stubs() {
		$wcml      = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods( array( 'get_element_trid' ) )->getMock();
		$wpdb      = $this->stubs->wpdb();

		return array( $wcml, $sitepress, $wpdb );
	}

	/**
	 * @test
	 */
	function it_must_return_false_if_sku_is_not_found() {
		WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		//Stubs
		list( $wcml, $sitepress, $wpdb ) = $this->get_stubs();

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpdb );

		$this->assertFalse( $subject->check_product_sku( false, mt_rand(), 'test_sku' ) );
	}

	/**
	 * @test
	 */
	function it_must_return_false_if_there_are_no_other_products_with_the_same_sku() {
		WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::wpFunction( 'get_post_type', array( 'return' => 'product' ) );
		WP_Mock::wpPassthruFunction( 'wp_slash' );

		//Stubs
		list( $wcml, $sitepress, $wpdb ) = $this->get_stubs();

		//Mocks
		$wpdb->method( 'get_results' )->willReturn( array() );

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpdb );

		$this->assertFalse( $subject->check_product_sku( true, mt_rand(), 'test_sku' ) );
	}

	/**
	 * @test
	 */
	function it_must_return_false_if_there_are_other_products_with_the_same_sku_and_same_trid() {
		WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::wpFunction( 'get_post_type', array( 'return' => 'product' ) );
		WP_Mock::wpPassthruFunction( 'wp_slash' );

		//Stubs
		list( $wcml, $sitepress, $wpdb ) = $this->get_stubs();

		//Mocks
		$trid                = mt_rand();
		$original_product_id = mt_rand( 100, 200 );

		$product_a       = new stdClass();
		$product_a->ID   = $original_product_id;
		$product_a->trid = $trid;
		$product_b       = new stdClass();
		$product_b->ID   = 2;
		$product_b->trid = $trid;

		$products = array( $product_a->ID => $product_a, $product_b->ID => $product_b );

		$sitepress->method( 'get_element_trid' )->willReturnCallback(
			function ( $product_id ) use ( $products ) {
				return $products[ $product_id ]->trid;
			}
		);

		$wpdb->method( 'get_results' )->willReturn( $products );

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpdb );

		$this->assertFalse( $subject->check_product_sku( true, $original_product_id, 'test_sku' ) );
	}

	/**
	 * @test
	 */
	function it_must_return_true_if_there_are_other_products_with_the_same_sku_on_different_trid() {
		WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::wpFunction( 'get_post_type', array( 'return' => 'product' ) );
		WP_Mock::wpPassthruFunction( 'wp_slash' );

		//Stubs
		list( $wcml, $sitepress, $wpdb ) = $this->get_stubs();

		//Mocks
		$trid                = mt_rand();
		$original_product_id = mt_rand( 100, 200 );

		$product_a       = new stdClass();
		$product_a->ID   = $original_product_id;
		$product_a->trid = 1;
		$product_b       = new stdClass();
		$product_b->ID   = 2;
		$product_b->trid = $trid;

		$products = array( $product_a->ID => $product_a, $product_b->ID => $product_b );

		$sitepress->method( 'get_element_trid' )->willReturnCallback(
			function ( $product_id ) use ( $products ) {
				return $products[ $product_id ]->trid;
			}
		);

		$wpdb->method( 'get_results' )->willReturn( $products );

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpdb );

		$this->assertTrue( $subject->check_product_sku( true, $original_product_id, 'test_sku' ) );
	}
}
