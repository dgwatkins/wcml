<?php


class Test_WCML_Prduct_Prices extends WCML_UnitTestCase {

	function setUp(){
		global $woocommerce_wpml, $sitepress;

		add_filter('wcml_load_multi_currency', '__return_true');
		set_current_screen( 'front' );

		parent::setUp();

		$this->sitepress        =& $sitepress;
		$this->woocommerce_wpml =& $woocommerce_wpml;

		// Multi currency objects
		require_once WCML_PLUGIN_PATH . '/inc/multi-currency-support.class.php';
		$woocommerce_wpml->multi_currency_support = new WCML_Multi_Currency_Support;
		require_once WCML_PLUGIN_PATH . '/inc/multi-currency.class.php';
		$woocommerce_wpml->multi_currency = new WCML_WC_MultiCurrency();

		$woocommerce_wpml->multi_currency_support->init();
		$woocommerce_wpml->multi_currency->init();

		// settings
		$this->settings 				=& $woocommerce_wpml->settings;
		$this->multi_currency 			=& $woocommerce_wpml->multi_currency;
		$this->multi_currency_support 	=& $woocommerce_wpml->multi_currency_support;

		$this->wcml_helper = new WCML_Helper();

		// LANGUAGE AND CURRENCIES
		$this->default_currency 	= 'GBP';
		$this->secondary_currencies	= array('USD', 'RON', 'AUD', 'CHF');

		$this->set_up_currencies();
		$this->set_up_languages();

		// PRODUCTS
		$this->products['simple'] = $this->add_simple_product('Test Product Simple');

	}

	public function set_up_languages(){

		$this->default_language			= WPML_TEST_LANGUAGE_CODE;
		$this->languages 		= array_map('trim', explode(',', WPML_TEST_LANGUAGE_CODES));

	}

	public function set_up_currencies(){

		$this->settings['enable_multi_currency'] = 2;
		$this->settings['default_currencies'] = array( 'en' => 'USD', 'de' => 'RON', 'it' => 'AUD'  );
		$this->settings['currency_options']['USD'] = array(
			'rate' 				=> 1.55,
			'position'			=> 'left',
			'thousand_sep'		=> '#',
			'decimal_sep'		=> '@',
			'num_decimals'		=> 4,
			'rounding'			=> 'down',
			'rounding_increment'=> 10,
			'auto_subtract'		=> 3
		);

		$this->settings['currency_options']['RON'] = array(
			'rate' 				=> 1.64,
			'position'			=> 'right',
			'thousand_sep'		=> '.',
			'decimal_sep'		=> ',',
			'num_decimals'		=> 0,
			'rounding'			=> 'up',
			'rounding_increment'=> 100,
			'auto_subtract'		=> 1
		);

		$this->settings['currency_options']['AUD'] = array(
			'rate' 				=> 2.45,
			'position'			=> 'right_space',
			'thousand_sep'		=> ',',
			'decimal_sep'		=> '.',
			'num_decimals'		=> 1,
			'rounding'			=> 'disabled',
			'rounding_increment'=> 0,
			'auto_subtract'		=> 0
		);

		$this->settings['currency_options']['CHF'] = array(
			'rate' 				=> 55,
			'position'			=> 'right',
			'thousand_sep'		=> '.',
			'decimal_sep'		=> ',',
			'num_decimals'		=> 2,
			'rounding'			=> 'disabled',
			'rounding_increment'=> 0,
			'auto_subtract'		=> 0

		);

		$this->woocommerce_wpml->update_settings( $this->settings );

	}

	public function add_simple_product($title){

		$product['title'] = $title;
		$product['price'] = 1234.56;

		$product['converted_price']['GBP'] = $product['price'];
		// according to settings defined in self::set_up_currencies -> round down, rounding increment 10, subtract 3
		// 1234.56 * 1.55 = 1913.568 (rounded down, rounding increment 10) -> 1910 -> 1907 (subtract 3)
		$product['converted_price']['USD'] = 1907;
		// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
		// 1234.56 * 1.64 =~ 2025 (rounded up, rounding increment 100) -> 2099 (subtract 1)
		$product['converted_price']['RON'] = 2099;
		// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
		// 1234.56 * 2.45 =~ 3024.672 (rounding disabled, num decimals 1)
		$product['converted_price']['AUD'] = 3024.6;
		// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
		// 1234.56 * 55 =~ 67900.80 (num decimals 2, rounding disabled)
		$product['converted_price']['CHF'] = round( $product['price'] * $this->settings['currency_options']['CHF']['rate'], 2 );

		// according to default woocommerce settings: symbol left (no space), ',' thousands separator, '.' decimal separator
		$product['formatted_price']['GBP'] = '<span class="amount">&pound;1,234.56</span>';
		// according to settings defined in self::set_up_currencies ->
		// symbol left (no space), '#' thousands separator, '@' decimal separator, 4 decimals
		$product['formatted_price']['USD'] = '<span class="amount">&#36;1#907@0000</span>';
		// according to settings defined in self::set_up_currencies ->
		// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 0 decimals
		$product['formatted_price']['RON'] = '<span class="amount">2.099lei</span>';
		// according to settings defined in self::set_up_currencies ->
		// symbol right (w space), '.' thousands separator, ',' decimal separator, 1 decimals
		$product['formatted_price']['AUD'] = '<span class="amount">3,024.6&nbsp;&#36;</span>';
		// according to settings defined in self::set_up_currencies ->
		// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 2 decimals
		$product['formatted_price']['CHF'] = '<span class="amount">67.900,80&#67;&#72;&#70;</span>';

		$product['price_on_language']['en'] = $product['converted_price']['USD'];
		// 'USD' price according to settings defined in self::set_up_currencies
		$product['price_on_language']['de'] = $product['converted_price']['RON'];
		// keep previous according to settings defined in self::set_up_currencies
		$product['price_on_language']['fr'] = $product['converted_price']['RON'];
		// keep previous according to settings defined in self::set_up_currencies
		$product['price_on_language']['es'] = $product['converted_price']['RON'];
		// keep previous according to settings defined in self::set_up_currencies
		$product['price_on_language']['ru'] = $product['converted_price']['RON'];
		// 'AUD' price according to settings defined in self::set_up_currencies
		$product['price_on_language']['it'] = $product['converted_price']['AUD'];

		$product['post'] = $this->wcml_helper->add_product( $this->default_language , false, $product['title'], 0,
							array( '_price' => $product['price'], '_regular_price' => $product['price'] ) );

		return $product;

	}

	// Check prices for a simple product, for different currencies and different languages
	public function test_simple_product(){

		$product = $this->products['simple'];
		$currencies = array_merge( array($this->default_currency), $this->secondary_currencies );

		foreach( $currencies as $currency){

			$this->multi_currency_support->set_client_currency( $currency );
			$wc_product = new WC_Product_Simple( $product['post']->id );

			// Compare amount with expected amounts
			$this->assertEquals( $product['converted_price'][$currency], $wc_product->get_price() );

			// Compare formatted price with expected formatted price
			$this->assertEquals( $product['formatted_price'][$currency], $wc_product->get_price_html() );
		}

		$this->multi_currency_support->set_client_currency( $this->default_currency );

		/*
		set_current_screen( 'front' );
		$this->reload_wpml_dependencies( false );

		foreach( $this->languages as $language ){

			$this->sitepress->switch_lang( $language );
			$wc_product = new WC_Product_Simple( $product['post']->id );

			echo $language . " ";
			echo $wc_product->get_price();
			echo "\n";

			//$this->assertEquals( $product['price_on_language'][$language], $wc_product->get_price() );

		}
		*/

	}



}