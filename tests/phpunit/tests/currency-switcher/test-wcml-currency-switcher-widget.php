<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WCML_Currency_Switcher_Widget
 * @group currency-switcher
 */
class Test_WCML_Currency_Switcher_Widget extends OTGS_TestCase {

	const WIDGET_INSTANCE_ID = 'sidebar-1';

	/**
	 * @test
	 * @dataProvider dp_widget
	 * @group wcml-3670
	 *
	 * @param array $widget_args
	 * @param array $widget_instance
	 */
	public function widget( $widget_args, $widget_instance ) {
		$id                       = 'sidebar-1';
		$currency_switcher_output = 'The currency switcher output';

		\WP_Mock::onAction( 'wcml_currency_switcher' )
			->with( [ 'switcher_id' => $id ] )
			->perform( function () use ( $currency_switcher_output ) {
				echo $currency_switcher_output;
			} );

		$subject = new WCML_Currency_Switcher_Widget();
		ob_start();
		$subject->widget( $widget_args, $widget_instance );
		$output = ob_get_clean();
		$expected = $widget_args['before_widget'] . $widget_args['before_title'] . $widget_instance['settings']['widget_title'];
		$expected .= $widget_args['after_title'] . $currency_switcher_output . $widget_args['after_widget'];
		$this->assertEquals( $expected, $output );
	}

	public function dp_widget() {
		$args = [
			'before_widget' => 'Text before widget',
			'after_widget'  => 'Text after widget',
			'before_title'  => 'Text before title',
			'after_title'   => 'Text after title',
			'id'            => self::WIDGET_INSTANCE_ID,
		];
		$instance = [
			'settings' => [
				'widget_title' => 'Widget Title',
			],
		];

		$args_with_id       = $args;
		$args_with_id['id'] = self::WIDGET_INSTANCE_ID;

		$instance_with_id       = $instance;
		$instance_with_id['id'] = self::WIDGET_INSTANCE_ID;

		return [
			'ID from args'     => [ $args_with_id, $instance ],
			'ID from instance' => [ $args, $instance_with_id ],
		];
	}

	/**
	 * @test
	 */
	public function update() {
		$sidebar_settings  = [ 'settings' => 'sidebar_settings' ];
		$cs                = FunctionMocker::replace( 'WCML_Currency_Switcher::get_settings', $sidebar_settings );
		$_POST['sidebar']  = self::WIDGET_INSTANCE_ID;
		$expected_instance = [
			'id'       => self::WIDGET_INSTANCE_ID,
			'settings' => $sidebar_settings,
		];
		$subject = new WCML_Currency_Switcher_Widget();
		$this->assertEquals( $expected_instance, $subject->update( false, false ) );
		$cs->wasCalledWithOnce( [ self::WIDGET_INSTANCE_ID ] );
		unset( $_POST['sidebar'] );
	}

	/**
	 * @test
	 */
	public function form() {
		$instance = [ 'id' => mt_rand( 1, 20 ) ];
		$admin_url = 'http://testsite.dev/wp-adminadmin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'];
		$text = 'Customize the currency switcher';
		$expected = sprintf( '<p><a class="button button-secondary wcml-cs-widgets-edit-link" href="%s"><span class="otgs-ico-edit"></span> %s</a></p>', $admin_url, $text );
		\WP_Mock::userFunction( 'admin_url', [
			'args'   => 'admin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'],
			'return' => 'http://testsite.dev/wp-adminadmin.php?page=wpml-wcml&tab=multi-currency#currency-switcher/' . $instance['id'],
		] );
		$subject = new WCML_Currency_Switcher_Widget();
		ob_start();
		$subject->form( $instance );
		$output = ob_get_clean();
		$this->assertEquals( $expected, $output );
	}
}
