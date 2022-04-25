<?php

namespace WCML\Compatibility\WcProductAddons;

use PHPUnit\Framework\MockObject\MockObject;
use woocommerce_wpml;
use WP_Mock;

/**
 * @group compatibility
 * @group wc-product-addons
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	const CLIENT_CURRENCY = 'USD';
	const CURRENCIES = [
		'EUR' => [],
		'USD' => [],
		'GBP' => [],
	];

	/**
	 * @param MockObject|woocommerce_wpml $woocommerce_wpml
	 *
	 * @return MulticurrencyHooks
	 */
	private function getSubject( $woocommerce_wpml = null ) {
		$woocommerce_wpml = $woocommerce_wpml ?: $this->getWooCommerceWpml();

		return new MulticurrencyHooks( $woocommerce_wpml );
	}

	/**
	 * @return MockObject|woocommerce_wpml
	 */
	private function getWooCommerceWpml() {
		$wooWpml = $this->getMockBuilder( woocommerce_wpml::class )
		                ->disableOriginalConstructor()
		                ->getMock();

		$mc = $this->getMockBuilder( \WCML_Multi_Currency::class )
		           ->setMethods( [
			           'get_client_currency',
			           'get_currencies',
		           ] )
		           ->disableOriginalConstructor()
		           ->getMock();

		$mc->method( 'get_client_currency' )->willReturn( self::CLIENT_CURRENCY );
		$mc->method( 'get_currencies' )->willReturn( self::CURRENCIES );

		$wooWpml->multi_currency = $mc;

		return $wooWpml;
	}

	/**
	 * @test
	 */
	public function itShouldAddHooksInAdmin() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );

		$subject = $this->getSubject();

		WP_Mock::expectFilterAdded( 'get_product_addons_fields', [ $subject, 'product_addons_price_filter' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'wcml_cart_contents_not_changed', [
			$subject,
			'filter_booking_addon_product_in_cart_contents',
		], 20 );
		WP_Mock::expectActionAdded( 'save_post', [ $subject, 'maybeUpdateCustomPricesValues' ], 10, 2 );

		// admin only
		WP_Mock::expectActionAdded( 'woocommerce_product_addons_panel_start', [ $subject, 'load_dialog_resources' ] );
		WP_Mock::expectActionAdded( 'woocommerce_product_addons_panel_option_row', [
			$subject,
			'dialog_button_after_option_row',
		], 10, 4 );
		WP_Mock::expectActionAdded( 'woocommerce_product_addons_panel_before_options', [
			$subject,
			'dialog_button_before_options',
		], 10, 3 );
		WP_Mock::expectActionAdded( 'wcml_before_sync_product', [ $subject, 'update_custom_prices_values' ] );
		WP_Mock::expectActionAdded( 'woocommerce_product_addons_global_edit_objects', [
			$subject,
			'custom_prices_settings_block',
		] );

		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function itShouldAddHooksInFrontend() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$subject = $this->getSubject();

		WP_Mock::expectFilterAdded( 'get_product_addons_fields', [ $subject, 'product_addons_price_filter' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'wcml_cart_contents_not_changed', [
			$subject,
			'filter_booking_addon_product_in_cart_contents',
		], 20 );
		WP_Mock::expectActionNotAdded( 'save_post', [ $subject, 'maybeUpdateCustomPricesValues' ], 10, 2 );

		// admin only
		WP_Mock::expectActionNotAdded( 'woocommerce_product_addons_panel_start', [
			$subject,
			'load_dialog_resources',
		] );
		WP_Mock::expectActionNotAdded( 'woocommerce_product_addons_panel_option_row', [
			$subject,
			'dialog_button_after_option_row',
		], 10, 4 );
		WP_Mock::expectActionNotAdded( 'woocommerce_product_addons_panel_before_options', [
			$subject,
			'dialog_button_before_options',
		], 10, 3 );
		WP_Mock::expectActionNotAdded( 'wcml_before_sync_product', [ $subject, 'update_custom_prices_values' ] );
		WP_Mock::expectActionNotAdded( 'woocommerce_product_addons_global_edit_objects', [
			$subject,
			'custom_prices_settings_block',
		] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider dp_should_filter_product_addons_price
	 *
	 * @param array $addons
	 * @param array $expectedAddons
	 */
	public function it_should_filter_product_addons_price( $addons, $expectedAddons ) {
		$productId = 123;

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $productId, '_wcml_custom_prices_status', true ],
			'return' => true,
		] );

		$this->assertSame( $expectedAddons, $this->getSubject()->product_addons_price_filter( $addons, $productId ) );
	}

	public function dp_should_filter_product_addons_price() {
		return
			[
				'Flat_Fee_Addon_Options_Level_Prices'         => [
					[
						[
							'price'   => 0,
							'options' => [ [ 'price' => 11, 'price_USD' => 111, 'price_type' => 'flat_fee' ] ],
						],
					],
					[
						[
							'price'   => 0,
							'options' => [ [ 'price' => 111, 'price_USD' => 111, 'price_type' => 'flat_fee' ] ],
						],
					],
				],
				'Flat_Fee_Addon_Level_Prices'                 => [
					[ [ 'price' => 22, 'price_USD' => 222, 'options' => [], 'price_type' => 'flat_fee' ] ],
					[ [ 'price' => 222, 'price_USD' => 222, 'options' => [], 'price_type' => 'flat_fee' ] ],
				],
				'Flat_Fee_Empty_Addon_Options_Level_Prices'   => [
					[ [ 'price' => 10, 'options' => [], 'price_type' => 'flat_fee' ] ],
					[ [ 'price' => 10, 'options' => [], 'price_type' => 'flat_fee' ] ],
				],
				'Percentage_Based_Addon_Level_Prices'         => [
					[ [ 'price' => 12, 'options' => [], 'price_type' => 'percentage_based' ] ],
					[ [ 'price' => 12, 'options' => [], 'price_type' => 'percentage_based' ] ],
				],
				'Percentage_Based_Addon_Options_Level_Prices' => [
					[ [ 'price' => 0, 'options' => [ [ 'price' => 14, 'price_type' => 'percentage_based' ] ] ] ],
					[ [ 'price' => 0, 'options' => [ [ 'price' => 14, 'price_type' => 'percentage_based' ] ] ] ],
				],
				'Quantity_Based_Addon_Level_Prices'           => [
					[ [ 'price' => 3, 'price_USD' => 33, 'options' => [], 'price_type' => 'quantity_based' ] ],
					[ [ 'price' => 33, 'price_USD' => 33, 'options' => [], 'price_type' => 'quantity_based' ] ],
				],
				'Quantity_Based_Addon_Options_Level_Prices'   => [
					[
						[
							'price'   => 0,
							'options' => [ [ 'price' => 5, 'price_USD' => 55, 'price_type' => 'quantity_based' ] ],
						],
					],
					[
						[
							'price'   => 0,
							'options' => [ [ 'price' => 55, 'price_USD' => 55, 'price_type' => 'quantity_based' ] ],
						],
					],
				],
				'Addon_Options_No_Price_Type'                 => [
					[ [ 'price' => 10 ], 'options' => [] ],
					[ [ 'price' => 10 ], 'options' => [] ],
				],
				'Addon_Options_No_Options'                    => [
					[ [ 'price' => 10 ] ],
					[ [ 'price' => 10 ] ],
				],
			];
	}

	/**
	 * @param string      $expectedOutput
	 * @param array|null  $showArg1
	 * @param string|null $showArg2
	 *
	 * @return void
	 */
	private function mockTemplate( $expectedOutput, $showArg1 = null, $showArg2 = null ) {
		$templateService = $this->getMockBuilder( 'IWPML_Template_Service' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [ 'show' ] )
		                        ->getMock();

		if ( $showArg1 && $showArg2 ) {
			$templateService->method( 'show' )
			                ->with( $showArg1, $showArg2 )
			                ->willReturn( $expectedOutput );
		} else {
			$templateService->method( 'show' )->willReturn( $expectedOutput );
		}

		$twig = \Mockery::mock( 'overload:WPML_Twig_Template_Loader' );
		$twig->shouldReceive( 'get_template' )->andReturn( $templateService );
	}

	/**
	 * @test
	 */
	public function it_should_add_button_to_open_currencies_dialog() {
		$defaultCurrency = 'EUR';
		$expectedOutput  = 'dialog_template';

		$product     = \Mockery::mock( \WP_Post::class );
		$product->ID = 1;

		$option = [
			'label'     => 'test option',
			'price'     => 10,
			'price_USD' => 100,
		];

		$productAddons = [
			[
				'options' => [ $option ],
			],
			[],
		];

		$loop = 0;

		$this->mockTemplate( $expectedOutput );

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $product->ID, '_wcml_custom_prices_status', true ],
			'return' => true,
		] );

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option' )->andReturn( $defaultCurrency );

		ob_start();
		$this->getSubject()->dialog_button_after_option_row( $product, $productAddons, $loop, $option );
		$dialog = ob_get_clean();

		$this->assertSame( 'dialog_template', $dialog );
	}


	/**
	 * @test
	 */
	public function it_should_update_custom_prices_values() {
		$productId = 1;
		$priceUsd  = 100;

		$productAddons = [
			[
				'type'    => 'checkboxes',
				'options' => [
					[ 'price' => 10 ],
				],
			],
			[
				'type' => 'no_options',
			],
		];

		$_POST['_product_addon_prices'][0]['price_USD'][0] = $priceUsd;

		WP_Mock::userFunction( 'wc_format_decimal', [
			'args'   => [ $priceUsd ],
			'return' => $priceUsd,
		] );

		$expectedAddons = [
			[
				'type'    => 'checkboxes',
				'options' => [
					[ 'price' => 10, 'price_USD' => $priceUsd ],
				],
			],
			[
				'type' => 'no_options',
			],
		];

		WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ $productId ],
			'return' => 'global_product_addon',
		] );

		WP_Mock::userFunction( 'get_post_meta', [
			'return' => $productAddons,
		] );

		WP_Mock::passthruFunction( 'maybe_unserialize' );


		WP_Mock::userFunction( 'update_post_meta', [
			'args'   => [ $productId, '_product_addons', $expectedAddons ],
			'times'  => 1,
			'return' => true,
		] );

		$_POST['_wcml_custom_prices_nonce'] = rand_str();
		$_POST['_wcml_custom_prices']       = 1;

		WP_Mock::userFunction( 'wp_verify_nonce', [
			'args'   => [ $_POST['_wcml_custom_prices_nonce'], 'wcml_save_custom_prices' ],
			'times'  => 1,
			'return' => true,
		] );

		WP_Mock::userFunction( 'update_post_meta', [
			'args'   => [ $productId, '_wcml_custom_prices_status', 1 ],
			'times'  => 1,
			'return' => true,
		] );

		$this->getSubject()->update_custom_prices_values( $productId );
	}

	/**
	 * @test
	 */
	public function it_should_add_custom_prices_settings_block() {
		$nonce        = rand_str();
		$_GET['edit'] = 11;

		WP_Mock::userFunction( 'wp_create_nonce', [
			'args'   => [ 'wcml_save_custom_prices' ],
			'times'  => 1,
			'return' => $nonce,
		] );

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $_GET['edit'], '_wcml_custom_prices_status', true ],
			'times'  => 1,
			'return' => 1,
		] );

		$expectedSettingsModel = [
			'strings'          => [
				'label'    => 'Multi-currency settings',
				'auto'     => 'Calculate prices in other currencies automatically',
				'manually' => 'Set prices in other currencies manually',
			],
			'custom_prices_on' => 1,
			'nonce'            => $nonce,
		];

		$expectedOutput = 'settings_template';

		$this->mockTemplate( $expectedOutput, $expectedSettingsModel, MulticurrencyHooks::SETTINGS_TEMPLATE );

		ob_start();
		$this->getSubject()->custom_prices_settings_block();
		$template = ob_get_clean();

		$this->assertSame( 'settings_template', $template );
	}
}
