<?php

/**
 * Class Test_WCML_Setup
 * @group wcml-1987
 *
 */
class Test_WCML_Setup extends OTGS_TestCase {

	public function setUp(){
		parent::setUp();

		\WP_Mock::passthruFunction('__');
		\WP_Mock::passthruFunction('esc_html__');

		\WP_Mock::userFunction( 'admin_url', array(
			'return' => function ( $step ) {
				return 'admin.php?page=wcml-setup&step=' . $step;
			}
		) );

		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );
	}

	private function get_wcml_setup_ui_mock(){
		return $this->getMockBuilder( 'WCML_Setup_UI' )
		            ->disableOriginalConstructor()
		            ->setMethods( array() )
		            ->getMock();
	}

	private function get_wcml_setup_handlers_mock(){
		return $this->getMockBuilder( 'WCML_Setup_Handlers' )
		     ->disableOriginalConstructor()
		     ->setMethods( array() )
		     ->getMock();
	}

	private function get_woocommerce_wpml_mock(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		     ->disableOriginalConstructor()
		     ->setMethods( array() )
		     ->getMock();
	}

	private function get_sitepress_mock() {

		$sitepress = $this->getMockBuilder( SitePress::class )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_wp_api' ) )
		                        ->getMock();

		return $sitepress;
	}

	public function tearDown(){
		parent::tearDown();
	}

	private function get_subject( $woocommerce_wpml = null ){

		if( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		return new WCML_Setup(
			$this->get_wcml_setup_ui_mock(),
			$this->get_wcml_setup_handlers_mock(),
			$woocommerce_wpml,
			$this->get_sitepress_mock()
		);
	}

	/**
	 * @test
	 */
	public function adding_hooks_cant_manage_options(){
		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => false
		) );

		$this->expectActionAdded( 'admin_init', array( $subject, 'wizard'), 10, 1, 0 );
		$this->expectActionAdded( 'admin_init', array( $subject, 'handle_steps'), 0, 1, 0 );
		$this->expectFilterAdded( 'wp_redirect', array( $subject, 'redirect_filters'), 10, 1, 0 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function adding_hooks_can_manage_options(){
		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => true
		) );

		\WP_Mock::expectActionAdded( 'admin_init', array( $subject, 'wizard'), 10, 1);
		\WP_Mock::expectActionAdded( 'admin_init', array( $subject, 'handle_steps'), 0, 1 );
		\WP_Mock::expectFilterAdded( 'wp_redirect', array( $subject, 'redirect_filters'), 10, 1 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function adding_hooks_setup_NOT_completed(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::userFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => false
		) );

		$woocommerce_wpml->settings['set_up_wizard_run'] = false;

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function adding_hooks_setup_completed(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::userFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => false
		) );

		$woocommerce_wpml->settings['set_up_wizard_run'] = true;

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function setup_redirect_no_activation_redirect(){
		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => false
		) );

		\WP_Mock::userFunction( 'delete_transient', array(
			'times' => 0
		) );

		$subject->setup_redirect();
	}

	/**
	 * @test
	 */
	public function setup_redirect_yes_activation_redirect_do_not_redirect_to_setup(){
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::userFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => array('install')
		) );

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wc_activation_redirect',
			'return' => false
		) );

		// is_wcml_setup_page = true
		$_GET['page'] = 'wcml-setup';

		$this->assertNull( $subject->setup_redirect() );

	}

	/**
	 * @test
	 */
	public function setup_redirect_yes_activation_redirect_do_not_redirect_to_setup_if_is_already_in_WC_Wizard(){
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::userFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => [],
		) );

		// Is in WC wizard redirection
		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wc_activation_redirect',
			'return' => true
		) );

		\WP_Mock::userFunction( 'is_network_admin', array(
			'return' => false
		) );

		\WP_Mock::userFunction( 'current_user_can', array(
			'return' => true
		) );

		\WP_Mock::userFunction( 'wpml_is_ajax' )->andReturn( false );

		$this->assertNull( $subject->setup_redirect() );

	}

	/**
	 * @test
	 */
	public function setup_redirect_yes_activation_redirect_do_redirect_to_setup_Has_completed(){
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::userFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => array()
		) );

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wc_activation_redirect',
			'return' => false
		) );

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup' . rand_str();

		\WP_Mock::userFunction( 'is_network_admin', array(
			'return' => false
		) );

		\WP_Mock::userFunction( 'current_user_can', array(
			'return' => true
		) );

		\WP_Mock::userFunction( 'wpml_is_ajax' )->andReturn( false );

		// has_completed = yes
		$woocommerce_wpml->settings['set_up_wizard_run'] = true;

		\WP_Mock::userFunction( 'wp_safe_redirect', array(
			'times' => 0
		) );
		\WP_Mock::userFunction( 'wp_die', array(
			'times' => 0
		) );

		$subject->setup_redirect();
	}

	/**
	 * @test
	 */
	public function setup_redirect_yes_activation_redirect_do_redirect_to_setup_Has_completed_NOT(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::userFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => array()
		) );

		\WP_Mock::userFunction( 'get_transient', array(
			'args'   => '_wc_activation_redirect',
			'return' => false
		) );

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup' . rand_str();

		\WP_Mock::userFunction( 'is_network_admin', array(
			'return' => false
		) );

		\WP_Mock::userFunction( 'current_user_can', array(
			'return' => true
		) );

		\WP_Mock::userFunction( 'wpml_is_ajax' )->andReturn( false );

		// has_completed = no
		$woocommerce_wpml->settings['set_up_wizard_run'] = false;

		\WP_Mock::userFunction( 'wcml_safe_redirect', array(
			'times'  => 1,
			'return' => true,
		) );

		$subject->setup_redirect();
	}

	/**
	 * @test
	 */
	public function wizard_is_not_setup_page(){
		$subject = $this->get_subject();

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup' . rand_str();

		\WP_Mock::userFunction( 'wp_die', array(
			'times' => 0
		) );

		$subject->wizard();
	}

	/**
	 * @test
	 */
	public function wizard_is_setup_page(){
		\WP_Mock::userFunction( 'WCML\functions\assetLink' )->andReturn( '/uri/to/asset/' );

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup';
		$woocommerce_wpml->settings['set_up_wizard_splash'] = true;

		if ( ! defined( 'WCML_VERSION' ) ) {
			define( 'WCML_VERSION', rand_str() );
		}

		if ( ! defined( 'ICL_PLUGIN_URL' ) ) {
			define( 'ICL_PLUGIN_URL', rand_str() );
		}

		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', rand_str() );
		}

		\WP_Mock::userFunction( 'wp_enqueue_style', array(
			'times' => 2
		) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array(
			'times' => 1
		) );

		\WP_Mock::userFunction( 'wp_die', array(
			'times' => 1
		) );

		$subject->wizard();
	}

	/**
	 * @test
	 */
	public function complete_setup() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );
		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' );
		add_action( 'wcml_setup_completed', function() {
			$this->addToAssertionCount( 1 );
		} );

		$subject->complete_setup();
	}

	/**
	 * @test
	 */
	public function redirect_filters_without_next_step_url() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'sanitize_text_field', array(
			'times' => 0
		) );
		$url = rand_str();
		$this->assertSame( $url, $subject->redirect_filters( $url ) );
	}

	/**
	 * @test
	 */
	public function redirect_filters_with_next_step_url() {
		$subject = $this->get_subject();

		$_POST['next_step_url'] = rand_str();

		\WP_Mock::userFunction( 'sanitize_text_field', array(
			'times' => 1,
			'args' => [ $_POST['next_step_url'] ],
			'return' => $_POST['next_step_url']
		) );

		$url = rand_str();
		$this->assertSame( $_POST['next_step_url'], $subject->redirect_filters( $url ) );

	}

	/**
	 * @test
	 */
	public function handle_steps_invalid_nonce(){
		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'wp_create_nonce', array(
			'return' => rand_str()
		) );

		\WP_Mock::userFunction( 'sanitize_text_field', array(
			'times' => 0
		) );

		$subject->handle_steps();

	}

	/**
	 * @test
	 */
	public function handle_steps_valid_nonce(){
		$subject = $this->get_subject();

		$_POST['handle_step'] = rand_str();
		$_POST['nonce'] = rand_str();

		\WP_Mock::userFunction( 'wp_create_nonce', array(
			'return' => $_POST['nonce']
		) );

		\WP_Mock::userFunction( 'sanitize_text_field', array(
			'times' => 1,
			'args' => [ $_POST['handle_step'] ]
		) );

		$subject->handle_steps();

	}

}
