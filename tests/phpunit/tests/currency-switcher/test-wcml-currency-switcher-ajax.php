<?php

/**
 * Class Test_WCML_Currency_Switcher_Ajax
 * @group currency-switcher
 */
class Test_WCML_Currency_Switcher_Ajax extends OTGS_TestCase {

	function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 */
	public function wcml_currencies_order() {
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'update_settings' ) )->getMock();
		$_POST['wcml_nonce'] = 'test_nonce';
		$_POST['order'] = 'USD;EUR';
		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' )->with( array( 'currencies_order' => array( 'USD', 'EUR' ) ) )->willReturn( true );
		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST['wcml_nonce'], 'set_currencies_order_nonce' ),
			'return' => true,
			'times'  => 1,
		));
		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );
		\WP_Mock::wpPassthruFunction( 'esc_html__' );
		\WP_Mock::wpFunction( 'wp_send_json_success', array(
			'args'   => array( array( 'message' => 'Currencies order updated' ) ),
			'return' => true,
			'times'  => 1,
		));
		$subject = new WCML_Currency_Switcher_Ajax( $woocommerce_wpml );
		$subject->wcml_currencies_order();
	}

	/**
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function wcml_delete_currency_switcher() {
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'update_settings', 'get_settings' ) )->getMock();
		$woocommerce_wpml->multi_currency = new stdClass();
		$woocommerce_wpml->multi_currency->currency_switcher = new stdClass();
		$switcher_id = 'new_widget';
		$_POST['wcml_nonce'] = 'test_nonce';
		$_POST['switcher_id'] = $switcher_id;
		$_POST['widget_id'] = mt_rand( 1, 20 );
		$woocommerce_wpml->settings = array(
			'currency_switchers' => array(
				$_POST['widget_id'] => array(),
			),
		);
		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST['wcml_nonce'], 'delete_currency_switcher' ),
			'return' => true,
			'times'  => 1,
		));

		$wcml_settings = array(
			'currency_switchers' => array(
				$switcher_id => array(),
				'other_id' => array(),
			),
		);

		$sidebars = array(
			$switcher_id => array(
				'currency_sel_widget',
				'other_widget',
			),
			'other_id'  => array(
				'other_widget1',
				'other_widget2',
			),
		);

		\WP_Mock::wpFunction( 'wp_get_sidebars_widgets', array(
			'return' => $sidebars,
			'times'  => 1,
		));

		\WP_Mock::wpFunction( 'remove_action', array(
			'return' => $sidebars,
			'times'  => 1,
			'args'   => array( 'pre_update_option_sidebars_widgets', array( $woocommerce_wpml->multi_currency->currency_switcher, 'update_option_sidebars_widgets' ), 10, 2 ),
		));

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );
		\WP_Mock::wpPassthruFunction( 'esc_html__' );
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
		unset( $wcml_settings['currency_switchers'][ $switcher_id ] );
		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' )->with( $wcml_settings )->willReturn( true );

		unset( $sidebars[ $switcher_id ][0] );
		\WP_Mock::wpFunction( 'wp_set_sidebars_widgets', array(
			'args'   => array( $sidebars ),
			'return' => true,
		));

		\WP_Mock::wpFunction( 'wp_send_json_success', array(
			'return' => true,
			'times'  => 1,
		));

		$this->stubs->WP_Widget();
		$subject = new WCML_Currency_Switcher_Ajax( $woocommerce_wpml );
		$subject->wcml_delete_currency_switcher();
	}

	/**
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function wcml_currencies_switcher_save_settings() {
		$woocommerce_wpml                                    = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array(
			'update_settings'
		) )->getMock();
		$woocommerce_wpml->multi_currency                    = new stdClass();
		$woocommerce_wpml->multi_currency->currency_switcher = new stdClass();
		$switcher_id                                         = 'new_widget';
		$_POST['switcher_id']                                = $switcher_id;
		$_POST['widget_id']                                  = mt_rand( 1, 20 );
		$_POST['wcml_nonce']                                 = 'test_nonce';
		$_POST['template']                                   = 'widget_template';
		$_POST['widget_title']                               = 'widget_title';
		$_POST['switcher_style']                             = 'some_style';
		$_POST['color_scheme']                               = array(
			'id1' => '#000000',
			'id2' => '#000001',
			'id3' => '#000003',
		);
		$woocommerce_wpml->settings                          = array(
			'currency_switchers' => array(
				$_POST['widget_id'] => array(),
			),
		);
		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST['wcml_nonce'], 'wcml_currencies_switcher_save_settings' ),
			'return' => true,
			'times'  => 1,
		) );
		\WP_Mock::wpPassthruFunction( 'stripslashes_deep' );
		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );
		\WP_Mock::wpPassthruFunction( 'sanitize_hex_color' );
		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( 'widget_currency_sel_widget' ),
			'return' => array(),
			'times'  => 1,
		) );

		$widget_settings   = array();
		$widget_settings[] = array(
			'id'       => $_POST['widget_id'],
			'settings' => array(
				'widget_title'   => $_POST['widget_title'],
				'switcher_style' => $_POST['switcher_style'],
				'template'       => $_POST['template'],
				'color_scheme'   => $_POST['color_scheme'],
			),
		);
		\WP_Mock::wpFunction( 'update_option', array(
			'args'   => array( 'widget_currency_sel_widget', $widget_settings ),
			'return' => true,
			'times'  => 1,
		));
		$wcml_settings = array(
			'currency_switchers' => array(
				$_POST['widget_id'] => $widget_settings[0]['settings'],
			),
		);
		$woocommerce_wpml->expects( $this->once() )->method( 'update_settings' )->with( $wcml_settings )->willReturn( true );

		$sidebars = array(
			$switcher_id => array(
				'currency_sel_widget',
				'other_widget',
			),
			'other_id'  => array(
				'other_widget1',
				'other_widget2',
			),
		);

		\WP_Mock::wpFunction( 'wp_get_sidebars_widgets', array(
			'return' => $sidebars,
			'times'  => 1,
		));

		\WP_Mock::wpFunction( 'remove_action', array(
			'return' => $sidebars,
			'times'  => 1,
			'args'   => array( 'pre_update_option_sidebars_widgets', array( $woocommerce_wpml->multi_currency->currency_switcher, 'update_option_sidebars_widgets' ), 10, 2 ),
		));

		\WP_Mock::wpFunction( 'wp_set_sidebars_widgets', array(
			'args'   => array( $sidebars ),
			'return' => true,
		));

		\WP_Mock::wpFunction( 'wp_send_json_success', array(
			'return' => true,
			'times'  => 1,
		));

		$this->stubs->WP_Widget();
		$subject           = new WCML_Currency_Switcher_Ajax( $woocommerce_wpml );
		$subject->wcml_currencies_switcher_save_settings();
	}

	/**
	 * @test
	 */
	public function wcml_currencies_switcher_preview() {
		$_POST['switcher_id']    = 'switcher_id';
		$_POST['switcher_style'] = 'switcher_style';
		$_POST['color_scheme']   = 'color_scheme';
		$_POST['wcml_nonce']     = 'test_nonce';
		$_POST['template']       = 'Test template %name% (%symbol%) - %code%';
		$inline_style_handler    = 'inline-style-handler';
		$inline_css              = 'inline-css-goes-here';
		$switcher_preview        = 'switcher preview';
		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST['wcml_nonce'], 'wcml_currencies_switcher_preview' ),
			'return' => true,
			'times'  => 1,
		) );
		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );
		\WP_Mock::wpPassthruFunction( 'stripslashes_deep' );
		$switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Templates' )->disableOriginalConstructor()->setMethods( array(
			'has_styles',
			'get_inline_style_handler',
		) )->getMock();

		$switcher_template->expects( $this->once() )->method( 'has_styles' )->willReturn( true );
		$switcher_template->expects( $this->once() )->method( 'get_inline_style_handler' )->willReturn( $inline_style_handler );
		$woocommerce_wpml               = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->cs_templates = $this->getMockBuilder( 'WCML_Currency_Switcher_Templates' )->disableOriginalConstructor()->setMethods(
			array( 'get_color_picket_css', 'get_template' )
		)->getMock();
		$woocommerce_wpml->cs_templates->expects( $this->once() )->method( 'get_color_picket_css' )->with( $_POST['switcher_id'], array(
			'switcher_style' => $_POST['switcher_style'],
			'color_scheme'   => $_POST['color_scheme']
		) )->willReturn( $inline_css );
		$woocommerce_wpml->cs_templates->expects( $this->once() )->method( 'get_template' )->with( $_POST['switcher_style'] )->willReturn( $switcher_template );
		$woocommerce_wpml->multi_currency                    = new stdClass();
		$woocommerce_wpml->multi_currency->currency_switcher = $this->getMockBuilder( 'WCML_Currency_Switcher' )->disableOriginalConstructor()->setMethods(
			array( 'wcml_currency_switcher' )
		)->getMock();
		$woocommerce_wpml->multi_currency->currency_switcher->expects( $this->once() )->method( 'wcml_currency_switcher' )->with(
			array(
				'switcher_id'    => $_POST['switcher_id'],
				'format'         => $_POST['template'],
				'switcher_style' => $_POST['switcher_style'],
				'color_scheme'   => $_POST['color_scheme'],
				'preview'		 => true
			)
		)->will( $this->returnCallback( function () use ( $switcher_preview ) {
			echo $switcher_preview;
		} ) );

		$output = array(
			'inline_styles_id' => $inline_style_handler . '-inline-css',
			'inline_css'       => $inline_css,
			'preview'          => $switcher_preview,
		);

		\WP_Mock::wpFunction( 'wp_send_json_success', array(
			'return' => true,
			'args'   => array( $output ),
			'times'  => 1,
		) );
		$subject = new WCML_Currency_Switcher_Ajax( $woocommerce_wpml );
		$subject->wcml_currencies_switcher_preview();
	}
}
