<?php


class Test_WCML_Product_Prices extends WCML_UnitTestCase {

	private $settings;
	private $multi_currency;
	private $products;
	private $default_currency;
	private $secondary_currencies;

	function setUp(){

		add_filter('wcml_load_multi_currency', '__return_true');
		set_current_screen( 'front' );

		parent::setUp();

		// Multi currency objects
		$this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
		$this->woocommerce_wpml->multi_currency->init();

		// settings
		$this->settings 				=& $this->woocommerce_wpml->settings;
		$this->multi_currency 			=& $this->woocommerce_wpml->multi_currency;

		$this->multi_currency->prices->prices_init();

		// LANGUAGE AND CURRENCIES
		$this->default_currency 	= 'GBP';
		$this->secondary_currencies	= array('USD', 'RON', 'AUD', 'CHF');

		$this->set_up_languages();
		$this->set_up_currencies();

		// PRODUCTS
		$this->products['simple'] 		= $this->add_simple_product('Test Product Simple');
		$this->products['simple_sale'] 	= $this->add_simple_sale_product('Test Product Simple');
		$this->products['variable'] 	= $this->add_variable_product('Test Product Variable');
		$this->products['variable_sale']= $this->add_variable_sale_product('Test Product Variable Sale');

	}

	private function set_up_languages(){

		$this->default_language			= WPML_TEST_LANGUAGE_CODE;
		$this->languages 		= array_map('trim', explode(',', WPML_TEST_LANGUAGE_CODES));

	}

	private function set_up_currencies(){

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
			'auto_subtract'		=> 3,
		);
		// enabled on all languages
		foreach( $this->languages as $cur ){
			$this->settings['currency_options']['USD']['languages'][$cur] = 1;
		}

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
		// enabled on all languages
		foreach( $this->languages as $cur ){
			$this->settings['currency_options']['RON']['languages'][$cur] = 1;
		}


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
		// enabled on all languages
		foreach( $this->languages as $cur ){
			$this->settings['currency_options']['AUD']['languages'][$cur] = 1;
		}


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
		// enabled on all languages
		foreach( $this->languages as $cur ){
			$this->settings['currency_options']['CHF']['languages'][$cur] = 1;
		}


		$this->woocommerce_wpml->update_settings( $this->settings );

	}

	private function wc_format_price( $price ){

		$price_formatted = '<span class="woocommerce-Price-amount amount">';

		$price_formatted .= str_replace(
			array( '<cur>', '</cur>' ),
			array( '<span class="woocommerce-Price-currencySymbol">', '</span>' ),
			$price
		);

		$price_formatted .= '</span>';

		return $price_formatted;
	}

	private function add_simple_product($title){

		$product['title'] = $title;
		$product['price'] = 1234.56;

		$expected['price'] = array(
			'GBP' 	=> $product['price'],
			// according to settings defined in self::set_up_currencies -> round down, rounding increment 10, subtract 3
			// 1234.56 * 1.55 = 1913.568 (rounded down, rounding increment 10) -> 1910 -> 1907 (subtract 3)
			'USD'	=> 1907,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1234.56 * 1.64 =~ 2025 (rounded up, rounding increment 100) -> 2099 (subtract 1)
			'RON'	=> 2099,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1234.56 * 2.45 =~ 3024.672 (rounding disabled, num decimals 1)
			'AUD'	=> 3024.6,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1234.56 * 55 =~ 67900.80 (num decimals 2, rounding disabled)
			'CHF'	=> round( $product['price'] * $this->settings['currency_options']['CHF']['rate'], 2 )
		);

		$expected['formatted'] = array(
			// according to default woocommerce settings: symbol left (no space), ',' thousands separator, '.' decimal separator
			'GBP' => $this->wc_format_price('<cur>&pound;</cur>1,234.56'),
			// according to settings defined in self::set_up_currencies ->
			// symbol left (no space), '#' thousands separator, '@' decimal separator, 4 decimals
			'USD' => $this->wc_format_price('<cur>&#36;</cur>1#907@0000'),
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 0 decimals
			'RON' => $this->wc_format_price('2.099<cur>lei</cur>'),
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w space), '.' thousands separator, ',' decimal separator, 1 decimals
			'AUD' => $this->wc_format_price('3,024.6&nbsp;<cur>&#36;</cur>'),
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 2 decimals
			'CHF' => $this->wc_format_price('67.900,80<cur>&#67;&#72;&#70;</cur>'),
	);

		$expected['price_on_language'] = array(
			'en' => $expected['price']['USD'], // 'USD' price according to settings defined in self::set_up_currencies
			'de' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'fr' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'es' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'ru' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'it' => $expected['price']['AUD'], // 'AUD' price according to settings defined in self::set_up_currencies
		);

		$product['expected'] = $expected;

		$product['post'] = $this->wcml_helper->add_product( $this->default_language , false, $product['title'], 0,
							array( '_price' => $product['price'], '_regular_price' => $product['price'] ) );

		return $product;

	}

	private function add_simple_sale_product($title){

		$product['title'] = $title;
		$product['price'] = 1234.56;
		$product['sale_price'] = 1002.34;

		$expected['price'] = array(
			'GBP' 	=> $product['sale_price'],
			// according to settings defined in self::set_up_currencies -> round down, rounding increment 10, subtract 3
			// 1002.34 * 1.55 = 1553.627 (rounded down, rounding increment 10) -> 1550 -> 1547 (subtract 3)
			'USD'	=> 1547,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1002.34 * 1.64 =~ 1643.8376 (rounded up, rounding increment 100) -> 1699 (subtract 1)
			'RON'	=> 1699,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1002.34 * 2.45 = 2455.7 (rounding disabled, num decimals 1)
			'AUD'	=> 2455.7,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1002.34 * 55 = 55128.7 (num decimals 2, rounding disabled)
			'CHF'	=> round( $product['sale_price'] * $this->settings['currency_options']['CHF']['rate'], 2 )
		);

		$expected['formatted'] = array(
			// according to default woocommerce settings: symbol left (no space), ',' thousands separator, '.' decimal separator
			'GBP' => '<del>' . $this->wc_format_price('<cur>&pound;</cur>1,234.56') . '</del> <ins>' . $this->wc_format_price('<cur>&pound;</cur>1,002.34') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol left (no space), '#' thousands separator, '@' decimal separator, 4 decimals
			'USD' => '<del>' . $this->wc_format_price('<cur>&#36;</cur>1#907@0000') . '</del> <ins>' . $this->wc_format_price('<cur>&#36;</cur>1#547@0000') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 0 decimals
			'RON' => '<del>' . $this->wc_format_price('2.099<cur>lei</cur>') .'</del> <ins>' . $this->wc_format_price('1.699<cur>lei</cur>') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w space), '.' thousands separator, ',' decimal separator, 1 decimals
			'AUD' => '<del>' . $this->wc_format_price('3,024.6&nbsp;<cur>&#36;</cur>') . '</del> <ins>' . $this->wc_format_price('2,455.7&nbsp;<cur>&#36;</cur>') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 2 decimals
			'CHF' => '<del>' . $this->wc_format_price('67.900,80<cur>&#67;&#72;&#70;</cur>') .'</del> <ins>' . $this->wc_format_price('55.128,70<cur>&#67;&#72;&#70;</cur>') .'</ins>'
		);

		$expected['price_on_language'] = array(
			'en' => $expected['price']['USD'], // 'USD' price according to settings defined in self::set_up_currencies
			'de' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'fr' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'es' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'ru' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'it' => $expected['price']['AUD']  // 'AUD' price according to settings defined in self::set_up_currencies
		);

		$product['expected'] = $expected;

		$product['post'] = $this->wcml_helper->add_product( $this->default_language , false, $product['title'], 0,
			array(
					'_price' 			=> $product['sale_price'],
					'_regular_price' 	=> $product['price'],
					'_sale_price'		=> $product['sale_price']
			) );

		return $product;

	}

	private function add_variable_product($title){

		// 2 variations: white & black. Prices: 10.06 and respectively 15.99

		WCML_Helper::register_attribute( 'color' );
		$white = WCML_Helper::add_attribute_term( 'White', 'color', $this->sitepress->get_default_language() );
		$black = WCML_Helper::add_attribute_term( 'Black', 'color', $this->sitepress->get_default_language() );
		$variation_data = array(

			'product_title' => $title,

			'attribute' => array(
				'name' => 'pa_color'
			),

			'variations' => array(
				'white' => array(
					'price'     => 10.06,
					'regular'   => 10.06
				),
				'black' => array(
					'price'     => 15.99,
					'regular'   => 15.99
				)
			)
		);

		$product['post'] = $this->wcml_helper->add_variable_product( $variation_data );

		foreach( $variation_data['variations'] as $vp ){
			if( !isset($min_variation_price) || $min_variation_price > $vp['price'] ){
				$min_variation_price = $vp['price'];
			}
			if( !isset($max_variation_price) || $max_variation_price < $vp['price'] ){
				$max_variation_price = $vp['price'];
			}
			if( !isset($min_variation_regular_price) || $min_variation_regular_price > $vp['regular'] ){
				$min_variation_regular_price = $vp['regular'];
			}
			if( !isset($max_variation_regular_price) || $max_variation_regular_price < $vp['regular'] ){
				$max_variation_regular_price = $vp['regular'];
			}
		}


		$expected['price'] = array(
			'GBP' 	=> $min_variation_price,
			// according to settings defined in self::set_up_currencies -> round down, rounding increment 10, subtract 3
			// 10.06 * 1.55 = 15.593 (rounded down, rounding increment 10) -> 10 -> 7 (subtract 3)
			'USD'	=> 7,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 10.06 * 1.64 = 16.4984 (rounded up, rounding increment 100) -> 100 -> 99 (subtract 1)
			'RON'	=> 99,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 10.06 * 2.45 =~ 24.6 (rounding disabled, num decimals 1)
			'AUD'	=> 24.6,

			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 10.06 * 55 = 553.3 (num decimals 2, rounding disabled)
			'CHF'	=> round( $min_variation_price * $this->settings['currency_options']['CHF']['rate'], 2 )
		);

		$expected['formatted'] = array(
			// according to default woocommerce settings: symbol left (no space), ',' thousands separator, '.' decimal separator
			'GBP' => $this->wc_format_price( '<cur>&pound;</cur>10.06' ) . '&ndash;' . $this->wc_format_price( '<cur>&pound;</cur>15.99' ) ,
			// according to settings defined in self::set_up_currencies ->
			// symbol left (no space), '#' thousands separator, '@' decimal separator, 4 decimals
			'USD' => $this->wc_format_price( '<cur>&#36;</cur>7@0000' ) . '&ndash;' . $this->wc_format_price( '<cur>&#36;</cur>17@0000' ) ,
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 0 decimals
			'RON' => $this->wc_format_price( '99<cur>lei</cur>' ),
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w space), '.' thousands separator, ',' decimal separator, 1 decimals
			'AUD' => $this->wc_format_price( '24.6&nbsp;<cur>&#36;</cur>' ) . '&ndash;' . $this->wc_format_price( '39.1&nbsp;<cur>&#36;</cur>' ),
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 2 decimals
			'CHF' => $this->wc_format_price( '553,30<cur>&#67;&#72;&#70;</cur>' ) . '&ndash;' . $this->wc_format_price( '879,45<cur>&#67;&#72;&#70;</cur>' ),
		);

		$expected['price_on_language'] = array(
			'en' => $expected['price']['USD'], // 'USD' price according to settings defined in self::set_up_currencies
			'de' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'fr' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'es' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'ru' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'it' => $expected['price']['AUD']  // 'AUD' price according to settings defined in self::set_up_currencies
		);

		$product['expected'] = $expected;

		return $product;

	}

	private function add_variable_sale_product($title){

		WCML_Helper::register_attribute( 'size' );
		$white = WCML_Helper::add_attribute_term( 'Small', 'size', $this->sitepress->get_default_language() );
		$black = WCML_Helper::add_attribute_term( 'Big', 'size', $this->sitepress->get_default_language() );
		$variation_data = array(

			'product_title' => $title,

			'attribute' => array(
				'name' => 'pa_size'
			),

			'variations' => array(
				'small' => array(
					'price'     => 1000,
					'regular'   => 1100.33,
					'sale'   	=> 1000
				),
				'big' => array(
					'price'     => 2000,
					'regular'   => 2100.44,
					'sale'   	=> 2000
				)
			)
		);

		$product['post'] = $this->wcml_helper->add_variable_product( $variation_data );

		foreach( $variation_data['variations'] as $vp ){
			if( !isset($min_variation_price) || $min_variation_price > $vp['price'] ){
				$min_variation_price = $vp['price'];
			}
			if( !isset($max_variation_price) || $max_variation_price < $vp['price'] ){
				$max_variation_price = $vp['price'];
			}
			if( !isset($min_variation_regular_price) || $min_variation_regular_price > $vp['regular'] ){
				$min_variation_regular_price = $vp['regular'];
			}
			if( !isset($max_variation_regular_price) || $max_variation_regular_price < $vp['regular'] ){
				$max_variation_regular_price = $vp['regular'];
			}

		}

		$expected['price'] = array(
			'GBP' 	=> $min_variation_price,
			// according to settings defined in self::set_up_currencies -> round down, rounding increment 10, subtract 3
			// 1000 * 1.55 = 1550 (rounded down, rounding increment 10) -> 1550 -> 1547 (subtract 3)
			'USD'	=> 1547,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1000 * 1.64 = 1640 (rounded up, rounding increment 100) -> 1700 -> 1699 (subtract 1)
			'RON'	=> 1699,
			// according to settings defined in self::set_up_currencies -> round up, rounding increment 100, subtract 1
			// 1000 * 55 = 55000 (num decimals 2, rounding disabled)
			'AUD'	=> 2450,
			// according to settings defined in self::set_up_currencies -> rounding disabled
			// 1100.33 * 55 = 60518.15 (num decimals 2, rounding disabled)
			'CHF'	=> round( $min_variation_price * $this->settings['currency_options']['CHF']['rate'], 2 )
		);


		$expected['formatted'] = array(
			// according to default woocommerce settings: symbol left (no space), ',' thousands separator, '.' decimal separator
			'GBP' => '<del>' .
				$this->wc_format_price('<cur>&pound;</cur>1,100.33') . '&ndash;' .
				$this->wc_format_price('<cur>&pound;</cur>2,100.44') . '</del> <ins>' .
				$this->wc_format_price('<cur>&pound;</cur>1,000.00') . '&ndash;' .
				$this->wc_format_price('<cur>&pound;</cur>2,000.00') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol left (no space), '#' thousands separator, '@' decimal separator, 4 decimals
			'USD' => '<del>' .
				$this->wc_format_price('<cur>&#36;</cur>1#697@0000') . '&ndash;' .
				$this->wc_format_price('<cur>&#36;</cur>3#247@0000') . '</del> <ins>' .
				$this->wc_format_price('<cur>&#36;</cur>1#547@0000') . '&ndash;' .
				$this->wc_format_price('<cur>&#36;</cur>3#097@0000') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 0 decimals
			'RON' => '<del>' .
				$this->wc_format_price('1.899<cur>lei</cur>') . '&ndash;' .
				$this->wc_format_price('3.499<cur>lei</cur>') . '</del> <ins>' .
				$this->wc_format_price('1.699<cur>lei</cur>') . '&ndash;' .
				$this->wc_format_price('3.299<cur>lei</cur>') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w space), '.' thousands separator, ',' decimal separator, 1 decimals
			'AUD' => '<del>' .
				$this->wc_format_price('2,695.8&nbsp;<cur>&#36;</cur>') . '&ndash;' .
				$this->wc_format_price('5,146.0&nbsp;<cur>&#36;</cur>') . '</del> <ins>' .
				$this->wc_format_price('2,450.0&nbsp;<cur>&#36;</cur>') . '&ndash;' .
				$this->wc_format_price('4,900.0&nbsp;<cur>&#36;</cur>') . '</ins>',
			// according to settings defined in self::set_up_currencies ->
			// symbol right (w/ space), '.' thousands separator, ',' decimal separator, 2 decimals
			'CHF' => '<del>' .
				$this->wc_format_price('60.518,15<cur>&#67;&#72;&#70;</cur>') . '&ndash;' .
				$this->wc_format_price('115.524,20<cur>&#67;&#72;&#70;</cur>') . '</del> <ins>' .
				$this->wc_format_price('55.000,00<cur>&#67;&#72;&#70;</cur>') . '&ndash;' .
				$this->wc_format_price('110.000,00<cur>&#67;&#72;&#70;</cur>') . '</ins>'
		);

		$expected['price_on_language'] = array(
			'en' => $expected['price']['USD'], // 'USD' price according to settings defined in self::set_up_currencies
			'de' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'fr' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'es' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'ru' => $expected['price']['RON'], // keep previous according to settings defined in self::set_up_currencies
			'it' => $expected['price']['AUD']  // 'AUD' price according to settings defined in self::set_up_currencies
		);

		$product['expected'] = $expected;

		return $product;

	}

	private function run_product_test( $product, $wc_product_type ){

		$currencies = array_merge( array($this->default_currency), $this->secondary_currencies );

		foreach( $currencies as $currency){

			$this->multi_currency->set_client_currency( $currency );
			$wc_product = new $wc_product_type( $product['post']->id );

			// Compare amount with expected amounts
			$this->assertEquals( $product['expected']['price'][$currency], $wc_product->get_price() );

			// Compare formatted price with expected formatted price
			$this->assertEquals( $product['expected']['formatted'][$currency], $wc_product->get_price_html() );
		}


		foreach( $this->languages as $language ){

			$this->switch_language( $language );

			$wc_product = new $wc_product_type( $product['post']->id );

			// Compare price with expected price for language
			$this->assertEquals( $product['expected']['price_on_language'][$language], $wc_product->get_price() );

		}

		// Switch currency on each language
		foreach( $this->languages as $language ){

			$this->switch_language( $language );

			foreach( $currencies as $currency){

				$this->multi_currency->set_client_currency( $currency );
				$wc_product = new $wc_product_type( $product['post']->id );

				// Compare amount with expected amounts
				$this->assertEquals( $product['expected']['price'][$currency], $wc_product->get_price() );

				// Compare formatted price with expected formatted price
				$this->assertEquals( $product['expected']['formatted'][$currency], $wc_product->get_price_html() );
			}

		}

	}

	// Check prices for a simple product, for different currencies and different languages
	public function test_simple_product(){

		$this->run_product_test( $this->products['simple'], 'WC_Product_Simple' );

	}

	// Check prices for a simple product on sale, for different currencies and different languages
	public function test_simple_sale_product(){

		$this->run_product_test( $this->products['simple_sale'], 'WC_Product_Simple' );

	}

	// Check prices for a variable product, for different currencies and different languages
	public function test_variable_product(){

		$this->run_product_test( $this->products['variable'], 'WC_Product_Variable' );

	}

	// Check prices for a variable product on sale, for different currencies and different languages
	public function test_variable_sale_product(){

		$this->run_product_test( $this->products['variable_sale'], 'WC_Product_Variable' );

	}

	// Operations that simulate switching to a different language
	private function switch_language( $language ){
		global $woocommerce;

		$this->sitepress->switch_lang( $language );

		$client_currency = $this->multi_currency->get_client_currency();
		$this->multi_currency->set_client_currency(null);

		$woocommerce->session->set('client_currency_language', '');
		$woocommerce->session->save_data();

		if( empty( $this->settings['default_currencies'][$language] ) ){
			$this->multi_currency->set_client_currency( $client_currency );
		}

	}


}