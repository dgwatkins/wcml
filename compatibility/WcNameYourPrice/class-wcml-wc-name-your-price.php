<?php

/**
 * WooCommerce Name Your Price compatibility.
 *
 * @version 4.12.2
 */
class WCML_WC_Name_Your_Price implements \IWPML_Action {

	/**
	 * @var SitePress
	 */
	public $sitepress;
	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;
	/**
	 * @var int
	 */
	private $multi_currency_mode;

	/**
	 * WCML_WC_Name_Your_Price constructor.
	 *
	 * @param SitePress        $sitepress
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( SitePress $sitepress, woocommerce_wpml $woocommerce_wpml ) {
		$this->sitepress           = $sitepress;
		$this->woocommerce_wpml    = $woocommerce_wpml;
		$this->multi_currency_mode = $woocommerce_wpml->settings['enable_multi_currency'];
	}

	public function add_hooks() {
		// Price filters.
		if ( ! is_admin() ) {
			if ( is_callable( [ 'WC_Name_Your_Price_Compatibility', 'is_nyp_gte' ] ) && WC_Name_Your_Price_Compatibility::is_nyp_gte( '3.0' ) ) {
				add_filter( 'wc_nyp_raw_suggested_price', [ $this, 'product_price_filter' ], 10, 2 );
				add_filter( 'wc_nyp_raw_minimum_price', [ $this, 'product_price_filter' ], 10, 2 );
				add_filter( 'wc_nyp_raw_maximum_price', [ $this, 'product_price_filter' ], 10, 2 );
			} else {
				add_filter( 'woocommerce_raw_suggested_price', [ $this, 'product_price_filter' ], 10, 2 );
				add_filter( 'woocommerce_raw_minimum_price', [ $this, 'product_price_filter' ], 10, 2 );
				add_filter( 'woocommerce_raw_maximum_price', [ $this, 'product_price_filter' ], 10, 2 );
			}
		}

		// Cart currency switching.
		if ( $this->is_multi_currency_on() ) {
			add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_initial_currency' ] );
			add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'filter_woocommerce_get_cart_item_from_session' ], 20, 2 );
		}
	}

	public function product_price_filter( $price, $product ) {
		return apply_filters( 'wcml_raw_price_amount', $price );
	}

	/**
	 * @return bool
	 */
	private function is_multi_currency_on() {
		return $this->multi_currency_mode === $this->sitepress->get_wp_api()->constant( 'WCML_MULTI_CURRENCIES_INDEPENDENT' );
	}

	/**
	 * Store the initial currency when item is added.
	 *
	 * @param array $cart_item_data The Cart Item data.
	 *
	 * @return array
	 * @since 4.12.2
	 *
	 */
	public function add_initial_currency( $cart_item_data ) {

		if ( isset( $cart_item_data['nyp'] ) ) {
			$cart_item_data['nyp_currency'] = get_woocommerce_currency();
			$cart_item_data['nyp_original'] = $cart_item_data['nyp'];
		}

		return $cart_item_data;
	}

	/**
	 * Filter Name Your Price Cart prices.
	 *
	 * @param array $session_data The Session data.
	 * @param array $values       The values.
	 *
	 * @return array
	 *
	 * @since    4.12.2
	 *
	 * @internal filter.
	 */
	public function filter_woocommerce_get_cart_item_from_session( $session_data, $values ) {

		// Preserve original currency.
		if ( isset( $values['nyp_currency'] ) ) {
			$session_data['nyp_currency'] = $values['nyp_currency'];
		}

		// Preserve original entered value.
		if ( isset( $values['nyp_original'] ) ) {
			$session_data['nyp_original'] = $values['nyp_original'];
		}

		$current_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

		if ( isset( $session_data['nyp_currency'] ) && $session_data['nyp_currency'] !== $current_currency ) {

			// Product is in the 'data'.
			$product = $session_data['data'];

			$price_in_current_currency = $this->woocommerce_wpml->multi_currency->prices->convert_raw_woocommerce_price( $session_data['nyp'], $current_currency );

			// Set to price in current currency.
			$product->set_price( $price_in_current_currency );
			$product->set_regular_price( $price_in_current_currency );
			$product->set_sale_price( $price_in_current_currency );

			// Subscription-specific price and variable billing period.
			if ( $product->is_type( [ 'subscription', 'subscription_variation' ] ) ) {
				$product->update_meta_data( '_subscription_price', $price_in_current_currency );
			}

		}

		return $session_data;
	}

}