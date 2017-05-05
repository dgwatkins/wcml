<?php

if ( ! class_exists( 'WPML_Templates_Factory' ) ) {
	abstract class WPML_Templates_Factory {

		private $custom_filters;
		private $custom_functions;

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
 */
class Test_WCML_Currency_Switcher_Templates extends OTGS_TestCase {

	function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$wpml_file        = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
		$subject          = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );

		\WP_Mock::expectActionAdded( 'after_setup_theme', array( $subject, 'after_setup_theme_action' ) );
		\WP_Mock::expectActionAdded( 'wp_enqueue_scripts', array( $subject, 'enqueue_template_resources' ) );
		\WP_Mock::expectActionAdded( 'admin_head', array( $subject, 'admin_enqueue_template_resources' ) );
		$subject->init_hooks();
	}

	/**
	 * @test
	 */
	public function get_template() {
		$woocommerce_wpml                = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$wpml_file                       = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
		$wcml_currency_switcher_template = $this->getMockBuilder( 'WCML_Currency_Switcher_Template' )->disableOriginalConstructor()->getMock();

		$template_slug = 'dummy_template';
		$subject       = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
		$subject->set_templates( array( $template_slug => $wcml_currency_switcher_template ) );

		$this->assertEquals( $wcml_currency_switcher_template, $subject->get_template( $template_slug ) );
	}

	/**
	 * @test
	 */
	public function get_active_templates() {
		$wpml_file                           = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml                    = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$currency_switchers                  = array(
			array(
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
		$subject = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
		$subject->set_templates( $template );
		$this->assertEquals( $template, $subject->get_active_templates() );

		$wpml_file                           = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml                    = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$subject = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
		$template                            = array(
			'wcml-dropdown' => 'dummy_template_data',
		);
		$subject->set_templates( $template );
		$this->assertEquals( $template, $subject->get_active_templates() );
	}

	/**
	 * @test
	 */
	public function it_falls_back_to_default_template() {
		$wpml_file        = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$template         = array(
			'wcml-dropdown' => 'dummy_template_data',
		);
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( array( 'currency_switcher_product_visibility' => 1 ) );
		$subject = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
		$subject->set_templates( $template );
		$this->assertEquals( $template, $subject->get_active_templates() );
	}

	/**
	 * @test
	 */
	public function get_templates() {
		$woocommerce_wpml                = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$wpml_file                       = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
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
		$subject                                   = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
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
		$wpml_file        = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods( array( 'get_settings' ) )->getMock();
		$wcml_settings    = array(
			'currency_switcher_additional_css' => 'some_additional_css',
			'currency_switchers'               => array(
				'switcher1' => array(
					'switcher_style' => 'template1',
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
		$woocommerce_wpml->expects( $this->once() )->method( 'get_settings' )->willReturn( $wcml_settings );
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
		\WP_Mock::wpFunction( 'wp_add_inline_style', array(
			'times' => 2,
		) );
		\WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'args'   => array( 'script1_handler', 'url/to/script1.js', array(), WCML_VERSION ),
			'return' => true,
		) );
		\WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'args'   => array( 'wp_enqueue_style', 'url/to/style1.css', array(), WCML_VERSION ),
			'return' => true,
		) );
		\WP_Mock::wpPassthruFunction( 'wp_strip_all_tags' );

		$templates = array(
			'template1' => $wcml_currency_switcher_template,
		);
		$subject   = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
		$subject->enqueue_template_resources( $templates );
	}

	/**
	 * @test
	 */
	public function after_setup_theme_action() {
		if ( ! defined( 'WCML_PLUGIN_PATH' ) ) {
			define( 'WCML_PLUGIN_PATH', '../..' );
		}
		$wpml_file        = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->setMethods( array( 'fix_dir_separator', 'get_uri_from_path' ) )->getMock();
		$wpml_file->method( 'fix_dir_separator' )->will( $this->returnCallback( array( $this, 'fix_dir_separator' ) ) );
		$wpml_file->method( 'get_uri_from_path' )->will( $this->returnCallback( array( $this, 'get_uri_from_path' ) ) );
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();


		\WP_Mock::wpFunction( 'get_template_directory', array(
			'returns' => './',
		));

		\WP_Mock::wpFunction( 'get_stylesheet_directory', array(
			'returns' => './',
		));

		\WP_Mock::wpFunction( 'wp_upload_dir', array(
			'args'   => array( null, false ),
			'returns' => './',
		));

		\WP_Mock::wpPassthruFunction( 'sanitize_title_with_dashes' );

		$subject   = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );
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

	public function fix_dir_separator( $dir ) {
		return $dir;
	}

	public function get_uri_from_path( $path ) {
		return $path;
	}

	public function get_currency_switcher_templates_subject( $template_slug ) {

		$woocommerce_wpml                = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$wpml_file                       = $this->getMockBuilder( 'WCML_File' )->disableOriginalConstructor()->getMock();

		$currency_switchers = array(
			array(
				'switcher_style' => $template_slug,
				'format'         => array(
					'template_options' => array(),
				),
			),
		);

		$wcml_settings['currency_switchers'] = $currency_switchers;
		$woocommerce_wpml->method( 'get_settings' )->willReturn( $wcml_settings );
		$subject = new WCML_Currency_Switcher_Templates( $woocommerce_wpml, $wpml_file );

		return $subject;
	}
}
