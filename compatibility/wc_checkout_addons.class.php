<?php
/**
 * Description of wc_checkout_addons
 *
 * @author konrad
 */
class WCML_Checkout_Addons {
	public function __construct() {
		add_filter( 'wc_checkout_add_ons_add_on_get_cost', array($this, 'wc_checkout_add_ons_wpml_multi_currency_support') );
	}
	
	public function wc_checkout_add_ons_wpml_multi_currency_support( $cost ) {
		return apply_filters( 'wcml_raw_price_amount', $cost );
	}
}
