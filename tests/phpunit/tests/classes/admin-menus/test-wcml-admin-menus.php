<?php

class Test_WCML_Admin_Menus extends OTGS_TestCase {


	public function restrict_admin_with_redirect_mock( $trnsl_interface ) {

		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )->setMethods( array( 'is_original_product' ) )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->settings = array();
		$woocommerce_wpml->settings[ 'trnsl_interface' ] = $trnsl_interface;

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => false
		));

		WCML_Admin_Menus::set_up_menus( $woocommerce_wpml, $sitepress, $this->stubs->wpdb(), true );

		$product_id = mt_rand( 1, 100 );
		$_GET['post'] = $product_id;

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args' => array( $product_id ),
			'return' => 'product'
		));

	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function restrict_admin_with_redirect_product_translation() {
		global $pagenow;
		$pagenow = 'post.php';

		\WP_Mock::wpFunction( 'is_ajax', array(
			'return' => false
		));

		$this->restrict_admin_with_redirect_mock( true );

		$admin_url= rand_str();

		\WP_Mock::wpFunction( 'admin_url', array(
			'args' => array( 'admin.php?page=wpml-wcml&tab=products&prid=' . $_GET['post']  ),
			'return' => $admin_url
		));

		\WP_Mock::wpFunction( 'wp_redirect', array(
			'args' => array( $admin_url  ),
			'times' => 1,
			'return' => true
		));

		WCML_Admin_Menus::restrict_admin_with_redirect();
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function restrict_admin_with_redirect_admin_duplicate_page() {
		global $pagenow;
		$pagenow = 'admin.php';

		$_GET['action'] = 'duplicate_product';

		$this->restrict_admin_with_redirect_mock( true );

		$admin_url= rand_str();

		\WP_Mock::wpFunction( 'admin_url', array(
			'args' => array( 'admin.php?page=wpml-wcml&tab=products'  ),
			'times' => 1,
			'return' => $admin_url
		));

		\WP_Mock::wpFunction( 'wp_redirect', array(
			'args' => array( $admin_url  ),
			'times' => 1,
			'return' => true
		));

		WCML_Admin_Menus::restrict_admin_with_redirect();
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function restrict_admin_with_redirect_editing_product_in_non_default_lang() {
		global $pagenow;
		$pagenow = 'post.php';

		$this->restrict_admin_with_redirect_mock( false );

		\WP_Mock::expectActionAdded( 'admin_notices' , array( 'WCML_Admin_Menus', 'inf_editing_product_in_non_default_lang' ) );

		WCML_Admin_Menus::restrict_admin_with_redirect();
	}

    public function tearDown(){
	    unset( $_GET['post'] );
    }

}
