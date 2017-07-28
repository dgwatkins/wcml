<?php

class Test_WCML_Cart_Sync_Warnings extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp() {
		parent::setUp();

		\WP_Mock::wpPassthruFunction( '__' );
		\WP_Mock::wpPassthruFunction( 'esc_html_x' );
		\WP_Mock::wpPassthruFunction( 'esc_html__' );
		\WP_Mock::wpPassthruFunction( 'esc_url' );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );
	}

	public function get_subject(){

		return new WCML_Cart_Sync_Warnings( $this->woocommerce_wpml, $this->sitepress );
	}

	/**
	 * @test
	 */
	public function it_does_not_adds_hooks_when_notices_are_not_needed(){
		$subject = $this->get_subject();
		WP_Mock::expectActionNotAdded( 'admin_notices', array( $subject, 'show_cart_notice' ), 10, 1 );
		WP_Mock::expectActionNotAdded( 'admin_enqueue_scripts', array( $subject, 'register_styles' ), 10, 1 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_hooks_when_notices_are_needed(){
		$cart_sync_constant = 1;
		$cart_clear_constant = 0;
		$this->wp_api->method( 'constant' )->with( 'WCML_CART_SYNC' )->willReturn( $cart_sync_constant );

		$this->getMockBuilder( 'WC_Bookings' )
			->disableOriginalConstructor()
			->getMock();


		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $cart_clear_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $cart_sync_constant;
		$this->woocommerce_wpml->settings[ 'dismiss_cart_warning' ] = 0;

		$subject = $this->get_subject();

		WP_Mock::expectActionAdded( 'admin_notices', array( $subject, 'show_cart_notice' ) );
		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', array( $subject, 'register_styles' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function check_if_show_notices_needed(){

		$cart_sync_constant = 1;
		$cart_clear_constant = 0;
		$this->wp_api->method( 'constant' )->with( 'WCML_CART_SYNC' )->willReturn( $cart_sync_constant );

		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $cart_clear_constant;
		$this->woocommerce_wpml->settings['cart_sync']['currency_switch'] = $cart_clear_constant;

		$subject = $this->get_subject();
		$this->assertFalse( $subject->check_if_show_notices_needed() );

		$this->woocommerce_wpml->settings['cart_sync']['lang_switch'] = $cart_sync_constant;
		$this->woocommerce_wpml->settings[ 'dismiss_cart_warning' ] = 1;

		$subject = $this->get_subject();
		$this->assertFalse( $subject->check_if_show_notices_needed() );
	}

	/**
	 * @test
	 */
	public function show_cart_notice(){

		$wcml_plugin_url = rand_str();
		$this->woocommerce_wpml->settings = array();
		$_SERVER['REQUEST_URI'] = rand_str();

		$this->wp_api->method( 'constant' )->with( 'WCML_PLUGIN_URL' )->willReturn( $wcml_plugin_url );
		\WP_Mock::wpFunction( 'admin_url', array(
			'args'   => array( 'admin.php?page=wpml-wcml&tab=settings#cart' ),
			'return' => 'admin.php?page=wpml-wcml&tab=settings#cart'
		) );

		$subject = $this->get_subject();

		ob_start();
		$subject->show_cart_notice();
		$cart_notice_html = ob_get_clean();

		$this->assertNotContains( 'Woocommerce Product Addons', $cart_notice_html );

		$this->getMockBuilder( 'WC_Product_Addons' )
			->disableOriginalConstructor()
			->getMock();

		ob_start();
		$subject->show_cart_notice();
		$cart_notice_html = ob_get_clean();

		$this->assertContains( 'Woocommerce Product Addons', $cart_notice_html );
	}

	/**
	 * @test
	 */
	public function get_list_of_active_extensions(){

		$subject = $this->get_subject();

		$this->getMockBuilder( 'WC_Product_Bundle' )
			->disableOriginalConstructor()
			->getMock();

		$list_of_extensions = $subject->get_list_of_active_extensions();

		$this->assertContains( 'Woocommerce Product Bundles', $list_of_extensions );

	}

}
