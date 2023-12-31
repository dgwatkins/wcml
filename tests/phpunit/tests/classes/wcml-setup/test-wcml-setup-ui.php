<?php

/**
 * Class Test_WCML_Setup_UI
 * @group wcml-1987
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 */
class Test_WCML_Setup_UI extends OTGS_TestCase {

	private function get_woocommerce_wpml_mock(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->setMethods( array() )
		            ->getMock();
	}

	private function get_subject(){
		return new WCML_Setup_UI( $this->get_woocommerce_wpml_mock() );
	}

	/**
	 * @test
	 */
	public function add_hooks_no_privileges(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args' => [ 'manage_options' ],
			'return' => false
		) );

		$_GET['page'] = 'wcml-setup';
		$this->expectActionAdded( 'admin_menu', array( $this, 'admin_menus'), 10, 1, 0 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_not_setup_page(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args' => [ 'manage_options' ],
			'return' => true
		) );

		$_GET['page'] = 'wcml-setup' . rand_str();
		$this->expectActionAdded( 'admin_menu', array( $this, 'admin_menus'), 10, 1, 0 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_with_privileges_on_setup_page(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args' => [ 'manage_options' ],
			'return' => true
		) );

		$_GET['page'] = 'wcml-setup';

		\WP_Mock::expectActionAdded( 'admin_menu', array( $subject, 'admin_menus' ) );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function admin_menus(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'add_dashboard_page', array(
			'args' => [ '', '', 'manage_options', 'wcml-setup', '' ],
			'times' => 1
		) );

		$subject->admin_menus();
	}

	/**
	 * @test
	 */
	public function setup_header(){
		$subject = $this->get_subject();

		$step_keys = [
			rand_str(),
			rand_str(),
			rand_str()
		];

		$steps = [
			$step_keys[0] => [],
			$step_keys[1] => [],
			$step_keys[2] => [],
		];

		\WP_Mock::wpFunction( 'set_current_screen', array(
			'args' => [ 'wcml-setup' ],
			'times' => 1
		) );

		$header_ui_stub  = Mockery::mock( 'overload:WCML_Setup_Header_UI' );
		$header_ui_stub->shouldReceive( 'get_view' );

		$subject->setup_header( $steps, $step_keys[1] );
	}

	/**
	 * @test
	 */
	public function setup_steps(){
		$subject = $this->get_subject();

		$step_keys = [ rand_str(), rand_str(), rand_str() ];

		$current_step = $step_keys[1];

		$steps = [
			$step_keys[0] => [ 'name' => rand_str() ],
			$step_keys[1] => [ 'name' => rand_str() ],
			$step_keys[2] => [ 'name' => rand_str() ],
		];

		\WP_Mock::wpFunction( 'esc_html', array(
			'times' => count( $steps ) - 1
		) );

		ob_start();
		$subject->setup_steps( $steps, $current_step );
		ob_end_clean();
	}

	/**
	 * @test
	 */
	public function setup_content(){
		$subject = $this->get_subject();

		$ui_stub  = Mockery::mock( 'overload:WCML_Templates_Factory' );
		$ui_stub->shouldReceive( 'get_view' );

		ob_start();
		$subject->setup_content( $ui_stub );
		ob_end_clean();
	}

	/**
	 * @test
	 */
	public function setup_footer(){
		$subject = $this->get_subject();

		$has_handler = rand_str();

		$footer_ui_stub  = Mockery::mock( 'overload:WCML_Setup_Footer_UI' );
		$footer_ui_stub->shouldReceive( 'get_view' );

		$subject->setup_footer( $has_handler );

	}

	/**
	 * @test
	 */
	public function wizard_notice(){
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 1
		) );

		$notice_ui_stub  = Mockery::mock( 'overload:WCML_Setup_Notice_UI' );
		$notice_ui_stub->shouldReceive( 'get_view' ) ;

		$subject->wizard_notice();
	}

	/**
	 * @test
	 * @group wizard_notice
	 *
	 * @param string $test_pagenow
	 *
	 * @dataProvider dp_pagenow_for_wizard_notice
	 */
	public function it_should_add_wizard_notice_hook_on_wp_dashboard_or_plugin_page( $test_pagenow ){
		global $pagenow;
		$pagenow_buff = $pagenow;
		$pagenow      = $test_pagenow;
		
		
		$subject = $this->get_subject();
		
		\WP_Mock::expectFilterAdded( 'admin_notices', array( $subject, 'wizard_notice') );
		
		$subject->add_wizard_notice_hook();
		
		$pagenow = $pagenow_buff;
		
	}
	
	/**
	 * Data Provider for test it_should_add_wizard_notice_hook_on_wp_dashboard_or_plugin_page.
	 */
	public function dp_pagenow_for_wizard_notice() {
		return array(
			'dashboard' => array( 'index.php' ),
			'plugins'   => array( 'plugins.php' ),
		);
	}
	
	/**
	 * @test
	 * @group wizard_notice
	 */
	public function it_should_NOT_add_wizard_notice_hook_on_wcml_dashboard_page(){

		$_GET['page'] = 'wpml-wcml';

		$subject = $this->get_subject();

		\WP_Mock::expectFilterNotAdded( 'admin_notices', array( $subject, 'wizard_notice') );

		$subject->add_wizard_notice_hook();

		unset( $_GET['page'] );
	}

	/**
	 * @test
	 * @group wizard_notice
	 */
	public function it_should_not_add_wizard_notice_hook_on_not_wp_dashboard_and_wcml_dashboard_admin_pages(){

		$subject = $this->get_subject();

		\WP_Mock::expectFilterNotAdded( 'admin_notices', array( $subject, 'wizard_notice') );

		$subject->add_wizard_notice_hook();
	}

}