<?php

/**
 * @author OnTheGo Systems
 * @group  sku
 */
class Test_WCML_Products_SKU extends OTGS_TestCase {
	/**
	 * @return array
	 */
	private function get_stubs() {
		$wcml                   = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$sitepress              = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->getMock();
		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )->disableOriginalConstructor()->setMethods( array( 'get_element_trid' ) )->getMock();
		$wpdb                   = $this->stubs->wpdb();
		$wpml_cache             = $this->get_wpml_cache_mock();

		return array( $wcml, $sitepress, $wpml_post_translations, $wpdb, $wpml_cache );
	}

	private function get_wpml_cache_mock() {
		$that = $this;

		$wpml_cache_mock = $this->getMockBuilder( 'WPML_WP_Cache' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get', 'set' ) )
		                        ->getMock();

		$wpml_cache_mock->method( 'get' )->willReturnCallback(
			function ( $key ) use ( $that ) {
				if ( isset( $that->cached_data[ $key ] ) ) {
					$found = true;

					return $that->cached_data[ $key ];
				} else {
					return false;
				}
			}
		);

		$wpml_cache_mock->method( 'set' )->willReturnCallback(
			function ( $key, $value ) use ( $that ) {
				$that->cached_data[ $key ] = $value;
			}
		);

		return $wpml_cache_mock;
	}

	/**
	 * @return WCML_Products
	 */
	private function get_subject(){
		//Stubs
		list( $wcml, $sitepress, $wpml_post_translations, $wpdb, $wpml_cache ) = $this->get_stubs();

		// Test
		return new WCML_Products( $wcml, $sitepress, $wpml_post_translations, $wpdb, $wpml_cache );
	}

	/**
	 * @test
	 */
	function it_must_return_false_if_sku_is_not_found() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );

		$subject = $this->get_subject();

		$this->assertFalse( $subject->check_product_sku( false, mt_rand(), 'Test_WCML_Products_SKU' ) );
	}

	/**
	 * @test
	 */
	function it_must_return_false_if_there_are_no_other_products_with_the_same_sku() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'product' ) );
		WP_Mock::passthruFunction( 'wp_slash' );

		//Stubs
		list( $wcml, $sitepress, $wpml_post_translations, $wpdb ) = $this->get_stubs();

		//Mocks
		$wpdb->method( 'get_results' )->willReturn( array() );

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpml_post_translations, $wpdb );

		$this->assertFalse( $subject->check_product_sku( true, mt_rand(), 'Test_WCML_Products_SKU' ) );
	}

	/**
	 * @test
	 */
	function it_must_return_false_if_there_are_other_products_with_the_same_sku_and_same_trid() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'product' ) );
		WP_Mock::passthruFunction( 'wp_slash' );

		//Stubs
		list( $wcml, $sitepress, $wpml_post_translations, $wpdb ) = $this->get_stubs();

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

		$wpml_post_translations->method( 'get_element_trid' )->willReturnCallback(
			function ( $product_id ) use ( $products ) {
				return $products[ $product_id ]->trid;
			}
		);

		$wpdb->method( 'get_results' )->willReturn( $products );

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpml_post_translations, $wpdb );

		$this->assertFalse( $subject->check_product_sku( true, $original_product_id, 'Test_WCML_Products_SKU' ) );
	}

	/**
	 * @test
	 */
	function it_must_return_true_if_there_are_other_products_with_the_same_sku_on_different_trid() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'product' ) );
		WP_Mock::passthruFunction( 'wp_slash' );

		//Stubs
		list( $wcml, $sitepress, $wpml_post_translations, $wpdb ) = $this->get_stubs();

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

		$wpml_post_translations->method( 'get_element_trid' )->willReturnCallback(
			function ( $product_id ) use ( $products ) {
				return $products[ $product_id ]->trid;
			}
		);

		$wpdb->method( 'get_results' )->willReturn( $products );

		// Test
		$subject = new WCML_Products( $wcml, $sitepress, $wpml_post_translations, $wpdb );

		$this->assertTrue( $subject->check_product_sku( true, $original_product_id, 'Test_WCML_Products_SKU' ) );
	}
}
