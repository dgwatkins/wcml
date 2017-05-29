<?php

/**
 * @group refactoring
 */
class Test_WCML_Admin_Menu_Main extends OTGS_TestCase {
	/** @var WPML_Translation_Management */
	private $translation_management;

	/** @var WCML_Admin_Menu_Display */
	private $render_strategy;

	/** @var WPML_WP_API */
	private $wp_api;

	public function setUp() {
		parent::setUp();

		$this->translation_management = $this->getMockBuilder( 'WPML_Translation_Management' )->getMock();
		$this->render_strategy        = $this->createMock( 'WCML_Admin_Menu_Display' );
		$this->wp_api                 = $this->getMockBuilder( 'WPML_WP_API' )->setMethods( array(
			'current_user_can',
			'add_submenu_page',
		) )->getMock();

		\WP_Mock::wpPassthruFunction( '__' );
	}

	/**
	 * @test
	 */
	public function it_inits_hooks() {
		$subject = new WCML_Admin_Menu_Main( true, $this->translation_management, $this->render_strategy, $this->wp_api );

		\WP_Mock::expectActionAdded( 'admin_menu', array( $subject, 'register_menus' ), 80 );

		$subject->init_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_menu_page_when_dependencies_are_invalid_and_woocommerce_class_does_not_exist() {
		$subject = new WCML_Admin_Menu_Main( false, $this->translation_management, $this->render_strategy, $this->wp_api );

		\WP_Mock::wpFunction( 'add_menu_page', array(
			'times' => 1,
			'args'  => array(
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				array( $this->render_strategy, 'render' ),
				\Mockery::type( 'string' ),
			),
		) );

		$subject->register_menus();
	}

	/**
	 * @test
	 */
	public function it_adds_subpage_when_dependencies_are_fine() {
		\WP_Mock::wpFunction( 'current_user_can', array(
				'return' => true,
				'args'   => array( 'wpml_manage_woocommerce_multilingual' ),
			)
		);
		$subject = new WCML_Admin_Menu_Main( true, $this->translation_management, $this->render_strategy, $this->wp_api );

		\WP_Mock::wpFunction( 'add_submenu_page', array(
			'times' => 1,
			'args'  => array(
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				array( $this->render_strategy, 'render' ),
			),
		) );

		$subject->register_menus();
	}

	/**
	 * @test
	 */
	public function it_adds_subpage_when_woocommerce_class_exists() {
		\WP_Mock::wpFunction( 'current_user_can', array(
				'return' => true,
				'args'   => array( 'wpml_manage_woocommerce_multilingual' ),
			)
		);
		$subject = new WCML_Admin_Menu_Main( false, $this->translation_management, $this->render_strategy, $this->wp_api );

		$this->getMockBuilder( 'WooCommerce' )->getMock();

		\WP_Mock::wpFunction( 'add_submenu_page', array(
			'times' => 1,
			'args'  => array(
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				\Mockery::type( 'string' ),
				array( $this->render_strategy, 'render' ),
			),
		) );

		$subject->register_menus();
	}

	/**
	 * @test
	 */
	public function it_adds_another_subpage_when_a_user_can_translate() {
		$subject = new WCML_Admin_Menu_Main( true, $this->translation_management, $this->render_strategy, $this->wp_api );

		\WP_Mock::wpFunction( 'current_user_can', array(
				'return' => false,
				'args'   => array( 'wpml_manage_woocommerce_multilingual' ),
			)
		);
		\WP_Mock::wpFunction( 'current_user_can', array(
				'return' => true,
				'args'   => array( 'wpml_operate_woocommerce_multilingual' ),
			)
		);
		$this->wp_api->method( 'current_user_can' )->willReturn( false );

		$menu               = array();
		$menu['order']      = 400;
		$menu['page_title'] = 'Translations';
		$menu['menu_title'] = 'Translations';
		$menu['capability'] = 'wpml_operate_woocommerce_multilingual';
		$menu['menu_slug']  = WPML_TM_FOLDER . '/menu/translations-queue.php';
		$menu['function']   = array( $this->translation_management, 'translation_queue_page' );
		$menu['icon_url']   = ICL_PLUGIN_URL . '/res/img/icon16.png';

		\WP_Mock::expectAction( 'wpml_admin_menu_register_item', $menu );

		$subject->register_menus();
	}
}
