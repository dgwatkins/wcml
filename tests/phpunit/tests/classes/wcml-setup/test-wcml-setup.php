<?php

/**
 * Class Test_WCML_Setup
 * @group wcml-1987
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 */
class Test_WCML_Setup extends OTGS_TestCase {

	public function setUp(){
		parent::setUp();

		\WP_Mock::wpPassthruFunction('__');
		\WP_Mock::wpPassthruFunction('esc_html__');

		$templates_factory_stub = Mockery::mock( 'overload:WPML_Templates_Factory' );

		\WP_Mock::wpFunction( 'admin_url', array(
			'return' => function ( $step ) {
				return 'admin.php?page=wcml-setup&step=' . $step;
			}
		) );


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

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_wp_api' ) )
		                        ->getMock();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'constant', 'version_compare' ) )
		                     ->getMock();

		$wp_api->method( 'version_compare' )->willReturn( true );

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

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

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => false
		) );

		$this->expectActionAdded( 'admin_init', array( $subject, 'wizard'), 10, 1, 0 );
		$this->expectActionAdded( 'admin_init', array( $subject, 'handle_steps'), 0, 1, 0 );
		$this->expectActionAdded( 'wp_redirect', array( $subject, 'redirect_filters'), 10, 1, 0 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function adding_hooks_can_manage_options(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'current_user_can', array(
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

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => false
		) );

		$woocommerce_wpml->settings['set_up_wizard_run'] = false;
		\WP_Mock::expectActionAdded( 'admin_init', array( $subject, 'skip_setup'), 1 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function adding_hooks_setup_completed(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => 'manage_options',
			'return' => false
		) );

		$woocommerce_wpml->settings['set_up_wizard_run'] = true;
		$this->expectActionAdded( 'admin_init', array( $subject, 'skip_setup'), 11, 1, 0 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function setup_redirect_no_activation_redirect(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => false
		) );

		\WP_Mock::wpFunction( 'delete_transient', array(
			'times' => 0
		) );

		$subject->setup_redirect();
	}

	/**
	 * @test
	 */
	public function setup_redirect_yes_activation_redirect_do_not_redirect_to_setup(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::wpFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => array('install')
		) );

		// is_wcml_setup_page = true
		$_GET['page'] = 'wcml-setup';

		$this->assertNull( $subject->setup_redirect() );

	}

	/**
	 * @test
	 */
	public function setup_redirect_yes_activation_redirect_do_redirect_to_setup_Has_completed(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::wpFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => array()
		) );

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup' . rand_str();

		\WP_Mock::wpFunction( 'is_network_admin', array(
			'return' => false
		) );

		\WP_Mock::wpFunction( 'current_user_can', array(
			'return' => true
		) );

		// has_completed = yes
		$woocommerce_wpml->settings['set_up_wizard_run'] = true;

		\WP_Mock::wpFunction( 'wp_safe_redirect', array(
			'times' => 0
		) );
		\WP_Mock::wpFunction( 'wp_die', array(
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

		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => '_wcml_activation_redirect',
			'return' => true
		) );

		\WP_Mock::wpFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => [ 'woocommerce_admin_notices', [] ],
			'return' => array()
		) );

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup' . rand_str();

		\WP_Mock::wpFunction( 'is_network_admin', array(
			'return' => false
		) );

		\WP_Mock::wpFunction( 'current_user_can', array(
			'return' => true
		) );

		// has_completed = no
		$woocommerce_wpml->settings['set_up_wizard_run'] = false;

		\WP_Mock::wpFunction( 'wp_safe_redirect', array(
			'times' => 1
		) );
		\WP_Mock::wpFunction( 'wp_die', array(
			'times' => 1
		) );

		\WP_Mock::expectFilterAdded( 'wp_die_handler', array( $subject, 'exit_wrapper' ) );

		$subject->setup_redirect();
	}

	/**
	 * @test
	 */
	public function wizard_is_not_setup_page(){
		$subject = $this->get_subject();

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup' . rand_str();

		\WP_Mock::wpFunction( 'wp_die', array(
			'times' => 0
		) );

		$subject->wizard();
	}

	/**
	 * @test
	 */
	public function wizard_is_setup_page(){

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		// is_wcml_setup_page = false
		$_GET['page'] = 'wcml-setup';
		$woocommerce_wpml->settings['set_up_wizard_splash'] = true;

		if ( ! defined( 'WCML_VERSION' ) ) {
			define( 'WCML_VERSION', rand_str() );
		}

		\WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 1
		) );
		\WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'wp_die', array(
			'times' => 1
		) );

		$subject->wizard();
	}

	/**
	 * @test
	 */
	public function skip_setup_should_not_do_anything_without_get_params(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times' => 0
		) );
		\WP_Mock::wpFunction( 'remove_filter', array(
			'times' => 0
		) );
		\WP_Mock::wpFunction( 'delete_transient', array(
			'times' => 0
		) );

		$subject->skip_setup();
	}

	/**
	 * @test
	 */
	public function skip_setup_should_fail_on_invalid_nonce_or_lack_of_permissions(){
		$subject = $this->get_subject();

		$_GET['wcml-setup-skip'] = rand_str();
		$_GET['_wcml_setup_nonce'] = rand_str();

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times' => 1,
			'return' => false
		) );

		\WP_Mock::wpFunction( 'wp_die', array(
			'times' => 2
		) );

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args' => [ 'manage_options' ],
			'return' => false
		) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		$subject->skip_setup();
	}

	/**
	 * @test
	 */
	public function skip_setup_should_not_fail_on_valid_nonce_and_ok_permissions(){
		$subject = $this->get_subject();

		$_GET['wcml-setup-skip'] = rand_str();
		$_GET['_wcml_setup_nonce'] = rand_str();

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times' => 1,
			'return' => true
		) );

		\WP_Mock::wpFunction( 'wp_die', array(
			'times' => 0
		) );

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args' => [ 'manage_options' ],
			'return' => true
		) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'delete_transient', array(
			'args' => '_wcml_activation_redirect',
			'times' => 1
		) );

		$subject->skip_setup();
	}

	/**
	 * @test
	 */
	public function complete_setup() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );
		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' );
		$subject->complete_setup();
	}

	/**
	 * @test
	 */
	public function redirect_filters_without_next_step_url() {
		$subject = $this->get_subject();
		\WP_Mock::wpFunction( 'sanitize_text_field', array(
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

		\WP_Mock::wpFunction( 'sanitize_text_field', array(
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
	public function next_step_url(){
		$subject = $this->get_subject();

		$url = $subject->next_step_url();
		$this->assertTrue( is_string( $url ) );
	}

	/**
	 * @test
	 */
	public function handle_steps_invalid_nonce(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'wp_create_nonce', array(
			'return' => rand_str()
		) );

		\WP_Mock::wpFunction( 'sanitize_text_field', array(
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

		\WP_Mock::wpFunction( 'wp_create_nonce', array(
			'return' => $_POST['nonce']
		) );

		\WP_Mock::wpFunction( 'sanitize_text_field', array(
			'times' => 1,
			'args' => [ $_POST['handle_step'] ]
		) );

		$subject->handle_steps();

	}

}