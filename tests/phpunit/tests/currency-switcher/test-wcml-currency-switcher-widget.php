<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WCML_Currency_Switcher_Widget
 * @group currency-switcher
 */
class Test_WCML_Currency_Switcher_Widget extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function widget() {
		\WP_Mock::passthruFunction( '__' );
		$widget_args = [
			'before_widget' => 'Text before widget',
			'after_widget'  => 'Text after widget',
			'before_title'  => 'Text before title',
			'after_title'   => 'Text after title',
			'id'            => 123,
		];
		$widget_instance = [
			'settings' => [
				'widget_title' => 'Widget Title',
			],
		];
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
	 */
	public function update() {
		$sidebar_settings = [ 'settings' => 'sidebar_settings' ];

		$csGetSettings = FunctionMocker::replace( WCML_Currency_Switcher::class . '::get_settings', $sidebar_settings );

		$_POST['sidebar'] = 'sidebar1';
		$expected_instance = [
			'id'       => 'sidebar1',
			'settings' => $sidebar_settings,
		];
		\WP_Mock::passthruFunction( '__' );
		$subject = new WCML_Currency_Switcher_Widget();
		$this->assertEquals( $expected_instance, $subject->update( false, false ) );

		$csGetSettings->wasCalledWithOnce( [ 'sidebar1' ] );
	}

	/**
	 * @test
	 */
	public function form() {
		$instance = [ 'id' => 123 ];
		$admin_url = 'http://testsite.dev/wp-adminadmin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'];
		$text = 'Customize the currency switcher';
		$expected = sprintf( '<p><a class="button button-secondary wcml-cs-widgets-edit-link" href="%s"><span class="otgs-ico-edit"></span> %s</a></p>', $admin_url, $text );
		\WP_Mock::userFunction( 'admin_url', [
			'args'   => 'admin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'],
			'return' => 'http://testsite.dev/wp-adminadmin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'],
		] );
		\WP_Mock::passthruFunction( 'esc_html__' );
		\WP_Mock::passthruFunction( 'esc_url' );
		\WP_Mock::passthruFunction( '__' );
		$subject = new WCML_Currency_Switcher_Widget();
		ob_start();
		$subject->form( $instance );
		$output = ob_get_clean();
		$this->assertEquals( $expected, $output );
	}
}
