<?php

/**
 * @group refactoring
 */
class Test_WCML_Admin_Menu_Language_Switcher extends OTGS_TestCase {

	public function tearDown() {
		parent::tearDown();

		unset( $_GET['post_type'], $_GET['post'], $_GET['page'] );
	}

	/**
	 * @test
	 */
	public function it_does_not_remove_hook_if_non_admin_request() {
		$sitepress = $this->getMockBuilder( 'SitePress' )->setMethods( array( 'admin_language_switcher' ) )->getMock();
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		\WP_Mock::wpFunction('remove_action', array( 'times' => 0 ));

		$subject = new WCML_Admin_Menu_Language_Switcher( false, $sitepress );
		$subject->remove_hook();
	}

	/**
	 * @test
	 * @dataProvider data_provider
	 *
	 * @param string $post_type
	 * @param string $post
	 * @param string $page
	 * @param string $pagenow
	 */
	public function it_removes_hook( $post_type, $post, $page, $pagenow ) {
		$_GET['post_type'] = $post_type;
		$_GET['post'] = $post;
		$_GET['page'] = $page;


		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'get_post_type', array( 'return' => $post_type ) );

		\WP_Mock::onFilter( 'wcml_is_attributes_page' )
		        ->with( true )
		        ->reply( true );

		$sitepress = $this->getMockBuilder( 'SitePress' )->setMethods( array( 'admin_language_switcher' ) )->getMock();
		\WP_Mock::wpFunction('remove_action', array(
			'times' => 1,
			'args' => array( 'wp_before_admin_bar_render', array( $sitepress, 'admin_language_switcher' ) ),
		));

		$subject = new WCML_Admin_Menu_Language_Switcher( $pagenow, $sitepress );
		$subject->remove_hook();
	}

	public function data_provider() {
		return array(
			'Is WPML WCML page' => array( false, false, 'wpml-wcml', false ),
			'Is new order' => array( 'shop_order', false, false, 'edit.php' ),
			'Is edit order' => array( 'shop_order', 10, false, 'post.php' ),
			'Is edit coupon' => array( 'shop_coupon', 10, false, 'post.php' ),
			'Is shipping zone' => array( false, false, 'shipping_zones', false ),
			'Is attributes page' => array( false, false, 'product_attributes', false ),
		);
	}
}
