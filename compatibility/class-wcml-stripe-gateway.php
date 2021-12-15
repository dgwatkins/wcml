<?php

namespace WCML\Compatibility;

class Stripe_Gateway {
	/**
	 * @var \woocommerce_wpml
	 */
	private $woocommerce_wpml;

	const PRIORITY = 10;

	public function __construct( \woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		add_action( 'woocommerce_admin_order_totals_after_total', [ $this, 'suspendCurrencySymbolFilter' ], self::PRIORITY - 1 );
	}

	public function suspendCurrencySymbolFilter() {
		if ( remove_filter( 'woocommerce_currency_symbol', [ $this->woocommerce_wpml->multi_currency->orders, '_use_order_currency_symbol' ] ) ) {
			add_action(
				'woocommerce_admin_order_totals_after_total',
				function() {
					add_filter( 'woocommerce_currency_symbol', [ $this->woocommerce_wpml->multi_currency->orders, '_use_order_currency_symbol' ] );
				},
				self::PRIORITY + 100
			);
		}
	}
}
