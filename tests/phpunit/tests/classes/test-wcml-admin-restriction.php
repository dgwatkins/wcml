<?php

/**
 * @group refactoring
 */
class Test_WCML_Admin_Restriction extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();
		\WP_Mock::wpPassthruFunction( 'admin_url' );
		\WP_Mock::wpFunction( 'wp_die', array( 'return' => null ) );
	}

	public function tearDown() {
		parent::tearDown();
		unset( $_GET['action'], $_GET['post'] );
	}

	/**
	 * @test
	 */
	public function it_does_not_add_any_hooks_if_wcml_dependencies_are_not_correct() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->check_dependencies = false;

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), 'test.php' );

		$this->expectActionAdded( 'admin_init', array( $subject, 'redirect_if_rules_are_not_fulfilled' ), 10, 1, 0 );
		$this->expectFilterAdded( 'woocommerce_prevent_admin_access', array( $subject, 'check_user_admin_access' ), 10,1, 0 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_does_not_add_redirection_hooks_in_frontend() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->check_dependencies = true;

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), 'test.php' );

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$this->expectActionAdded( 'admin_init', array( $subject, 'redirect_if_rules_are_not_fulfilled' ), 10, 1, 0 );
		$this->expectFilterAdded( 'woocommerce_prevent_admin_access', array( $subject, 'check_user_admin_access' ), 10, 1, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_does_not_add_redirection_hooks_if_a_wpml_core_is_not_activated() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->check_dependencies = true;

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', null, 'test.php' );

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$this->expectActionAdded( 'admin_init', array( $subject, 'redirect_if_rules_are_not_fulfilled' ), 10, 1, 0 );
		$this->expectFilterAdded( 'woocommerce_prevent_admin_access', array( $subject, 'check_user_admin_access' ), 10, 1, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_does_not_prevent_access_if_a_current_user_has_required_privileges() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');

		\WP_Mock::wpFunction( 'current_user_can', array( 'args' => array( 'wpml_manage_woocommerce_multilingual' ), 'return' => true ) );

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), 'test.php' );
		$this->assertFalse( $subject->check_user_admin_access( true ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_prevent_access_if_a_current_user_has_defined_language_pairs() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');

		\WP_Mock::wpFunction( 'current_user_can', array( 'args' => array( 'wpml_manage_woocommerce_multilingual' ), 'return' => false ) );

		$user_id = 12;
		\WP_Mock::wpFunction( 'get_current_user_id', array( 'return' => $user_id ) );
		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array( $user_id, 'wp_language_pairs', true ),
			'return' => array( 'en' => 'EN', 'fr' => 'FR' ),
		));

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), 'test.php' );
		$this->assertFalse( $subject->check_user_admin_access( true ) );
	}

	/**
	 * @test
	 */
	public function it_skips_all_redirections_if_a_trnsl_interface_setting_is_not_true() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->settings = array( 'trnsl_interface' => false );

		// below lines would invoke single product redirection if  trnsl_interface was true
		$post_id = 12;
		$pagenow = 'post.php';
		\WP_Mock::wpFunction( 'is_ajax', array( 'return' => false ) );
		$_GET['post'] = $post_id;

		$woocommerce_wpml->products = $this->createMock( 'WCML_Products' );
		$woocommerce_wpml->products->method( 'is_original_product' )->with( $post_id )->willReturn( false );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'product',
		) );

		\WP_Mock::wpFunction( 'wp_redirect', array( 'times' => 0 ) );

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), $pagenow );
		$subject->redirect_if_rules_are_not_fulfilled();
	}

	/**
	 * @test
	 */
	public function it_redirects_to_single_product() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->settings = array( 'trnsl_interface' => true );

		$post_id = 12;
		$pagenow = 'post.php';
		\WP_Mock::wpFunction( 'is_ajax', array( 'return' => false ) );
		$_GET['post'] = $post_id;

		$woocommerce_wpml->products = $this->createMock( 'WCML_Products' );
		$woocommerce_wpml->products->method( 'is_original_product' )->with( $post_id )->willReturn( false );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'product',
		) );

		\WP_Mock::wpFunction( 'wp_redirect', array( 'times' => 1, 'args' => WCML_Admin_Restriction::URL . '&prid=' . $post_id ) );

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), $pagenow );
		$subject->redirect_if_rules_are_not_fulfilled();
	}

	/**
	 * @param string $action
	 *
	 * @test
	 * @dataProvider dp_actions
	 */
	public function it_skips_single_product_redirection_for_specific_actions( $action ) {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->settings = array( 'trnsl_interface' => true );

		$_GET['action'] = $action;

		$post_id = 12;
		$pagenow = 'post.php';
		\WP_Mock::wpFunction( 'is_ajax', array( 'return' => false ) );
		$_GET['post'] = $post_id;

		$woocommerce_wpml->products = $this->createMock( 'WCML_Products' );
		$woocommerce_wpml->products->method( 'is_original_product' )->with( $post_id )->willReturn( false );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $post_id ),
			'return' => 'product',
		) );

		\WP_Mock::wpFunction( 'wp_redirect', array( 'times' => 0 ) );

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $this->get_sitepress(), $pagenow );
		$subject->redirect_if_rules_are_not_fulfilled();
	}

	public function dp_actions() {
		return array(
			array( 'trash' ),
			array( 'delete' ),
			array( 'untrash' )
		);
	}

	/**
	 * @test
	 */
	public function it_redirects_when_a_non_default_lang_product_is_being_duplicated() {
		$woocommerce_wpml = $this->createMock('woocommerce_wpml');
		$woocommerce_wpml->settings = array( 'trnsl_interface' => true );

		$pagenow = 'admin.php';
		$_GET['action'] = 'duplicate_product';
		$_GET['post'] = $post_id = 12;

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_default_language' )->willReturn( 'en' );
		$sitepress->method( 'get_language_for_element' )->with($post_id, 'post_product')->willReturn( 'fr' );

		\WP_Mock::wpFunction( 'wp_redirect', array( 'times' => 1, 'args' => array( WCML_Admin_Restriction::URL ) ) );

		$subject = new WCML_Admin_Restriction( $woocommerce_wpml, 'wp_', $sitepress, $pagenow );
		$subject->redirect_if_rules_are_not_fulfilled();
	}

	private function get_sitepress() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'get_default_language', 'get_language_for_element' ) )
		            ->getMock();
	}
}
