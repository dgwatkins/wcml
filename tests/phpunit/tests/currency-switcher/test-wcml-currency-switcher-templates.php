<?php

if ( ! class_exists( 'WPML_Templates_Factory' ) ) {
	abstract class WPML_Templates_Factory {

		protected $custom_filters;
		protected $custom_functions;

		/* @var WPML_WP_API $wp_api */
		private $wp_api;

		/**
		 * WPML_Templates_Factory constructor.
		 *
		 * @param array $custom_functions
		 * @param array $custom_filters
		 * @param WPML_WP_API $wp_api
		 */
		public function __construct( array $custom_functions = array(), array $custom_filters = array(), $wp_api = null ) {
			$this->init_template_base_dir();
			$this->custom_functions = $custom_functions;
			$this->custom_filters   = $custom_filters;

			if ( $wp_api ) {
				$this->wp_api = $wp_api;
			}
		}

		abstract protected function init_template_base_dir();
	}
}

/**
 * Class Test_WCML_Currency_Switcher_Templates
 * @group currency-switcher
 * @group fix-tests-on-windows
 */
class Test_WCML_Currency_Switcher_Templates extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WCML_File */
	private $wcml_file;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_wp_api' ) )
		                        ->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		$this->wcml_file = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );
	}

	public function get_subject( $woocommerce_wpml = false, $wcml_file = false ){

		if( !$woocommerce_wpml ) $woocommerce_wpml = $this->woocommerce_wpml;
		if( !$wcml_file ) $wcml_file = $this->wcml_file;

		return new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $this->sitepress->get_wp_api(), $wcml_file );
	}

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$enable_multi_currency = 1;
		$woocommerce_wpml      = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$wcml_settings         = array(
			'enable_multi_currency' => $enable_multi_currency
		);
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
		$this->wp_api->method( 'constant' )->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )->willReturn( $enable_multi_currency );

		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::expectActionAdded( 'after_setup_theme', array( $subject, 'after_setup_theme_action' ) );
		\WP_Mock::expectActionAdded( 'wp_enqueue_scripts', array( $subject, 'enqueue_template_resources' ) );
		\WP_Mock::expectActionAdded( 'admin_head', array( $subject, 'admin_enqueue_template_resources' ) );
		$subject->init_hooks();
	}

	/**
	 * @test
	 */
	public function get_template() {
		$wcml_currency_switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Template' )->disableOriginalConstructor()->getMock();

		$template_slug = 'dummy_template';
		$subject       = $this->get_subject();
		$subject->set_templates( array( $template_slug => $wcml_currency_switcher_template ) );

		$this->assertEquals( $wcml_currency_switcher_template, $subject->get_template( $template_slug ) );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function get_active_templates() {
		$sidebar = rand_str();
		$woocommerce_wpml                    = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();

		$currency_switchers                  = array(
			$sidebar => array(
				'switcher_style' => 'template1',
				'format'         => array(
					'template_options' => array(),
				),
			),
		);
		$template                            = array(
			'template1' => 'dummy_template_data',
		);
		$wcml_settings['currency_switchers'] = $currency_switchers;
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
		$woocommerce_wpml->cs_properties              = $this->getMockBuilder( 'WCML_Currency_Switcher_Properties' )->disableOriginalConstructor()->setMethods( array( 'is_currency_switcher_active' ) )->getMock();
		$woocommerce_wpml->cs_properties->expects( $this->once() )->method( 'is_currency_switcher_active' )->with( $sidebar, $wcml_settings )->willReturn( true );

		$subject = $this->get_subject( $woocommerce_wpml );
		$subject->set_templates( $template );
		$this->assertEquals( $template, $subject->get_active_templates() );

		$woocommerce_wpml                    = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$subject = $this->get_subject( $woocommerce_wpml );
		$template                            = array(
			'wcml-dropdown' => 'dummy_template_data',
		);
		$subject->set_templates( $template );
		$this->assertEquals( $template, $subject->get_active_templates( true ) );
	}

	/**
	 * @test
	 */
	public function it_falls_back_to_default_template() {
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$template         = array(
			'wcml-dropdown' => 'dummy_template_data',
		);
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( array( 'currency_switcher_product_visibility' => 1 ) );
		$subject = $this->get_subject( $woocommerce_wpml );
		$subject->set_templates( $template );
		$this->assertEquals( $template, $subject->get_active_templates( true ) );
	}

	/**
	 * @test
	 */
	public function get_templates() {
		$woocommerce_wpml                = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$wcml_currency_switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Template' )->disableOriginalConstructor()->setMethods( array( 'get_template_data' ) )->getMock();
		$core_template_data              = array(
			'is_core'            => true,
			'some_template_data' => array(),
		);
		$custom_template_data            = array(
			'is_core'            => false,
			'some_template_data' => array(),
		);
		$wcml_currency_switcher_template->method( 'get_template_data' )->willReturnOnConsecutiveCalls( $core_template_data, $custom_template_data );

		$templates = array(
			'template1' => $wcml_currency_switcher_template,
			'template2' => $wcml_currency_switcher_template,
		);

		$expected_templates                        = array();
		$expected_templates['core']['template1']   = $core_template_data;
		$expected_templates['custom']['template2'] = $custom_template_data;
		$subject                                   = $this->get_subject( $woocommerce_wpml );
		$subject->set_templates( $templates );
		$this->assertEquals( $expected_templates, $subject->get_templates() );
	}

	/**
	 * @test
	 */
	public function enqueue_template_resources() {
		if ( ! defined( 'WCML_VERSION' ) ) {
			define( 'WCML_VERSION', '0.0.0' );
		}

		$switcher_id = rand_str();
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$wcml_settings    = array(
			'currency_switcher_additional_css' => 'some_additional_css',
			'currency_switchers'               => array(
				$switcher_id => array(
					'switcher_style' => 'wcml-dropdown',
					'color_scheme'   => array(
						'border_normal'             => '1px solid red',
						'font_other_normal'         => 'red',
						'background_other_normal'   => 'red',
						'font_other_hover'          => 'red',
						'background_other_hover'    => 'red',
						'font_current_normal'       => 'red',
						'background_current_normal' => 'red',
						'font_current_hover'        => 'red',
						'background_current_hover'  => 'red',
					),
				),
			),
		);
		$woocommerce_wpml->method( 'get_settings' )->willReturn( $wcml_settings );
		$methods                         = array(
			'get_scripts',
			'get_styles',
			'has_styles',
			'get_inline_style_handler',
			'get_resource_handler',
		);
		$wcml_currency_switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Template' )->disableOriginalConstructor()->setMethods( $methods )->getMock();
		$wcml_currency_switcher_template->method( 'get_scripts' )->willReturn( array(
			'script1' => 'url/to/script1.js',
		) );
		$wcml_currency_switcher_template->method( 'get_styles' )->willReturn( array(
			'style1' => 'url/to/style1.css',
		) );
		$wcml_currency_switcher_template->method( 'get_resource_handler' )->willReturnMap(
			array(
				array( 'script1', 'script1_handler' ),
				array( 'style1', 'wp_enqueue_style' ),
			)
		);
		$wcml_currency_switcher_template->method( 'has_styles' )->willReturn( true );
		$wcml_currency_switcher_template->method( 'get_inline_style_handler' )->willReturn( 'inline_style_handler' );
		\WP_Mock::userFunction( 'wp_add_inline_style', array(
			'times' => 2,
		) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array(
			'args'   => array( 'script1_handler', 'url/to/script1.js', array(), WCML_VERSION, true ),
			'return' => true,
		) );
		\WP_Mock::userFunction( 'wp_enqueue_style', array(
			'args'   => array( 'wp_enqueue_style', 'url/to/style1.css', array(), WCML_VERSION ),
			'return' => true,
		) );
		\WP_Mock::passthruFunction( 'wp_strip_all_tags' );

		$woocommerce_wpml->cs_properties              = $this->getMockBuilder( 'WCML_Currency_Switcher_Properties' )->disableOriginalConstructor()->setMethods( array( 'is_currency_switcher_active' ) )->getMock();
		$woocommerce_wpml->cs_properties->method( 'is_currency_switcher_active' )->with( $switcher_id, $wcml_settings )->willReturn( true );

		$templates = array(
			'wcml-dropdown' => $wcml_currency_switcher_template,
		);
		$subject   = $this->get_subject( $woocommerce_wpml );
		$subject->set_templates( $templates );

		$this->expectOutputString('');
		$subject->enqueue_template_resources( );
	}

	/**
	 * @test
	 */
	public function enqueue_not_active_template_resources() {

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$wcml_settings    = array(
			'currency_switcher_additional_css' => 'some_additional_css',
			'currency_switchers'               => array(
				rand_str() => array(
					'switcher_style' => 'wcml-dropdown',
					'color_scheme'   => array(),
				),
			),
		);
		$woocommerce_wpml->method( 'get_settings' )->willReturn( $wcml_settings );

		$template_mock = $this->getMockBuilder( 'WCML_Currency_Switcher_Templates' )->disableOriginalConstructor()->setMethods( array( 'get_scripts', 'get_styles', 'has_styles' ) )->getMock();
		$template_mock->method( 'get_scripts' )->willReturn( array() );
		$template_mock->method( 'get_styles' )->willReturn( array() );
		$template_mock->method( 'has_styles' )->willReturn( false );

		$subject   = $this->get_subject( $woocommerce_wpml );

		$this->expectOutputString('<style type="text/css" id="wcml-cs-inline-styles-currency_switcher-additional_css"></style>'. PHP_EOL);
		$subject->enqueue_template_resources( array( rand_str() => $template_mock ) );
	}

	/**
	 * @test
	 */
	public function after_setup_theme_action() {
		if ( ! defined( 'WCML_PLUGIN_PATH' ) ) {
			define( 'WCML_PLUGIN_PATH', '../..' );
		}
		$wcml_file        = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->setMethods( array( 'fix_dir_separator', 'get_uri_from_path' ) )->getMock();
		$wcml_file->method( 'fix_dir_separator' )->will( $this->returnCallback( array( $this, 'fix_dir_separator' ) ) );
		$wcml_file->method( 'get_uri_from_path' )->will( $this->returnCallback( array( $this, 'get_uri_from_path' ) ) );
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();


		\WP_Mock::userFunction( 'get_template_directory', array(
			'return' => './',
		));

		\WP_Mock::userFunction( 'get_stylesheet_directory', array(
			'return' => './',
		));

		\WP_Mock::userFunction( 'wp_upload_dir', array(
			'args'   => array( null, false ),
			'return' => [ 'basedir' => './' ],
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'wcml_currency_switcher_template_objects' ),
			'times' => 1,
			'return' => false
		));

		\WP_Mock::userFunction( 'update_option', array(
			'times'   => 1,
			'return' => true
		));

		\WP_Mock::passthruFunction( 'sanitize_title_with_dashes' );

		$subject   = $this->get_subject( $woocommerce_wpml, $wcml_file );
		$subject->after_setup_theme_action();
		$expected_templates = array(
			'wcml-dropdown' => array(
				'name'    => 'Dropdown',
				'slug'    => 'wcml-dropdown',
				'is_core' => true,
			),
			'wcml-dropdown-click' => array(
				'name'    => 'Dropdown click',
				'slug'    => 'wcml-dropdown-click',
				'is_core' => true,
			),
			'wcml-horizontal-list' => array(
				'name'    => 'Horizontal List',
				'slug'    => 'wcml-horizontal-list',
				'is_core' => true,
			),
			'wcml-vertical-list' => array(
				'name'    => 'Vertical List',
				'slug'    => 'wcml-vertical-list',
				'is_core' => true,
			),
		);
		foreach ( $expected_templates as $expected_template_slug => $expected_template_data ) {
			$template = $subject->get_template( $expected_template_slug );
			$this->assertTrue( $template instanceof WCML_Currency_Switcher_Template );
			$template_data = $template->get_template_data();
			$this->assertEquals( $expected_template_data['name'], $template_data['name'] );
			$this->assertEquals( $expected_template_data['slug'], $template_data['slug'] );
			$this->assertEquals( $expected_template_data['is_core'], $template_data['is_core'] );
		}
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function check_is_active() {

		$wcml_currency_switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Template' )->disableOriginalConstructor()->getMock();
		$template_slug = 'dummy_template';

		$subject = $this->get_currency_switcher_templates_subject( $template_slug );
		$subject->set_templates( array( $template_slug => $wcml_currency_switcher_template ) );

		$this->assertTrue( $subject->check_is_active( $template_slug ) );
		$this->assertFalse( $subject->check_is_active( 'dummy_template_false' ) );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function get_first_active() {

		$wcml_currency_switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Template' )->disableOriginalConstructor()->getMock();
		$template_slug = 'dummy_template';

		$subject = $this->get_currency_switcher_templates_subject( $template_slug );
		$subject->set_templates(
			array(
				$template_slug => $wcml_currency_switcher_template,
				'dummy_template_false' => $wcml_currency_switcher_template
			)
		);

		$this->assertEquals( $template_slug, $subject->get_first_active( ) );
	}

	/**
	 * @test
	 */
	public function maybe_late_enqueue_template() {

		$slug = rand_str();

		$template_mock = $this->getMockBuilder( 'WCML_Currency_Switcher_Templates' )->disableOriginalConstructor()->setMethods( array( 'get_scripts', 'get_styles' ) )->getMock();
		$template_mock->expects( $this->once() )->method( 'get_scripts' )->willReturn( array() );
		$template_mock->expects( $this->once() )->method( 'get_styles' )->willReturn( array() );

		$subject   = $this->get_subject( );

		$subject->maybe_late_enqueue_template( $slug, $template_mock );
	}

	public function fix_dir_separator( $dir ) {
		return $dir;
	}

	public function get_uri_from_path( $path ) {
		return $path;
	}

	public function get_currency_switcher_templates_subject( $template_slug ) {

		$woocommerce_wpml                = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$sidebar = rand_str();

		$currency_switchers = array(
			$sidebar => array(
				'switcher_style' => $template_slug,
				'format'         => array(
					'template_options' => array(),
				),
			),
		);

		$wcml_settings['currency_switchers'] = $currency_switchers;

		$woocommerce_wpml->cs_properties              = $this->getMockBuilder( 'WCML_Currency_Switcher_Properties' )->disableOriginalConstructor()->setMethods( array( 'is_currency_switcher_active' ) )->getMock();
		$woocommerce_wpml->cs_properties->method( 'is_currency_switcher_active' )->with( $sidebar, $wcml_settings )->willReturn( true );

		$woocommerce_wpml->method( 'get_settings' )->willReturn( $wcml_settings );
		$subject = $this->get_subject( $woocommerce_wpml );

		return $subject;
	}
}
