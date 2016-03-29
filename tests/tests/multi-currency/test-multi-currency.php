<?php

class Test_WCML_Multi_Currency extends WCML_UnitTestCase {

	private $settings;
	private $multi_currency;

	function setUp(){

		parent::setUp();

		// settings
		$settings = $this->woocommerce_wpml->settings;
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
		$settings['currency_options']['CHF'] = array(

			'rate' 				=> 55,
			'position'			=> 'right',
			'thousand_sep'		=> '.',
			'decimal_sep'		=> ',',
			'num_decimals'		=> 2,
			'rounding'			=> 'disabled',
			'rounding_increment'=> 0,
			'auto_subtract'		=> 0
		);

		$this->settings =& $settings;

		$this->woocommerce_wpml->update_settings( $settings );

		// Multi currency objects
		$this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
		$this->multi_currency =& $this->woocommerce_wpml->multi_currency;

		$this->multi_currency->prices->prices_init();
	}


	function test_raw_price_filter() {

		//AUD No rounding, exch rate: 2.45, 1 decimal
		$this->assertEquals( 7.3, $this->multi_currency->prices->raw_price_filter(3, 'AUD') );

		//RON Round up, exch rate: 1.64, 0 decimals
		$this->assertEquals( 799, $this->multi_currency->prices->raw_price_filter(434, 'RON') );

		//USD Round down, exch rate: 1.5.5, 0 decimals, round incr 10, round subt 3
		$this->assertEquals( 697, $this->multi_currency->prices->raw_price_filter(456, 'USD') );

	}

	function test_apply_rounding_rules() {

		$this->assertEquals( 12337, $this->multi_currency->prices->apply_rounding_rules(12345, 'USD') );

		$this->assertEquals( 12399, $this->multi_currency->prices->apply_rounding_rules(12345, 'RON') );

		$this->assertEquals( 123.3, $this->multi_currency->prices->apply_rounding_rules(123.37, 'AUD') ); //disabled
	}


	function test_formatted_price(){

		//convert + round + decimals

		$this->assertEquals( '<span class="amount">&#36;1#907@0000</span>',
				$this->multi_currency->prices->formatted_price(1234.137, 'USD') );

		$this->assertEquals( '<span class="amount">2.099lei</span>',
			$this->multi_currency->prices->formatted_price(1234.137, 'RON') );

		$this->assertEquals( '<span class="amount">3.023,6&#36;</span>',
			$this->multi_currency->prices->formatted_price(1234.137, 'AUD') );

	}


	function test_filter_price_woocommerce_paypal_args(){

		$arg = array( 'amount_1' => '12.78', 'currency_code' =>  'RON' );

		$arg = $this->multi_currency->prices->filter_price_woocommerce_paypal_args( $arg );

		$this->assertEquals( 13, $arg['amount_1'] );

		$arg = array( 'amount_1' => '12.78', 'currency_code' =>  'AUD' );

		$arg = $this->multi_currency->prices->filter_price_woocommerce_paypal_args( $arg );

		$this->assertEquals( 12.8, $arg['amount_1'] );

	}

	// test converting the coupon amount when using the multi currency mode
	function test_filter_coupon_data(){

		$coupon = WCML_Helper_Coupon::create_coupon();

		$coupon_amount	 = $coupon->coupon_amount;
		$minimum_amount  = $coupon->minimum_amount;
		$maximum_amount  = $coupon->maximum_amount;

		add_filter('wcml_raw_price_amount', array($this->multi_currency->prices, 'raw_price_filter'), 10, 2);
		$this->multi_currency->set_client_currency('CHF');

		$this->multi_currency->coupons->filter_coupon_data($coupon);

		$rate = $this->settings['currency_options']['CHF']['rate'];

		$this->assertEquals( $coupon->coupon_amount,  $rate * $coupon_amount );
		$this->assertEquals( $coupon->minimum_amount, $rate * $minimum_amount );
		$this->assertEquals( $coupon->maximum_amount, $rate * $maximum_amount );

	}

}