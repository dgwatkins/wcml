<?php

/**
 * Class Test_WCML_Admin_Curreny_Selector
 *
 * @group fix-tests-on-windows
 */
class Test_WCML_Admin_Curreny_Selector extends OTGS_TestCase {

	public function tearDown() {
		global $pagenow;
		parent::tearDown();

		unset( $pagenow, $_POST['wcml_nonce'], $_POST['currency'], $_COOKIE ['_wcml_dashboard_currency'] );
	}

	private function get_subject( $woocommerce_wpml = null, $currency_cookie = null ) {

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if ( null === $currency_cookie ) {
			$currency_cookie = $this->get_wcml_admin_cookie_mock();
		}

		return new WCML_Admin_Currency_Selector( $woocommerce_wpml, $currency_cookie );

	}

	private function get_woocommerce_wpml_mock() {
		return $this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                                      ->disableOriginalConstructor()
		                                      ->getMock();
	}

	private function get_wcml_admin_cookie_mock() {
		return $this->getMockBuilder( 'WCML_Admin_Cookie' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_value', 'set_value' ] )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function add_hooks_not_admin() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
		$this->expectActionAdded( 'admin_enqueue_scripts', array( $subject, 'load_js' ), 10, 1, 0 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_yes_admin_user_can_manage() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		\WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );

		\WP_Mock::expectActionAdded( 'init', array( $subject, 'set_dashboard_currency' ), 10, 1 );
		\WP_Mock::expectActionAdded( 'wp_ajax_wcml_dashboard_set_currency', array(
			$subject,
			'set_dashboard_currency_ajax'
		), 10, 1 );
		\WP_Mock::expectFilterAdded( 'woocommerce_currency_symbol', array(
			$subject,
			'filter_dashboard_currency_symbol'
		), 10, 1 );

		global $pagenow;
		$pagenow = 'index.php';
		$this->expectActionAdded( 'admin_footer', array( $subject, 'show_dashboard_currency_selector' ), 10, 1, 0 );
		\WP_Mock::expectActionAdded( 'woocommerce_after_dashboard_status_widget',
			array( $subject, 'show_dashboard_currency_selector' ), 10, 1 );

		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', array( $subject, 'load_js' ), 10, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_admin_user_cant_manage() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		\WP_Mock::userFunction( 'current_user_can', [ 'return' => false ] );

		$this->expectActionAdded( 'init', array( $subject, 'set_dashboard_currency' ), 10, 1, 0 );
		$this->expectActionAdded( 'wp_ajax_wcml_dashboard_set_currency',
			array( $subject, 'set_dashboard_currency_ajax' ), 10, 1, 0 );

		global $pagenow;
		$pagenow = 'index.php';
		$this->expectActionAdded( 'admin_footer', array( $subject, 'show_dashboard_currency_selector' ), 10, 1, 0 );
		\WP_Mock::expectActionAdded( 'woocommerce_after_dashboard_status_widget',
			array( $subject, 'show_dashboard_currency_selector' ), 10, 1, 0 );

		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', array( $subject, 'load_js' ), 10, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function load_js() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'wp_enqueue_script', [ 'times' => 1 ] );
		\WP_Mock::userFunction( 'wp_localize_script', [ 'times' => 1 ] );
		\WP_Mock::userFunction( 'wp_create_nonce', [ 'times' => 1, 'args' => 'wcml-admin-currency-selector' ] );
		$subject->load_js();
	}

	/**
	 * @test
	 */
	public function show_dashboard_currency_selector() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::passthruFunction( '_e' );

		$woocommerce_currency   = rand_str();
		$another_currency       = rand_str();
		$woocommerce_currencies = [
			$woocommerce_currency => rand_str(),
			$another_currency     => rand_str()
		];
		\WP_Mock::userFunction( 'get_woocommerce_currencies', [ 'return' => $woocommerce_currencies ] );
		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [ 'return' => $woocommerce_currency ] );

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->getMock();

		$woocommerce_wpml->multi_currency->orders = $this->getMockBuilder( 'WCML_Multi_Currency_Orders' )
		                                                 ->disableOriginalConstructor()
		                                                 ->setMethods( [ 'get_orders_currencies' ] )
		                                                 ->getMock();

		$currencies = [
			$woocommerce_currency => random_int( 0, 100 ),
			$another_currency     => random_int( 0, 100 ),
		];
		$woocommerce_wpml->multi_currency->orders->method( 'get_orders_currencies' )
		                                         ->willReturn( $currencies );

		ob_start();
		$subject->show_dashboard_currency_selector();

		$content = ob_get_contents();
		ob_end_clean();

		$this->assertContains( '<option value="' . $woocommerce_currency . '" selected="selected">', $content );
		$this->assertContains( '<option value="' . $another_currency . '" >', $content );

	}

	/**
	 * @test
	 */
	public function set_dashboard_currency_ajax_invalid_nonce() {

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$currency_cookie  = $this->get_wcml_admin_cookie_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $currency_cookie );

		$_POST['wcml_nonce'] = rand_str();
		\WP_Mock::passthruFunction( '__' );
		\WP_Mock::passthruFunction( 'sanitize_text_field' );
		\WP_Mock::userFunction( 'wp_verify_nonce', [ 'return' => false ] );
		\WP_Mock::userFunction( 'wp_send_json_error', [
				'args' => [ 'Invalid nonce', 403 ]
			]
		);

		$currency_code = rand_str();
		$subject->set_dashboard_currency_ajax( $currency_code );
	}

	/**
	 * @test
	 */
	public function set_dashboard_currency_ajax_valid_nonce() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$currency_cookie  = $this->get_wcml_admin_cookie_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $currency_cookie );

		$currency_code       = rand_str();
		$_POST['wcml_nonce'] = rand_str();
		$_POST['currency']   = $currency_code;
		\WP_Mock::passthruFunction( 'sanitize_text_field' );
		\WP_Mock::userFunction( 'wp_verify_nonce', [ 'return' => true ] );
		\WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 0 ] );
		\WP_Mock::userFunction(
			'wp_send_json_success',
			[
				'times' => 1,
			]
		);

		$currency_cookie->expects( $this->once() )->method( 'set_value' )
		                ->with( $currency_code, $this->isType( 'int' ) );
		$subject->set_dashboard_currency_ajax();
	}

	/**
	 * @test
	 */
	public function set_dashboard_currency() {

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$currency_cookie  = $this->get_wcml_admin_cookie_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $currency_cookie );

		$currency_code = rand_str();
		$currency_cookie->expects( $this->once() )->method( 'set_value' )
		                ->with( $currency_code, $this->isType( 'int' ) );
		$subject->set_dashboard_currency( $currency_code );

	}

	/**
	 * @test
	 */
	public function it_should_not_set_dashboard_currency_for_not_dashborad_pages() {

		global $pagenow;
		$pagenow = 'not_index.php';
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$currency_cookie  = $this->get_wcml_admin_cookie_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $currency_cookie );

		$currency_code = rand_str();
		$currency_cookie->expects( $this->never() )->method( 'set_value' );
		$subject->set_dashboard_currency();

		$pagenow = 'index.php';
	}

	/**
	 * @test
	 */
	public function get_cookie_dashboard_currency_from_cookie() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$currency_cookie  = $this->get_wcml_admin_cookie_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $currency_cookie );

		$currency_code = rand_str();
		$currency_cookie->expects( $this->once() )
		                ->method( 'get_value' )
		                ->willReturn( $currency_code );

		$this->assertSame( $currency_code, $subject->get_cookie_dashboard_currency() );
	}

	/**
	 * @test
	 */
	public function get_cookie_dashboard_currency_NOT_from_cookie() {

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$currency_cookie  = $this->get_wcml_admin_cookie_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $currency_cookie );

		$currency_code = rand_str();
		$currency_cookie->expects( $this->once() )
		                ->method( 'get_value' )
		                ->willReturn( null );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [ 'return' => $currency_code ] );

		$this->assertSame( $currency_code, $subject->get_cookie_dashboard_currency() );

	}

	/**
	 * @test
	 */
	public function filter_dashboard_currency_symbol_NOT_index_php() {
		global $pagenow;
		$pagenow = 'index.php' . rand_str();

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_currency_symbol', array(
			$subject,
			'filter_dashboard_currency_symbol'
		) );

		\WP_Mock::userFunction( 'remove_filter', [
				'args' => [
					'woocommerce_currency_symbol',
					[ $subject, 'filter_dashboard_currency_symbol' ]
				]
			]
		);

		\WP_Mock::userFunction( 'get_woocommerce_currency_symbol', [ 'times' => 0 ] );

		$currency = rand_str();

		$this->assertSame( $currency, $subject->filter_dashboard_currency_symbol( $currency ) );

	}

	/**
	 * @test
	 */
	public function filter_dashboard_currency_symbol_YES_index_php() {
		global $pagenow;
		$pagenow = 'index.php';

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_currency_symbol', array(
			$subject,
			'filter_dashboard_currency_symbol'
		) );

		\WP_Mock::userFunction( 'remove_filter', [
				'args' => [
					'woocommerce_currency_symbol',
					[ $subject, 'filter_dashboard_currency_symbol' ]
				]
			]
		);

		$currency                             = rand_str();
		$_COOKIE ['_wcml_dashboard_currency'] = rand_str();

		\WP_Mock::userFunction( 'get_woocommerce_currency_symbol', [ 'return' => $_COOKIE ['_wcml_dashboard_currency'] ] );

		$this->assertSame( $_COOKIE ['_wcml_dashboard_currency'], $subject->filter_dashboard_currency_symbol( $currency ) );

	}

}
