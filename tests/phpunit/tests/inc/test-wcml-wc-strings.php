<?php

class Test_WCML_WC_Strings extends OTGS_TestCase {

	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress( $wp_api = null ) {
		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api','get_current_language', 'get_default_language' ) )
		                  ->getMock();

		if( null === $wp_api ){
			$wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'constant', 'version_compare' ) )
		            ->getMock();
	}

	/**
	 * @return WCML_WC_Strings
	 */
	private function get_subject(  $woocommerce_wpml = null, $sitepress = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}

		return new WCML_WC_Strings( $woocommerce_wpml, $sitepress, $this->stubs->wpdb() );

	}

	/**
	 * @test
	 */
	public function add_on_init_hooks(){

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( rand_str() );

		\WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );

		$subject = $this->get_subject( null, $sitepress );
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_item_name', array( $subject, 'translated_cart_item_name' ), -1, 2 );
		$subject->add_on_init_hooks();
	}

	/**
	 * @test
	 */
	public function not_listing_pages_admin_hooks(){

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( rand_str() );

		global $pagenow;
		$pagenow = rand_str();

		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );

		\WP_Mock::userFunction( 'wpml_is_ajax', array( 'return' => false ) );

		$subject = $this->get_subject( null, $sitepress );
		\WP_Mock::expectFilterAdded( 'woocommerce_attribute_taxonomies', array( $subject, 'translate_attribute_taxonomies_labels' ) );
		$subject->add_on_init_hooks();
	}

	/**
	 * @test
	 */
	public function filter_woocommerce_breadcrumbs_prepend_shop_page_if_needed(){

		$sitepress = $this->get_sitepress_with_mocked_current_and_default_languages();

		$shop_page_id  = mt_rand( 1, 10 );
		$shop_page = new stdClass();
		$shop_page->ID = mt_rand( 11, 20 );
		$shop_page->post_title = rand_str();
		$shop_page->post_name = rand_str();
		$page_on_front = mt_rand( 21, 30 );
		$product_type_post_link = rand_str();

		\WP_Mock::userFunction( 'wc_get_page_id', array(
				'args' => array( 'shop' ),
				'return' => $shop_page_id
			)
		);

		\WP_Mock::userFunction( 'get_post_status', array(
				'args' => array( $shop_page_id ),
				'return' => 'publish'
			)
		);

		\WP_Mock::userFunction( 'get_post', array(
				'args' => array( $shop_page_id ),
				'return' => $shop_page
			)
		);

		\WP_Mock::userFunction( 'is_woocommerce', array(
				'return' => true
			)
		);

		\WP_Mock::userFunction( 'get_option', array(
				'args' => array( 'page_on_front' ),
				'return' => $page_on_front
			)
		);

		\WP_Mock::userFunction( 'get_post_type_archive_link', array(
				'args' => array( 'product' ),
				'return' => $product_type_post_link
			)
		);

		\WP_Mock::userFunction( 'get_home_url', array(
				'return' => rand_str()
			)
		);

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->url_translation = $this->getMockBuilder( 'WCML_Url_Translation' )
		                                          ->disableOriginalConstructor()
		                                          ->setMethods( array(
			                                          'get_base_translation'
		                                          ) )
		                                          ->getMock();
		$translated_base = array(
			'original_value' => rand_str(),
			'translated_base' => $shop_page->post_name
		);

		$woocommerce_wpml->url_translation->method( 'get_base_translation' )->willReturn( $translated_base );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$level1_url = rand_str();
		$level2_url = rand_str();
		$breadcrumbs = array(
			array( 'level 1', $level1_url ),
			array( 'level 2', $level2_url )
		);

		$expected_breadcrumbs = array(
			array( $shop_page->post_title, $product_type_post_link ),
			array( 'level 1', $level1_url ),
			array( 'level 2', $level2_url )
		);

		$filtered_breadcrumbs = $subject->filter_woocommerce_breadcrumbs( $breadcrumbs, new stdClass() );

		$this->assertEquals( $expected_breadcrumbs, $filtered_breadcrumbs );
	}

	/**
	 * @test
	 */
	public function filter_woocommerce_breadcrumbs_append_shop_page_after_home_page_if_needed(){

		$sitepress = $this->get_sitepress_with_mocked_current_and_default_languages();

		$shop_page_id  = mt_rand( 1, 10 );
		$shop_page = new stdClass();
		$shop_page->ID = mt_rand( 11, 20 );
		$shop_page->post_title = rand_str();
		$shop_page->post_name = rand_str();
		$page_on_front = mt_rand( 21, 30 );
		$product_type_post_link = rand_str();

		\WP_Mock::userFunction( 'wc_get_page_id', array(
				'args' => array( 'shop' ),
				'return' => $shop_page_id
			)
		);

		\WP_Mock::userFunction( 'get_post_status', array(
				'args' => array( $shop_page_id ),
				'return' => 'publish'
			)
		);

		\WP_Mock::userFunction( 'get_post', array(
				'args' => array( $shop_page_id ),
				'return' => $shop_page
			)
		);

		\WP_Mock::userFunction( 'is_woocommerce', array(
				'return' => true
			)
		);

		\WP_Mock::userFunction( 'get_option', array(
				'args' => array( 'page_on_front' ),
				'return' => $page_on_front
			)
		);

		\WP_Mock::userFunction( 'get_post_type_archive_link', array(
				'args' => array( 'product' ),
				'return' => $product_type_post_link
			)
		);


		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->url_translation = $this->getMockBuilder( 'WCML_Url_Translation' )
		                                          ->disableOriginalConstructor()
		                                          ->setMethods( array(
			                                          'get_base_translation'
		                                          ) )
		                                          ->getMock();
		$translated_base = array(
			'original_value' => rand_str(),
			'translated_base' => $shop_page->post_name
		);

		$woocommerce_wpml->url_translation->method( 'get_base_translation' )->willReturn( $translated_base );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$home_url = rand_str();

		\WP_Mock::userFunction( 'get_home_url', array(
				'return' => $home_url
			)
		);

		$level2_url = rand_str();
		$breadcrumbs = array(
			array( 'home', $home_url ),
			array( 'level 2', $level2_url )
		);

		$expected_breadcrumbs = array(
			array( 'home', $home_url ),
			array( $shop_page->post_title, $product_type_post_link ),
			array( 'level 2', $level2_url )
		);

		$filtered_breadcrumbs = $subject->filter_woocommerce_breadcrumbs( $breadcrumbs, new stdClass() );

		$this->assertEquals( $expected_breadcrumbs, $filtered_breadcrumbs );
	}

	/**
	 * @return \SitePress
	 */
	private function get_sitepress_with_mocked_current_and_default_languages() {
		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( rand_str() );
		$sitepress->method( 'get_default_language' )->willReturn( rand_str() );

		return $sitepress;
	}

}
