<?php

namespace WCML\Rest\Wrapper;

use WCML\Rest\Wrapper\Products\Images as ProductsImages;
use WCML\Rest\Wrapper\Products\Languages as ProductsLanguages;
use WCML\Rest\Wrapper\Products\Prices as ProductsPrices;

use WCML\Rest\Wrapper\Orders\Languages as OrdersLanguages;
use WCML\Rest\Wrapper\Orders\Prices as OrdersPrices;

class Factory {

	/**
	 * @return Handler
	 */
	public static function create( $objectType ) {
		global $woocommerce_wpml, $wpml_post_translations, $sitepress, $wpml_query_filter;

		$isMultiCurrencyOn = wcml_is_multi_currency_on();

		switch ( $objectType ) {
			case 'shop_order':
				$objects[] = new OrdersLanguages();
				if ( $isMultiCurrencyOn ) {
					$objects[] = new OrdersPrices( $woocommerce_wpml->multi_currency->orders );
				}

				return new Composite( $objects );
			case 'product':
				$objects[] = new ProductsLanguages( $sitepress, $wpml_post_translations, $wpml_query_filter );
				$objects[] = new ProductsImages( $woocommerce_wpml->products, $woocommerce_wpml->media );
				if ( $isMultiCurrencyOn ) {
					$objects[] = new ProductsPrices( $woocommerce_wpml->multi_currency, $woocommerce_wpml->settings['currencies_order'], $wpml_post_translations );
				}

				return new Composite( $objects );
			case 'product_cat':
			case 'product_tag':
				return new ProductTerms( $sitepress );
		}

		return new Handler();
	}

}