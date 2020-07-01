<?php

use WPML\FP\Fns;

class Test_WCML_WC_Gateways extends OTGS_TestCase {

	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress( $wp_api = null ) {
		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_default_language' ) )
		                  ->getMock();

		return $sitepress;
	}

	/**
	 * @return WCML_WC_Gateways
	 */
	private function get_subject( $woocommerce_wpml = false, $sitepress = false ) {

		if ( ! $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if ( ! $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new WCML_WC_Gateways( $woocommerce_wpml, $sitepress );
	}

	/**
	 * @test
	 * @group wcml-3266
	 */
	public function it_adds_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'init', array( $subject, 'on_init_hooks' ), 11 );

		\WP_Mock::expectFilterAdded( 'woocommerce_payment_gateways', Fns::withoutRecursion( Fns::identity(), array( $subject, 'loaded_woocommerce_payment_gateways' ) ) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_on_init_hooks() {
		global $pagenow;
		$current_pagenow = $pagenow;

		$pagenow         = 'admin.php';
		$_GET['page']    = 'wc-settings';
		$_GET['tab']     = 'checkout';
		$_GET['section'] = 'bacs';
		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_gateway_title', array( $subject, 'translate_gateway_title' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_gateway_description', array(
			$subject,
			'translate_gateway_description'
		), 10, 2 );
		\WP_Mock::expectActionAdded( 'admin_footer', array( $subject, 'show_language_links_for_gateways' ) );
		\WP_Mock::expectActionAdded( 'admin_footer', array(
			$subject,
			'append_currency_selector_to_bacs_account_settings'
		) );
		$subject->on_init_hooks();

		$pagenow = $current_pagenow;
		unset( $_GET['page'], $_GET['tab'], $_GET['section'] );
	}


	/**
	 * @test
	 * @dataProvider default_gateways_names
	 */
	public function it_should_register_default_gateway_strings( $gateway_name ) {

		$default_language = 'en';
		$settings = array();
		$settings['enabled'] = 'yes';
		$settings['title'] = rand_str(32);
		$settings['description'] = rand_str(32);
		$settings['instructions'] = rand_str(32);


		\WP_Mock::userFunction( 'icl_get_string_id', array(
			'args'   => array( $settings['title'], 'admin_texts_woocommerce_gateways', $gateway_name .'_gateway_title' ),
			'return' => false
		) );
		\WP_Mock::userFunction( 'icl_get_string_id', array(
			'args'   => array( $settings['description'], 'admin_texts_woocommerce_gateways', $gateway_name .'_gateway_description' ),
			'return' => false
		) );
		\WP_Mock::userFunction( 'icl_get_string_id', array(
			'args'   => array( $settings['instructions'], 'admin_texts_woocommerce_gateways', $gateway_name .'_gateway_instructions' ),
			'return' => false
		) );


		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_default_language' )->willReturn( $default_language );

		$subject = $this->get_subject( false, $sitepress );


		\WP_Mock::userFunction( 'icl_register_string', array(
			'args'   => array( 'admin_texts_woocommerce_gateways', $gateway_name .'_gateway_title', $settings['title'], false, $default_language ),
			'times' => 1,
			'return' => true
		) );
		\WP_Mock::userFunction( 'icl_register_string', array(
			'args'   => array( 'admin_texts_woocommerce_gateways', $gateway_name .'_gateway_description', $settings['description'], false, $default_language ),
			'times' => 1,
			'return' => true
		) );
		\WP_Mock::userFunction( 'icl_register_string', array(
			'args'   => array( 'admin_texts_woocommerce_gateways', $gateway_name .'_gateway_instructions', $settings['instructions'], false, $default_language ),
			'times' => 1,
			'return' => true
		) );

		$subject->register_gateway_settings_strings( $gateway_name, $settings );
	}

	public function default_gateways_names(){
		return array(
			array( 'cod' ),
			array( 'bacs' ),
			array( 'cheque' ),
			array( 'paypal' )
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_add_admin_footer_hook_for_bacs_page_when_mc_off() {
		global $pagenow;
		$current_pagenow = $pagenow;

		$pagenow         = 'admin.php';
		$_GET['page']    = 'wc-settings';
		$_GET['tab']     = 'checkout';
		$_GET['section'] = 'bacs';
		\WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectActionNotAdded( 'admin_footer', array(
			$subject,
			'append_currency_selector_to_bacs_account_settings'
		) );
		$subject->on_init_hooks();

		$pagenow = $current_pagenow;
		unset( $_GET['page'], $_GET['tab'], $_GET['section'] );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_dropdown_if_bacs_settings_not_saved_yet() {

		$bacs_settings            = array();
		$default_currency         = 'USD';
		$bacs_accounts_currencies = array();
		$active_currencies        = array( 'USD', 'EUR' );
		$default_dropdown         = '<select><option>USD</option></select>';

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_bacs_accounts', array() ),
			'return' => $bacs_settings
		) );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $default_currency
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'wcml_bacs_accounts_currencies', array() ),
			'return' => $bacs_accounts_currencies
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_currency_codes' ) )
		                                         ->getMock();
		$woocommerce_wpml->multi_currency->method( 'get_currency_codes' )->willReturn( $active_currencies );


		$currencies_dropdown_ui = $this->getMockBuilder( 'WCML_Currencies_Dropdown_UI' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get' ) )
		                               ->getMock();
		$currencies_dropdown_ui->method( 'get' )->with( $active_currencies, $default_currency )->willReturn( $default_dropdown );

		$subject = $this->get_subject( $woocommerce_wpml );

		$this->assertSame( array(
			$default_dropdown,
			array( $default_dropdown )
		), $subject->get_dropdown( $currencies_dropdown_ui ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_dropdown_for_bacs_settings() {

		$bacs_settings                  = array( 0 => array() );
		$default_currency               = 'USD';
		$this->bacs_accounts_currencies = array( 0 => 'EUR' );
		$active_currencies              = array( 'USD', 'EUR' );
		$this->default_dropdown         = '<select><option>USD</option></select>';
		$this->eur_dropdown             = '<select><option>EUR</option></select>';

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_bacs_accounts', array() ),
			'return' => $bacs_settings
		) );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $default_currency
		) );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'wcml_bacs_accounts_currencies', array() ),
			'return' => $this->bacs_accounts_currencies
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_currency_codes' ) )
		                                         ->getMock();
		$woocommerce_wpml->multi_currency->method( 'get_currency_codes' )->willReturn( $active_currencies );


		$currencies_dropdown_ui = $this->getMockBuilder( 'WCML_Currencies_Dropdown_UI' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get' ) )
		                               ->getMock();

		$that = $this;
		$currencies_dropdown_ui->method( 'get' )->willReturnCallback( function ( $active_currencies, $currency ) use ( $that ) {
			if ( $that->bacs_accounts_currencies[0] === $currency ) {
				return $that->eur_dropdown;
			} else {
				return $that->default_dropdown;
			}
		} );

		$subject = $this->get_subject( $woocommerce_wpml );

		$this->assertSame( array(
			$this->default_dropdown,
			array( $this->eur_dropdown )
		), $subject->get_dropdown( $currencies_dropdown_ui ) );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_translated_default_gateway_strings( $method_name, $string_name ) {

		$gateway_instructions = rand_str( 32 );
		$current_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $current_language )
		       ->reply( $gateway_instructions );

		WP_Mock::userFunction( '__', array(
			'args' => array( $gateway_instructions, 'woocommerce' ),
			'return' => $current_language_gateway_instructions
		));

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $current_language_gateway_instructions, $translated_gateway_instructions );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_current_language( $method_name, $string_name ) {

		$gateway_instructions = rand_str( 32 );
		$current_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $current_language )
		       ->reply( $current_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $current_language_gateway_instructions, $translated_gateway_instructions );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language( $method_name, $string_name ) {

		$_POST['wc_order_action'] = 'send_order_details';
		$_POST['order_status'] = 'wc-on-hold';
		$_POST['post_ID'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['post_ID'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['wc_order_action'] );
		unset( $_POST['order_status'] );
		unset( $_POST['post_ID'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_completed_order_email( $method_name, $string_name ) {

		$_POST['wc_order_action'] = 'send_order_details';
		$_POST['order_status'] = 'wc-completed';
		$_POST['post_ID'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['post_ID'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['wc_order_action'] );
		unset( $_POST['order_status'] );
		unset( $_POST['post_ID'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_processing_order_email( $method_name, $string_name ) {

		$_POST['wc_order_action'] = 'send_order_details';
		$_POST['order_status'] = 'wc-processing';
		$_POST['post_ID'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['post_ID'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['wc_order_action'] );
		unset( $_POST['order_status'] );
		unset( $_POST['post_ID'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_refunded_order_email( $method_name, $string_name ) {

		$_POST['wc_order_action'] = 'send_order_details';
		$_POST['order_status'] = 'wc-refunded';
		$_POST['post_ID'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['post_ID'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['wc_order_action'] );
		unset( $_POST['order_status'] );
		unset( $_POST['post_ID'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_on_hold_order_email( $method_name, $string_name ) {

		$_POST['order_status'] = 'wc-on-hold';
		$_POST['post_ID'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['post_ID'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['order_status'] );
		unset( $_POST['post_ID'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_inline_refunded_order_email( $method_name, $string_name ) {

		$_POST['action'] = 'woocommerce_refund_line_items';
		$_POST['order_id'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['order_id'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['action'] );
		unset( $_POST['order_id'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_user_note_order_email( $method_name, $string_name ) {

		$_POST['action'] = 'woocommerce_add_order_note';
		$_POST['note_type'] = 'customer';
		$_POST['post_id'] = 10;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_POST['post_id'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['action'] );
		unset( $_POST['note_type'] );
		unset( $_POST['post_id'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_completed_order_email_ajax( $method_name, $string_name ) {

		$_GET['action'] = 'woocommerce_mark_order_status';
		$_GET['status'] = 'completed';
		$_GET['order_id'] = 12;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_GET['order_id'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_GET['action'] );
		unset( $_GET['status'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_language_for_processing_order_email_ajax( $method_name, $string_name ) {

		$_GET['action'] = 'woocommerce_mark_order_status';
		$_GET['status'] = 'processing';
		$_GET['order_id'] = 12;

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$order_language = 'es';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $_GET['order_id'], 'wpml_language', true ),
			'return' => $order_language
		) );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $order_language )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_GET['action'] );
		unset( $_GET['status'] );
	}

	/**
	 * @test
	 * @dataProvider gateway_string_method
	 */
	public function it_should_get_gateway_strings_in_order_dashboard_language( $method_name, $string_name ) {

		$_POST['action'] = 'editpost';
		$_POST['post_status'] = 'draft';
		$_POST['save'] = 'Create';
		$_POST['order_status'] = 'wc-on-hold';
		$_POST['post_ID'] = 10;
		$_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ] = 'es';

		$gateway_instructions = rand_str( 32 );
		$order_language_gateway_instructions = rand_str( 32 );
		$gateway_id = 'bacs';
		$current_language = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $gateway_instructions, 'admin_texts_woocommerce_gateways', $gateway_id . $string_name, $_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ] )
		       ->reply( $order_language_gateway_instructions );

		$translated_gateway_instructions = $subject->$method_name( $gateway_instructions, $gateway_id );

		$this->assertSame( $order_language_gateway_instructions, $translated_gateway_instructions );

		unset( $_POST['action'] );
		unset( $_POST['post_status'] );
		unset( $_POST['save'] );
		unset( $_POST['order_status'] );
		unset( $_POST['post_ID'] );
		unset( $_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ] );
	}

	public function gateway_string_method(){

		return array(
			array( 'translate_gateway_title', '_gateway_title' ),
			array( 'translate_gateway_description', '_gateway_description' ),
			array( 'translate_gateway_instructions', '_gateway_instructions' )
		);
	}
}
