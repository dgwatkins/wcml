<?php

/**
 * Class Test_WCML_Currency_Switcher_Widget
 * @group currency-switcher
 */
class Test_WCML_Currency_Switcher_Widget extends OTGS_TestCase {

	function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function widget() {
		\WP_Mock::wpPassthruFunction( '__' );
		$widget_args = array(
			'before_widget' => 'Text before widget',
			'after_widget'  => 'Text after widget',
			'before_title'  => 'Text before title',
			'after_title'   => 'Text after title',
			'id'            => mt_rand( 1, 10 ),
		);
		$widget_instance = array(
			'settings' => array(
				'widget_title' => 'Widget Title',
			),
		);
		$this->stubs->WP_Widget();
		$subject = new WCML_Currency_Switcher_Widget();
		ob_start();
		$subject->widget( $widget_args, $widget_instance );
		$output = ob_get_clean();
		$expected = $widget_args['before_widget'] . $widget_args['before_title'] . $widget_instance['settings']['widget_title'];
		$expected .= $widget_args['after_title'] . $widget_args['after_widget'];
		$this->assertEquals( $expected, $output );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function update() {
		$this->stubs->WP_Widget();
		$sidebar_settings = array( 'settings' => 'sidebar_settings' );
		$mock             = \Mockery::mock( 'alias:WCML_Currency_Switcher' );
		$mock->shouldReceive( 'get_settings' )->with( 'sidebar1' )->andReturn( $sidebar_settings );
		$_POST['sidebar'] = 'sidebar1';
		$expected_instance = array(
			'id'       => 'sidebar1',
			'settings' => $sidebar_settings,
		);
		\WP_Mock::wpPassthruFunction( '__' );
		$subject = new WCML_Currency_Switcher_Widget();
		$this->assertEquals( $expected_instance, $subject->update( false, false ) );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function form() {
		$instance = array( 'id' => mt_rand( 1, 20 ) );
		$admin_url = 'http://testsite.dev/wp-adminadmin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'];
		$text = 'Customize the currency switcher';
		$expected = sprintf( '<p><a class="button button-secondary wcml-cs-widgets-edit-link" href="%s"><span class="otgs-ico-edit"></span> %s</a></p>', $admin_url, $text );
		\WP_Mock::wpFunction( 'admin_url', array(
			'args'   => 'admin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'],
			'return' => 'http://testsite.dev/wp-adminadmin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'],
		));
		$this->stubs->WP_Widget();
		\WP_Mock::wpPassthruFunction( 'esc_html__' );
		\WP_Mock::wpPassthruFunction( 'esc_url' );
		\WP_Mock::wpPassthruFunction( '__' );
		$subject = new WCML_Currency_Switcher_Widget();
		ob_start();
		$subject->form( $instance );
		$output = ob_get_clean();
		$this->assertEquals( $expected, $output );
	}
}
