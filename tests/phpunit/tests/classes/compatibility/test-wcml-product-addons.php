<?php

class Test_WCML_Product_Addons extends OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** var int */
	private $independent_currencies;

	public function setUp() {
		parent::setUp();

		$this->independent_currencies = 2;

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_wp_api' ) )
		                        ->getMock();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();

		$that = $this;
		$wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WCML_MULTI_CURRENCIES_INDEPENDENT' == $const ) {
				return $that->independent_currencies;
			} else if ( 'WCML_PLUGIN_PATH' == $const ) {
				return rand_str();
			}
		} );

		$this->sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->woocommerce_wpml->settings['enable_multi_currency'] = $this->independent_currencies;

	}

	public function get_subject() {

		return new WCML_Product_Addons( $this->sitepress, $this->woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_admin_hooks() {
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array(
			$subject,
			'replace_tm_editor_custom_fields_with_own_sections'
		) );
		\WP_Mock::expectFilterAdded( 'wcml_cart_contents_not_changed', array(
			$subject,
			'filter_booking_addon_product_in_cart_contents'
		), 20 );
		\WP_Mock::expectActionAdded( 'woocommerce_product_addons_panel_start', array(
			$subject,
			'load_dialog_resources'
		) );
		\WP_Mock::expectActionAdded( 'woocommerce_product_addons_panel_option_row', array(
			$subject,
			'dialog_button_after_option_row'
		), 10, 4 );
		\WP_Mock::expectActionAdded( 'woocommerce_product_addons_panel_before_options', array(
			$subject,
			'dialog_button_before_options'
		), 10, 3 );
		\WP_Mock::expectActionAdded( 'wcml_before_sync_product', array( $subject, 'update_custom_prices_values' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_front_hooks() {
		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'translate_addons_strings' ), 10, 4 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections() {

		$subject        = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_product_addons' ), $fields_to_hide );

	}

	/**
	 * @test
	 */
	public function it_sets_global_ids_without_adjusting_term_ids_in_query_args() {

		$args              = array();
		$args['tax_query'] = array();

		$global_addons    = array();
		$global_addon     = new stdClass();
		$global_addon->ID = mt_rand( 1, 10 );
		$global_addons[]  = $global_addon;

		\WP_Mock::wpFunction( 'is_archive', array(
			'return' => false
		) );

		\WP_Mock::wpFunction( 'get_posts', array(
			'args'   => array( $args ),
			'return' => $global_addons
		) );

		\WP_Mock::wpFunction( 'wp_list_pluck', array(
			'return' => array( $global_addon->ID )
		) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => true,
			'times'  => 3
		) );

		\WP_Mock::expectFilterAdded( 'get_terms_args', array( $this->sitepress, 'get_terms_args_filter' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1 );
		\WP_Mock::expectFilterAdded( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10, 3 );

		$subject             = $this->get_subject();
		$filtered_query_args = $subject->set_global_ids_in_query_args( $args );

		$this->assertEquals( array( 'include' => array( $global_addon->ID ) ), $filtered_query_args );

	}

	/**
	 * @test
	 */
	public function it_should_filter_product_addons_price() {

		$client_currency = 'USD';

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'woocommerce_wpml' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$subject = $this->get_subject();

		$not_converted_price = 33;
		$converted_price     = 55;

		$addons = array(
			array(
				'price'   => 0,
				'options' => array(
					array(
						'price'                     => 11,
						'price_' . $client_currency => 111
					)
				)
			),
			array(
				'price'                     => 22,
				'price_' . $client_currency => 222,
				'options'                   => array()
			),
			array(
				'price'   => $not_converted_price,
				'options' => array()
			)
		);

		\WP_Mock::onFilter( 'wcml_raw_price_amount' )->with( $not_converted_price )->reply( $converted_price );

		$expected_addons = array(
			array(
				'price'   => 0,
				'options' => array(
					array(
						'price'                     => 111,
						'price_' . $client_currency => 111
					)
				)
			),
			array(
				'price'                     => 222,
				'price_' . $client_currency => 222,
				'options'                   => array()
			),
			array(
				'price'   => $converted_price,
				'options' => array()
			)
		);

		$filtered_addons = $subject->product_addons_price_filter( $addons );
		$this->assertSame( $expected_addons, $filtered_addons );
	}

	/**
	 * @test
	 */
	public function it_should_add_button_to_open_currencies_dialog() {

		$default_currency = 'EUR';
		$currencies       = array( 'USD' => array() );

		$product     = new stdClass();
		$product->ID = 1;

		$option         = array(
			'label'     => 'test option',
			'price'     => 10,
			'price_USD' => 100
		);
		$product_addons = array( array( 'options' => array( $option ) ) );
		$loop           = 0;

		$expected_dialog_model = array();


		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_currencies' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_currencies' )->willReturn( $currencies );

		$subject = $this->get_subject();

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'show' ) )
		                         ->getMock();

		$template_service->method( 'show' )->willReturn( 'dialog_template' );

		$twig = \Mockery::mock( 'overload:WPML_Twig_Template_Loader' );
		$twig->shouldReceive( 'get_template' )
		     ->andReturn( $template_service );

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $product->ID, '_wcml_custom_prices_status', true ),
			'return' => true
		) );

		WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'woocommerce_currency' ),
			'return' => $default_currency
		) );

		ob_start();
		$subject->dialog_button_after_option_row( $product, $product_addons, $loop, $option );
		$dialog = ob_get_clean();

		$this->assertSame( 'dialog_template', $dialog );
	}


	/**
	 * @test
	 */
	public function it_should_update_custom_prices_values() {

		$currencies = array( 'USD' => array() );

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_currencies' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_currencies' )->willReturn( $currencies );

		$subject = $this->get_subject();

		$product_id = 1;

		$product_addons = array(
			array(
				'type'    => 'checkboxes',
				'options' => array(
					array( 'price' => 10 )
				)
			)
		);

		$_POST['_product_addon_prices'][0]['price_USD'][0] = 100;

		WP_Mock::userFunction( 'wc_format_decimal', array(
			'args'   => array( 100 ),
			'return' => 100
		) );

		$expected_addons = array(
			array(
				'type'    => 'checkboxes',
				'options' => array(
					array(
						'price'     => 10,
						'price_USD' => 100
					)
				)
			)
		);


		WP_Mock::userFunction( 'maybe_unserialize', array(
			'return' => $product_addons
		) );


		WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $product_id, '_product_addons', $expected_addons ),
			'times'  => 1,
			'return' => true
		) );

		$subject->update_custom_prices_values( $product_id );

	}

}
