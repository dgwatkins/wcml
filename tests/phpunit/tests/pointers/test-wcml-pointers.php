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
 * @author OnTheGo Systems
 * @group  pointers
 * @group  wcml-1986
 */
class Test_WCML_Pointers extends OTGS_TestCase {
	function tearDown() {
		unset( $_GET['tab'], $_GET['section'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	function it_add_hooks() {
		$subject = new WCML_Pointers();
		WP_Mock::expectActionAdded( 'admin_head', array( $subject, 'setup' ) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	function it_does_nothing_on_setup() {
		WP_Mock::wpFunction( 'get_current_screen', array( 'return' => null ) );
		WP_Mock::wpFunction( 'wp_register_style', array( 'times' => 0 ) );

		$subject = new WCML_Pointers();

		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_products_translation_link' ), 100, 1, 0 );
		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_shipping_classes_translation_link' ), 10, 1, 0 );
		$this->expectActionAdded( 'woocommerce_general_settings', array( $subject, 'add_multi_currency_link' ), 10, 1, 0 );
		$this->expectActionAdded( 'woocommerce_account_settings', array( $subject, 'add_endpoints_translation_link' ), 10, 1, 0 );

		$subject->setup();
	}

	/**
	 * @test
	 */
	function it_does_add_products_translation_link_action() {
		$current_screen     = new stdClass();
		$current_screen->id = 'edit-product';
		WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $current_screen ) );
		WP_Mock::wpFunction( 'wp_register_style', array( 'times' => 1, 'args' => array( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' ) ) );

		$subject = new WCML_Pointers();

		WP_Mock::expectActionAdded( 'admin_footer', array( $subject, 'add_products_translation_link' ), 100 );

		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_shipping_classes_translation_link' ), 10, 1, 0 );
		$this->expectActionAdded( 'woocommerce_general_settings', array( $subject, 'add_multi_currency_link' ), 10, 1, 0 );
		$this->expectActionAdded( 'woocommerce_account_settings', array( $subject, 'add_endpoints_translation_link' ), 10, 1, 0 );

		$subject->setup();
	}

	/**
	 * @test
	 */
	function it_does_add_shipping_classes_translation_link_action() {
		$current_screen     = new stdClass();
		$current_screen->id = 'woocommerce_page_wc-settings';
		WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $current_screen ) );
		WP_Mock::wpFunction( 'wp_register_style', array( 'times' => 1, 'args' => array( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' ) ) );

		$_GET['tab']     = 'shipping';
		$_GET['section'] = 'classes';

		$subject = new WCML_Pointers();

		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_products_translation_link' ), 100, 1, 0 );

		WP_Mock::expectActionAdded( 'admin_footer', array( $subject, 'add_shipping_classes_translation_link' ) );

		$this->expectActionAdded( 'woocommerce_general_settings', array( $subject, 'add_multi_currency_link' ), 10, 1, 0 );
		$this->expectActionAdded( 'woocommerce_account_settings', array( $subject, 'add_endpoints_translation_link' ), 10, 1, 0 );

		$subject->setup();
	}

	/**
	 * @test
	 */
	function it_does_add_multi_currency_link_in_general_tab() {
		$current_screen     = new stdClass();
		$current_screen->id = 'woocommerce_page_wc-settings';
		WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $current_screen ) );
		WP_Mock::wpFunction( 'wp_register_style', array( 'times' => 1, 'args' => array( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' ) ) );

		$_GET['tab'] = 'general';

		$subject = new WCML_Pointers();

		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_products_translation_link' ), 100, 1, 0 );
		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_shipping_classes_translation_link' ), 10, 1, 0 );

		WP_Mock::expectFilterAdded( 'woocommerce_general_settings', array( $subject, 'add_multi_currency_link' ) );

		$this->expectActionAdded( 'woocommerce_account_settings', array( $subject, 'add_endpoints_translation_link' ), 10, 1, 0 );

		$subject->setup();
	}

	/**
	 * @test
	 */
	function it_does_add_multi_currency_link_in_undefined_tab() {
		$current_screen     = new stdClass();
		$current_screen->id = 'woocommerce_page_wc-settings';
		WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $current_screen ) );
		WP_Mock::wpFunction( 'wp_register_style', array( 'times' => 1, 'args' => array( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' ) ) );

		$subject = new WCML_Pointers();

		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_products_translation_link' ), 100, 1, 0 );
		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_shipping_classes_translation_link' ), 10, 1, 0 );

		WP_Mock::expectFilterAdded( 'woocommerce_general_settings', array( $subject, 'add_multi_currency_link' ) );

		$this->expectActionAdded( 'woocommerce_account_settings', array( $subject, 'add_endpoints_translation_link' ), 10, 1, 0 );

		$subject->setup();
	}

	/**
	 * @test
	 */
	function it_does_add_endpoints_translation_link_in_account_tab() {
		$current_screen     = new stdClass();
		$current_screen->id = 'woocommerce_page_wc-settings';
		WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $current_screen ) );
		WP_Mock::wpFunction( 'wp_register_style', array( 'times' => 1, 'args' => array( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' ) ) );

		$_GET['tab'] = 'account';

		$subject = new WCML_Pointers();

		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_products_translation_link' ), 100, 1, 0 );
		$this->expectActionAdded( 'admin_footer', array( $subject, 'add_shipping_classes_translation_link' ), 10, 1, 0 );
		$this->expectActionAdded( 'woocommerce_general_settings', array( $subject, 'add_multi_currency_link' ), 10, 1, 0 );

		WP_Mock::expectFilterAdded( 'woocommerce_account_settings', array( $subject, 'add_endpoints_translation_link' ) );

		$subject->setup();
	}

	/**
	 * @test
	 * @dataProvider dp_links
	 *
	 * @param string $name
	 * @param string $admin_url
	 * @param string $callback
	 */
	function it_adds_link_with_jQuery( $name, $admin_url, $callback ) {
		$link = rand_str( 10 );

		WP_Mock::wpFunction( 'admin_url', array( 'times' => 1, 'return' => $link, 'args' => array( $admin_url ) ) );
		WP_Mock::wpFunction( '__', array( 'times' => 1, 'return' => $name, 'args' => array( $name, 'woocommerce-multilingual' ) ) );
		WP_Mock::wpFunction( 'wp_enqueue_style', array( 'times' => 1, 'args' => array( 'wcml-pointers' ) ) );

		$expected = $this->get_expected_script( $link, $name, $callback );

		$subject = new WCML_Pointers();

		ob_start();

		$subject->$callback();

		$actual = trim( preg_replace( '/\\s+/', ' ', ob_get_clean() ) );

		$this->assertSame( $expected, $actual );
	}

	function dp_links() {
		return array(
			array( 'Translate WooCommerce products', 'admin.php?page=wpml-wcml', 'add_products_translation_link' ),
			array( 'Translate shipping classes', 'admin.php?page=wpml-wcml&tab=product_shipping_class', 'add_shipping_classes_translation_link' ),
		);
	}

	private function get_expected_script( $link, $name, $callback ) {
		$scripts = array(
			'add_products_translation_link'         => '<script type="text/javascript"> jQuery(\'.subsubsub\').append(\'<a class="button button-small button-wpml wcml-pointer-products_translation" href="' . $link . '">' . $name . '</a>\'); </script>',
			'add_shipping_classes_translation_link' => '<script type="text/javascript"> jQuery(\'.wc-shipping-classes\').before(\'<a class="button button-small button-wpml wcml-pointer-shipping_classes_translation" href="' . $link . '">' . $name . '</a>\'); </script>',
		);

		return $scripts[ $callback ];
	}

	/**
	 * @test
	 * @dataProvider dp_descriptions
	 *
	 * @param string $name
	 * @param string $admin_url
	 * @param string $setting_id
	 * @param string $callback
	 */
	function it_does_not_add_the_multi_currency_link( $name, $admin_url, $setting_id, $callback ) {
		$link = rand_str( 10 );

		WP_Mock::wpFunction( 'admin_url', array( 'times' => 1, 'return' => $link, 'args' => array( $admin_url ) ) );
		WP_Mock::wpFunction( '__', array( 'times' => 1, 'return' => $name, 'args' => array( $name, 'woocommerce-multilingual' ) ) );
		WP_Mock::wpFunction( 'wp_enqueue_style', array( 'times' => 1, 'args' => array( 'wcml-pointers' ) ) );

		$settings = [
			[
				'id'   => 'id_1',
				'desc' => 'desc_1',
			],
			[
				'id'   => 'id_2',
				'desc' => 'desc_2',
			],
			[
				'id'   => 'id_3',
				'desc' => 'desc_3',
			],
		];

		$subject = new WCML_Pointers();

		$this->assertSame( $settings, $subject->$callback( $settings ) );
	}

	/**
	 * @test
	 * @dataProvider dp_descriptions
	 *
	 * @param string $name
	 * @param string $admin_url
	 * @param string $setting_id
	 * @param string $callback
	 */
	function it_adds_link_through_settings( $name, $admin_url, $setting_id, $callback ) {
		$link = rand_str( 10 );

		WP_Mock::wpFunction( 'admin_url', array( 'times' => 1, 'return' => $link, 'args' => array( $admin_url ) ) );
		WP_Mock::wpFunction( '__', array( 'times' => 1, 'return' => $name, 'args' => array( $name, 'woocommerce-multilingual' ) ) );
		WP_Mock::wpFunction( 'wp_enqueue_style', array( 'times' => 1, 'args' => array( 'wcml-pointers' ) ) );

		$tested_key         = rand_str( 5 );
		$tested_description = rand_str( 100 );
		$settings           = array(
			$tested_key => array(
				'id'   => $setting_id,
				'desc' => $tested_description,
			),
		);

		$expected                        = $settings;
		$expected[ $tested_key ]['desc'] = $this->get_description( $link, $name, $tested_description, $callback );

		$subject = new WCML_Pointers();

		$this->assertSame( $expected, $subject->$callback( $settings ) );
	}

	function dp_descriptions() {
		return array(
			array( 'Configure multi-currency for multilingual sites', 'admin.php?page=wpml-wcml&tab=multi-currency', 'pricing_options', 'add_multi_currency_link' ),
			array( 'Translate endpoints', 'admin.php?page=wpml-wcml&tab=slugs', 'account_endpoint_options', 'add_endpoints_translation_link' ),
		);
	}

	private function get_description( $link, $name, $current_description, $callback ) {
		$descriptions = array(
			'add_multi_currency_link'        => '<a class="button button-small button-wpml wcml-pointer-multi_currency" href="' . $link . '">' . $name . '</a><br />' . $current_description,
			'add_endpoints_translation_link' => '<a class="button button-small button-wpml wcml-pointer-endpoints_translation" href="' . $link . '">' . $name . '</a><br />' . $current_description,
		);

		return $descriptions[ $callback ];
	}
}
