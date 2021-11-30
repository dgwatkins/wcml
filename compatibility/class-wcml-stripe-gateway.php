<?php

namespace WCML\Compatibility;

class Stripe_Gateway {
	/**
	 * @var \woocommerce_wpml
	 */
	private $woocommerce_wpml;

	/**
	 * @var bool
	 */
	private static $currency_filter_deregistered = false;

	public function __construct( \woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		add_action( 'woocommerce_admin_order_totals_after_total', [ $this, 'deregister_currency_symbol_filter' ], 1 );
		add_action( 'woocommerce_admin_order_totals_after_total', [ $this, 'register_currency_symbol_filter' ], 100 );
	}

	public function deregister_currency_symbol_filter() {
		if ( isset( $this->woocommerce_wpml->multi_currency->orders ) ) {
			self::$currency_filter_deregistered = \remove_filter( 'woocommerce_currency_symbol', [ $this->woocommerce_wpml->multi_currency->orders, '_use_order_currency_symbol' ] );
		}
	}

	public function register_currency_symbol_filter() {
		if ( self::$currency_filter_deregistered && isset( $this->woocommerce_wpml->multi_currency->orders ) ) {
			add_filter( 'woocommerce_currency_symbol', [ $this->woocommerce_wpml->multi_currency->orders, '_use_order_currency_symbol' ] );
		}
	}
}
