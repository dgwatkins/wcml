<?php

/**
 * Class Test_WCML_Media
 *
 * @group wpmlcore-5614
 */
class Test_WCML_Media extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_sync_product_gallery() {
		$wcml = $this->getMockBuilder( 'woocommerce_wpml' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_translations', 'get_element_trid' ) )
		                  ->getMock();

		$sitepress->expects( $this->once() )
		          ->method( 'get_element_translations' )
		          ->willReturn( array() );


		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$product_id        = 2;
		$att_id            = 1;
		$duplicated_att_id = null;

		\WP_Mock::wpFunction( 'wp_get_post_parent_id', array(
			'args'   => $att_id,
			'return' => $product_id
		) );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => $product_id,
			'return' => 'product'
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array() );

		$subject = new WCML_Media( $wcml, $sitepress, $wpdb );
		$subject->sync_product_gallery_duplicate_attachment( $att_id, $duplicated_att_id );
	}

	/**
	 * @test
	 */
	public function it_does_not_sync_product_gallery_when_it_is_already_synced() {
		$wcml = $this->getMockBuilder( 'woocommerce_wpml' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_translations' ) )
		                  ->getMock();

		$sitepress->expects( $this->never() )
		          ->method( 'get_element_translations' )
		          ->willReturn( array() );


		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$product_id        = 2;
		$att_id            = 1;
		$duplicated_att_id = 3;

		\WP_Mock::wpFunction( 'wp_get_post_parent_id', array(
			'args'   => $att_id,
			'return' => $product_id
		) );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => $product_id,
			'return' => 'product'
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array() );

		$subject = new WCML_Media( $wcml, $sitepress, $wpdb );
		$subject->sync_product_gallery_duplicate_attachment( $att_id, $duplicated_att_id );
	}

	/**
	 * @test
	 */
	public function it_does_not_sync_product_gallery_when_it_is_not_a_product() {
		$wcml = $this->getMockBuilder( 'woocommerce_wpml' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_translations' ) )
		                  ->getMock();

		$sitepress->expects( $this->never() )
		          ->method( 'get_element_translations' )
		          ->willReturn( array() );


		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$product_id        = 2;
		$att_id            = 1;
		$duplicated_att_id = null;

		\WP_Mock::wpFunction( 'wp_get_post_parent_id', array(
			'args'   => $att_id,
			'return' => $product_id
		) );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => $product_id,
			'return' => 'page'
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array() );

		$subject = new WCML_Media( $wcml, $sitepress, $wpdb );
		$subject->sync_product_gallery_duplicate_attachment( $att_id, $duplicated_att_id );
	}
}