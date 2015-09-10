<?php

class Test_WCML_Multi_Currency extends WCML_UnitTestCase {

	function __construct(){

		// set up
		global $woocommerce_wpml;


		// settings
		$settings = $woocommerce_wpml->settings;

		$settings['enable_multi_currency'] = 2;

		$settings['default_currencies'] = array( 'en' => 'USD', 'de' => 'RON', 'it' => 'AUD'  );

		$settings['currency_options']['USD'] = array(

			'rate' 				=> 1.55,
			'position'			=> 'left',
			'thousand_sep'		=> '#',
			'decimal_sep'		=> '@',
			'num_decimals'		=> 4,
			'rounding'			=> 'down',
			'rounding_increment'=> 10,
			'auto_subtract'		=> 3

		);

		$settings['currency_options']['RON'] = array(

			'rate' 				=> 1.64,
			'position'			=> 'right',
			'thousand_sep'		=> '.',
			'decimal_sep'		=> ',',
			'num_decimals'		=> 0,
			'rounding'			=> 'up',
			'rounding_increment'=> 100,
			'auto_subtract'		=> 1

		);

		$settings['currency_options']['AUD'] = array(

			'rate' 				=> 2.45,
			'position'			=> 'right',
			'thousand_sep'		=> '.',
			'decimal_sep'		=> ',',
			'num_decimals'		=> 1,
			'rounding'			=> 'disabled',
			'rounding_increment'=> 0,
			'auto_subtract'		=> 0

		);

		$woocommerce_wpml->update_settings( $settings );

		// Multi currency objects
		require_once WCML_PLUGIN_PATH . '/inc/multi-currency-support.class.php';
		$woocommerce_wpml->multi_currency_support = new WCML_Multi_Currency_Support;

		$this->multi_currency_support =& $woocommerce_wpml->multi_currency_support;

		require_once WCML_PLUGIN_PATH . '/inc/multi-currency.class.php';
		$woocommerce_wpml->multi_currency = new WCML_WC_MultiCurrency();

		$this->multi_currency =& $woocommerce_wpml->multi_currency;
	}


	function test_raw_price_filter() {

		//AUD No rounding, exch rate: 2.45, 1 decimal
		$this->assertEquals( 7.35, $this->multi_currency->raw_price_filter(3, 'AUD') );

		//RON Round up, exch rate: 1.64, 0 decimals
		$this->assertEquals( 799, $this->multi_currency->raw_price_filter(434, 'RON') );

		//USD Round down, exch rate: 1.5.5, 0 decimals, round incr 10, rond subt 3
		$this->assertEquals( 697, $this->multi_currency->raw_price_filter(456, 'USD') );

	}

	function test_apply_rounding_rules() {

		$this->assertEquals( 12337, $this->multi_currency->apply_rounding_rules(12345, 'USD') );

		$this->assertEquals( 12399, $this->multi_currency->apply_rounding_rules(12345, 'RON') );

		$this->assertEquals( 123.37, $this->multi_currency->apply_rounding_rules(123.37, 'AUD') ); //disabled
	}


	function test_formatted_price(){

		//convert + round + decimals

		$this->assertEquals( '<span class="amount">&#36;1#907@0000</span>',
				$this->multi_currency->formatted_price(1234.137, 'USD') );

		$this->assertEquals( '<span class="amount">2.099lei</span>',
			$this->multi_currency->formatted_price(1234.137, 'RON') );

		$this->assertEquals( '<span class="amount">3.023,6&#36;</span>',
			$this->multi_currency->formatted_price(1234.137, 'AUD') );

	}




}