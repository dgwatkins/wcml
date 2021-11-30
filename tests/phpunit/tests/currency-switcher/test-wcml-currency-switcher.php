<?php

use WCML\MultiCurrency\Geolocation;
use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WCML_Currency_Switcher
 * @group currency-switcher
 */
class Test_WCML_Currency_Switcher extends OTGS_TestCase {

	function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 */
	public function it_init_function() {
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress        = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();
		$subject          = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		\WP_Mock::userFunction( 'add_shortcode', array(
			'times' => 1,
			'args'  => array( 'currency_switcher', array( $subject, 'currency_switcher_shortcode' ) ),
		) );
		\WP_Mock::expectActionAdded( 'wcml_currency_switcher', array( $subject, 'wcml_currency_switcher' ) );
		\WP_Mock::expectActionAdded( 'currency_switcher', array( $subject, 'currency_switcher' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_product_meta_start', array( $subject, 'show_currency_switcher' ) );
		\WP_Mock::expectActionAdded( 'pre_update_option_sidebars_widgets', array(
			$subject,
			'update_option_sidebars_widgets'
		), 10, 2 );
		$subject->on_init();
	}

	/**
	 * @test
	 */
	public function it_returns_settings() {
		global $woocommerce_wpml;
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$switcher_id      = mt_rand( 1, 20 );
		$switcher_data    = array(
			'dummy_data' => 'dummy_value',
		);
		$wcml_settings    = array(
			'currency_switchers' => array(
				$switcher_id => $switcher_data,
			),
		);
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress        = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();

		$subject = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$this->assertEquals( $switcher_data, $subject::get_settings( $switcher_id ) );
	}

	/**
	 * @test
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_gets_model_data() {
		$currencies      = array( 'EUR', 'USD', 'JPY' );
		$random_currency = array_rand( $currencies, 1 );
		$multi_currency  = $this->getMockBuilder( 'WCML_Multi_Currency' )->disableOriginalConstructor()->setMethods( array( 'get_client_currency' ) )->getMock();
		$multi_currency->expects( $this->once() )->method( 'get_client_currency' )->willReturn( $random_currency );
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml                 = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$woocommerce_wpml->multi_currency = $multi_currency;
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress                        = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->setMethods( array(
			'is_rtl',
			'get_current_language',
		) )->getMock();
		$args                             = array(
			'switcher_style' => 'random_style',
			'format'            => '%name% (%symbol%) - %code%',
			'switcher_id'    => mt_rand( 1, 10 ),
		);
		$mock_hard                        = \Mockery::mock( 'overload:WPML_Mobile_Detect' );
		$mock_hard->shouldReceive( 'isMobile' )->once()->andReturn( false );
		$mock_hard->shouldReceive( 'isTablet' )->once()->andReturn( true );

		$expected_model = array(
			'css_classes'       => 'random_style ' . $args['switcher_id'] . ' wcml_currency_switcher wcml-cs-touch-device',
			'format'            => $args[ 'format' ],
			'currencies'        => $currencies,
			'selected_currency' => $random_currency,
		);
		$subject        = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$model          = $subject->get_model_data( $args, $currencies );
		$this->assertEquals( $expected_model, $model );
	}

	/**
	 * @test
	 */
	public function it_returns_registered_sidebars() {
		global $wp_registered_sidebars;
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml       = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress              = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();
		$subject                = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$wp_registered_sidebars = array(
			'sidebar1',
			'sidebar2',
			'sidebar3',
		);
		$this->assertEquals( $wp_registered_sidebars, $subject->get_registered_sidebars() );
	}

	/**
	 * @test
	 */
	public function get_available_sidebars() {
		global $wp_registered_sidebars;
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml     = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress            = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();
		$subject              = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$currency_switcher_id = mt_rand( 1, 100 );
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( array(
			'currency_switchers' => array(
				$currency_switcher_id => 'dummy_data',
			),
		) );
		$wp_registered_sidebars = array(
			'sidebar2' => array(
				'id' => $currency_switcher_id,
			),
			'sidebar3' => array(
				'id' => mt_rand( 101, 200 ),
			),
			'sidebar1' => array(
				'id' => mt_rand( 201, 300 ),
			),
		);

		$expected_sidebars = $wp_registered_sidebars;
		unset( $expected_sidebars['sidebar2'] );
		$this->assertEquals( $expected_sidebars, $subject->get_available_sidebars() );
	}

	/**
	 * @test
	 */
	public function it_removes_currency_switcher_and_updates_options() {
		$sidebars         = array(
			'sidebar1'            => array(),
			'wp_inactive_widgets' => array(),
			'sidebar2'           => array(),
		);
		$wcml_settings    = array(
			'currency_switchers' => array(
				'sidebar2' => array(),
			),
		);
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array(
			'get_settings',
			'update_settings'
		) )->getMock();
		$woocommerce_wpml->expects( $this->exactly( count( $sidebars ) - 1 ) )->method( 'get_settings' )->willReturn( $wcml_settings );
		$woocommerce_wpml->expects( $this->exactly( count( $sidebars ) - 1 ) )->method( 'update_settings' )->willReturnMap( array(
			array( $wcml_settings, true ),
			array( array( 'currency_switchers' => array() ), true ),
		) );

		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress        = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();

		$subject = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$subject->update_option_sidebars_widgets( $sidebars, array() );
	}

	/**
	 * @test
	 */
	public function it_update_currency_switcher_with_default_options() {
		$sidebars         = array(
			'sidebar1'            => array(),
			'wp_inactive_widgets' => array(),
			'sidebar2'           => array(
				'currency_sel_widget',
				'currency_sel_widget',
			),
		);
		$wcml_settings    = array(
			'currency_switchers' => array(
				'sidebar2' => array(),
			),
		);
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array(
			'get_settings',
			'update_settings',
		) )->getMock();
		$woocommerce_wpml->expects( $this->exactly( count( $sidebars ) - 1 ) )->method( 'get_settings' )->willReturn( $wcml_settings );
		$woocommerce_wpml->expects( $this->exactly( count( $sidebars ) - 1 ) )->method( 'update_settings' )->willReturnMap( array(
			array( $wcml_settings, true ),
			array(
				array(
					'currency_switchers' => array(
						'switcher_style' => 'wcml-dropdown',
						'widget_title'   => '',
						'template'       => '%name% (%symbol%) - %code%',
						'color_scheme'   => array(
							'font_current_normal'       => '',
							'font_current_hover'        => '',
							'background_current_normal' => '',
							'background_current_hover'  => '',
							'font_other_normal'         => '',
							'font_other_hover'          => '',
							'background_other_normal'   => '',
							'background_other_hover'    => '',
							'border_normal'             => '',
						),
					),
				),
				true,
			),
		) );

		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress        = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();

		\Mockery::mock( 'WP_Widget' );
		$subject = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$subject->update_option_sidebars_widgets( $sidebars, array() );
	}

	/**
	 * @test
	 * @dataProvider currency_switcher_shortcode_data
	 */
	public function currency_switcher_shortcode( $switcher_id, $currency_mode ) {
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$shortcode_attrs = array();
		if ( $switcher_id ) {
			$shortcode_attrs['switcher_id'] = $switcher_id;
		}
		\WP_Mock::userFunction( 'shortcode_atts', array(
			'return' => $shortcode_attrs,
			'args'   => array( array(), $shortcode_attrs ),
		) );
		\WP_Mock::userFunction( 'wc_get_page_id', array(
			'return' => 'requested_page_id',
		) );
		\WP_Mock::userFunction( 'is_page', array(
			'return' => false,
			'args'   => array( 'requested_page_id' ),
		) );
		\WP_Mock::userFunction( 'is_product', array(
			'return' => false,
		) );
		\WP_Mock::userFunction( 'is_admin', array(
			'return' => false,
		) );
		\WP_Mock::userFunction( 'get_current_user_id', array(
			'return' => false,
		) );

		$switcher_id = array_key_exists( 'switcher_id', $shortcode_attrs ) ? $shortcode_attrs['switcher_id'] : 'product';
		$wcml_settings = array(
			'currency_switchers' => array(
				$switcher_id => array(
					'switcher_style' => 'wcml-horizontal-list',
					'template'       => 'Testing Switcher - %name% (%symbol%) - %code%',
					'color_scheme'   => 'gray',
				),
			),
			'currency_options'   => [
				'EUR' => [ 'location_mode' => 'all' ],
				'USD' => [ 'location_mode' => 'all' ],
				'JPY' => [ 'location_mode' => 'include', 'countries' => ['JP'] ],
			],
			'currency_mode'      => $currency_mode,
		);
		$currencies    = array( 'EUR', 'USD', 'JPY' );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCountryByUserIp', 'UA' );

		foreach ( $currencies as $currency ) {
			$wcml_settings['currency_options'][ $currency ]['languages']['en'] = ( 'JPY' !== $currency ) ? 1 : 0;
		}
		$multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )->disableOriginalConstructor()->setMethods( array( 'get_currency_codes' ) )->getMock();
		$multi_currency->expects( $this->once() )->method( 'get_currency_codes' )->willReturn( $currencies );

		$switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Templates' )->disableOriginalConstructor()->setMethods( array(
			'set_model',
			'get_view',
		) )->getMock();
		$switcher_template->method( 'set_model' )->with( 'MODEL_DATA' )->willReturn( false );
		$switcher_template->method( 'get_view' )->willReturn( 'SHORTCODE_VIEW' );

		$shortcode_template = $this->getMockBuilder( 'WCML_CS_Templates' )->disableOriginalConstructor()->setMethods( array( 'get_template', 'maybe_late_enqueue_template' ) )->getMock();
		$shortcode_template->method( 'get_template' )->with( 'wcml-horizontal-list' )->willReturn( $switcher_template );
		$shortcode_template->method( 'maybe_late_enqueue_template' )->willReturn( true );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings', 'get_setting' ) )->getMock();
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
		$woocommerce_wpml->expects( $this->once() )->method( 'get_setting' )->willReturn( $wcml_settings['currency_mode'] );
		$woocommerce_wpml->multi_currency = $multi_currency;
		$woocommerce_wpml->cs_templates   = $shortcode_template;

		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->setMethods( array( 'get_current_language' ) )->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( 'en' );

		$subject = \Mockery::mock( 'WCML_Currency_Switcher[get_model_data]', array( $woocommerce_wpml, $sitepress ) );
		$subject->shouldReceive( 'get_model_data' )->with( array(
			'switcher_id'    => $switcher_id,
			'switcher_style' => 'wcml-horizontal-list',
			'format'         => 'Testing Switcher - %name% (%symbol%) - %code%',
			'color_scheme'   => 'gray',
		), array( 'EUR', 'USD' ) )->andReturn( 'MODEL_DATA' );

		$this->assertEquals( 'SHORTCODE_VIEW', $subject->currency_switcher_shortcode( $shortcode_attrs ) );
	}

	public function currency_switcher_shortcode_data() {
		return array(
			array( mt_rand( 1, 100 ), Geolocation::MODE_BY_LANGUAGE ),
			array( mt_rand( 1, 100 ), Geolocation::MODE_BY_LOCATION ),
			array( false, Geolocation::MODE_BY_LANGUAGE ),
		);
	}

	/**
	 * @test
	 */
	public function it_calls_currency_switcher_shortcode() {
		$currency_switcher_output = 'short-code-output';
		$wcml_settings = array(
			'currency_switcher_product_visibility' => 1,
		);

		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml                 = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress        = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();

		$subject = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		\WP_Mock::userFunction( 'is_product', array(
			'return'  => true,
			'times'   => 1,
		));
		\WP_Mock::userFunction( 'do_shortcode', array(
			'return' => $currency_switcher_output,
		));
		ob_start();
		$subject->show_currency_switcher();
		$output = ob_get_clean();

		$this->assertEquals( $currency_switcher_output, $output );
	}


	/**
	 * @test
	 */
	public function check_and_convert_switcher_style() {

		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $woocommerce_wpml */
		$woocommerce_wpml     = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress            = $this->getMockBuilder( \WPML\Core\ISitePress::class )->disableOriginalConstructor()->getMock();

		$subject              = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );

		$initial_args = array(
			'switcher_style' => 'list',
			'orientation' => 'horizontal'
		);

		$expected_args = array(
			'switcher_style' => 'wcml-horizontal-list'
		);

		$this->assertEquals( $expected_args, $subject->check_and_convert_switcher_style( $initial_args ) );

		$initial_args = array(
			'switcher_style' => 'list',
			'orientation' => 'vertical'
		);

		$expected_args = array(
			'switcher_style' => 'wcml-vertical-list'
		);

		$this->assertEquals( $expected_args, $subject->check_and_convert_switcher_style( $initial_args ) );

		$initial_args = array(
			'switcher_style' => 'dropdown'
		);

		$expected_args = array(
			'switcher_style' => 'wcml-dropdown'
		);

		$this->assertEquals( $expected_args, $subject->check_and_convert_switcher_style( $initial_args ) );
	}
}
