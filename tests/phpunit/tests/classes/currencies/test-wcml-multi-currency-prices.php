<?php

/**
 * Class Test_WCML_Multi_Currency_Prices
 */
class Test_WCML_Multi_Currency_Prices extends OTGS_TestCase {

	private function get_subject( $multi_currency ){
		return new WCML_Multi_Currency_Prices( $multi_currency );
	}

	private function get_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function add_hooks_filters_not_loaded() {

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->load_filters = false;

		$subject = $this->get_subject( $multi_currency );

		\WP_Mock::expectFilterAdded( 'wcml_raw_price_amount', array( $subject, 'raw_price_filter' ), 10, 2 );

		\WP_Mock::expectFilterAdded( 'option_woocommerce_price_thousand_sep', array(
			$subject,
			'filter_currency_thousand_sep_option'
		) );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_price_decimal_sep', array(
			$subject,
			'filter_currency_decimal_sep_option'
		) );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_price_num_decimals', array(
			$subject,
			'filter_currency_num_decimals_option'
		) );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_currency_pos', array(
			$subject,
			'filter_currency_position_option'
		) );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'save_order_currency_for_filter' ), 10, 4 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_filters_loaded() {

		$multi_currency = $this->get_multi_currency_mock();
		$multi_currency->load_filters = true;

		$subject = $this->get_subject( $multi_currency );

		\WP_Mock::expectFilterAdded( 'init', array( $subject, 'prices_init' ), 5 );

		\WP_Mock::expectFilterAdded( 'woocommerce_currency', array( $subject, 'currency_filter' ) );

		\WP_Mock::expectFilterAdded( 'wcml_price_currency', array( $subject, 'price_currency_filter' ) );      // WCML filters
		\WP_Mock::expectFilterAdded( 'wcml_product_price_by_currency', array(
			$subject,
			'get_product_price_in_currency'
		), 10, 2 );  // WCML filters


		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'product_price_filter' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'variation_prices_filter' ), 12, 4 ); // second

		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_max_amount', array( $subject, 'raw_price_filter' ), 99 );
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_min_amount', array( $subject, 'raw_price_filter' ), 99 );

		\WP_Mock::expectFilterAdded( 'woocommerce_adjust_price', array( $subject, 'raw_price_filter' ), 10 );

		\WP_Mock::expectFilterAdded( 'wcml_formatted_price', array( $subject, 'formatted_price' ), 10, 2 ); // WCML filters

		// Shipping prices
		\WP_Mock::expectFilterAdded( 'woocommerce_paypal_args', array( $subject, 'filter_price_woocommerce_paypal_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_get_variation_prices_hash', array(
			$subject,
			'add_currency_to_variation_prices_hash'
		) );
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_contents_total', array(
			$subject,
			'filter_woocommerce_cart_contents_total'
		), 100 );
		\WP_Mock::expectFilterAdded( 'woocommerce_cart_subtotal', array( $subject, 'filter_woocommerce_cart_subtotal' ), 100, 3 );

		//filters for wc-widget-price-filter
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_results', array( $subject, 'filter_price_filter_results' ), 10, 3 );
		\WP_Mock::expectFilterAdded( 'woocommerce_price_filter_widget_amount', array( $subject, 'filter_price_filter_widget_amount' ) );

		\WP_Mock::expectActionAdded( 'woocommerce_cart_loaded_from_session', array(
			$subject,
			'filter_currency_num_decimals_in_cart'
		) );

		\WP_Mock::expectFilterAdded( 'wc_price_args', array( $subject, 'filter_wc_price_args' ) );

		$subject->add_hooks();
	}

}
