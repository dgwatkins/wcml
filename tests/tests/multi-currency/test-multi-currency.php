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

		$this->settings = $settings;

		$this->woocommerce_wpml->update_settings( $settings );

		// Multi currency objects
		$this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
		$this->multi_currency = $this->woocommerce_wpml->multi_currency;
	}


	function test_get_client_currency(){
		$this->check_language_default_currency();
		$this->check_switch_currency_exception();
		$this->check_order_currency();
	}

	function check_language_default_currency(){
		$current_language = $this->sitepress->get_current_language();
		$second_language = 'de';

		$this->multi_currency->set_client_currency( NULL );
		$this->sitepress->switch_lang( $second_language );
		$client_currency = $this->multi_currency->get_client_currency();
		$this->assertSame( $this->settings[ 'default_currencies' ][ $second_language ], $client_currency );
	}

	function check_switch_currency_exception(){

		$current_language = $this->sitepress->get_current_language();
		$second_language = 'de';
		$wc_default_currency = wcml_get_woocommerce_currency_option();

		$this->multi_currency->set_client_currency( NULL );
		$this->sitepress->switch_lang( $current_language );
		add_filter( 'wcml_switch_currency_exception', '__return_true');
		$client_currency = $this->multi_currency->get_client_currency();
		$this->assertEquals( $wc_default_currency, $client_currency );
	}

	function check_order_currency(){
		//test order currency when add product to order on order edit page
		$order = wp_insert_post( array( 'title'=> 'TEST Order', 'post_type' => 'shop_order' ) );
		update_post_meta( $order, '_order_currency','CHF' );
		$_SERVER['HTTP_REFERER'] = 'wp-admin/post.php?post='.$order.'&action=edit';
		$curr = $this->multi_currency->get_client_currency();
		$this->assertEquals( 'CHF', $curr );

		//get default currency during REST API call #wcml-1961
		$req_uri = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/';
		$curr = $this->multi_currency->get_client_currency();
		$this->assertEquals( wcml_get_woocommerce_currency_option(), $curr );
		$_SERVER['REQUEST_URI'] = $req_uri;
	}

	function test_raw_price_filter() {

		//AUD No rounding, exch rate: 2.45, 1 decimal
		$this->assertEquals( 7.3, $this->multi_currency->prices->raw_price_filter(3, 'AUD') );
		$this->assertEquals( 7.3, wcml_convert_price(3, 'AUD') );

		//RON Round up, exch rate: 1.64, 0 decimals
		$this->assertEquals( 799, $this->multi_currency->prices->raw_price_filter(434, 'RON') );
		$this->assertEquals( 799, wcml_convert_price(434, 'RON') );

		//USD Round down, exch rate: 1.5.5, 0 decimals, round incr 10, round subt 3
		$this->assertEquals( 697, $this->multi_currency->prices->raw_price_filter(456, 'USD') );
		$this->assertEquals( 697, wcml_convert_price(456, 'USD') );

	}

	function test_apply_rounding_rules() {

		$this->assertEquals( 12337, $this->multi_currency->prices->apply_rounding_rules(12345, 'USD') );

		$this->assertEquals( 12399, $this->multi_currency->prices->apply_rounding_rules(12345, 'RON') );

		$this->assertEquals( 123.3, $this->multi_currency->prices->apply_rounding_rules(123.37, 'AUD') ); //disabled
	}


	function test_formatted_price(){

		//convert + round + decimals
		
		$span_price 	= '<span class="woocommerce-Price-amount amount">';
		$span_currency	= '<span class="woocommerce-Price-currencySymbol">';
		$span_close		= '</span>';

		$this->assertEquals( $span_price . $span_currency . '&#36;' . $span_close . '1#907@0000' . $span_close,
				$this->multi_currency->prices->formatted_price(1234.137, 'USD') );

		$this->assertEquals( $span_price . '2.099' . $span_currency .'lei' . $span_close . $span_close,
			$this->multi_currency->prices->formatted_price(1234.137, 'RON') );

		$this->assertEquals( $span_price .'3.023,6' . $span_currency . '&#36;' . $span_close . $span_close,
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

		$args = array(
			'code'              => 'dummycoupon',
			'amount'            => 1,
			'minimum_amount'    => 3,
			'maximum_amount'    => 17,
		);

		remove_action('woocommerce_coupon_loaded', array($this->multi_currency->coupons, 'filter_coupon_data'));
		$coupon = WCML_Helper_Coupon::create_coupon( $args );

		$this->multi_currency->set_client_currency('CHF');

		add_action('woocommerce_coupon_loaded', array($this->multi_currency->coupons, 'filter_coupon_data'));
		$coupon_converted = new WC_Coupon( $args['code'] );

		if( method_exists( $coupon, 'get_amount') ){ // WC 2.7+
			$coupon_amount_converted   = $coupon_converted->get_amount();
			$minimum_amount_converted  = $coupon_converted->get_minimum_amount();
			$maximum_amount_converted  = $coupon_converted->get_maximum_amount();
		} else {
			$coupon_amount_converted   = $coupon_converted->coupon_amount;
			$minimum_amount_converted  = $coupon_converted->minimum_amount;
			$maximum_amount_converted  = $coupon_converted->maximum_amount;
		}

		$rate = $this->settings['currency_options']['CHF']['rate'];

		$this->assertEquals( $rate * $args['amount'],           $coupon_amount_converted );
		$this->assertEquals( $rate * $args['minimum_amount'],   $minimum_amount_converted );
		$this->assertEquals( $rate * $args['maximum_amount'],   $maximum_amount_converted );

	}
	
	// test converting a price
	public function test_convert_price_amount(){

		// Using the currency explicitly
		$amount = 100;
		$currency = 'RON';
		$converted = $this->multi_currency->prices->convert_price_amount( $amount, $currency);
		$expected  = $amount * $this->settings['currency_options'][$currency]['rate'];

		$this->assertEquals( $expected , $converted );

		// Not using the currency explicitly

		$amount = 100;
		$currency = 'AUD';
		$this->multi_currency->set_client_currency( $currency );
		$converted = $this->multi_currency->prices->convert_price_amount( $amount );
		$expected  = $amount * $this->settings['currency_options'][$currency]['rate'];

		$this->assertEquals( $expected , $converted );

	}

	// test unconverting a price
	public function test_unconvert_price_amount(){

		// Using the currency explicitly
		$amount = 164;
		$currency = 'RON';
		$converted = $this->multi_currency->prices->unconvert_price_amount( $amount, $currency);
		$expected  = $amount / $this->settings['currency_options'][$currency]['rate'];

		$this->assertEquals( $expected , $converted );

		// Not using the currency explicitly
		$amount = 730;
		$currency = 'AUD';
		$this->multi_currency->set_client_currency( $currency );
		$converted = $this->multi_currency->prices->unconvert_price_amount( $amount );
		$expected  = $amount / $this->settings['currency_options'][$currency]['rate'];

		$this->assertEquals( $expected , $converted );

	}

	public function test_save_order_currency_for_filter(){

		//add order post and set currency for it
		$order_id = wpml_test_insert_post( $this->sitepress->get_default_language(), 'shop_order', false, rand_str() );
		add_post_meta( $order_id, '_order_currency', $this->settings[ 'default_currencies' ][ $this->sitepress->get_default_language() ] );

		$_GET[ 'post_type' ] = 'shop_order';
		$order_currency = get_post_meta( $order_id, '_order_currency',true );

		$this->assertEquals( $order_currency, $this->multi_currency->prices->get_admin_order_currency_code() );

	}

}