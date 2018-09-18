<?php

class WCML_Endpoints {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var wpdb
	 */
	private $wpdb;

	var $endpoints_strings = array();

	function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		add_action( 'init', array( $this, 'migrate_ones_string_translations' ), 9 );
		add_action( 'wpml_after_add_endpoints_translations', array( $this, 'add_wc_endpoints_translations' ) );

		add_filter( 'wpml_sl_blacklist_requests', array( $this, 'reserved_requests' ) );
		add_filter( 'wpml_endpoint_permalink_filter', array( $this, 'endpoint_permalink_filter' ), 10, 2 );
		add_filter( 'wpml_endpoint_url_value', array( $this, 'filter_endpoint_url_value' ), 10, 2 );

		add_filter( 'woocommerce_settings_saved', array( $this, 'update_original_endpoints_strings' ) );
		add_filter( 'woocommerce_get_endpoint_url', array( $this, 'filter_get_endpoint_url' ), 10, 4 );
		add_filter( 'wpml_current_ls_language_url_endpoint', array( $this, 'add_endpoint_to_current_ls_language_url' ), 10, 4 );

		if ( ! is_admin() ) {
			add_filter( 'pre_get_posts', array( $this, 'check_if_endpoint_exists' ) );
		}
	}

	public function migrate_ones_string_translations() {

		if ( ! get_option( 'wcml_endpoints_context_updated' ) ) {

			$endpoint_keys = array(
				'order-pay',
				'order-received',
				'view-order',
				'edit-account',
				'edit-address',
				'lost-password',
				'customer-logout',
				'add-payment-method',
				'set-default-payment-method',
				'delete-payment-method',
				'payment-methods',
				'downloads',
				'orders'
			);

			foreach ( $endpoint_keys as $endpoint_key ) {

				$existing_string_id = $this->wpdb->get_var(
					$this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings 
											WHERE context = %s AND name = %s",
						WPML_Endpoints_Support::STRING_CONTEXT, $endpoint_key )
				);

				if( $existing_string_id ){

					$existing_wcml_string_id = $this->wpdb->get_var(
						$this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings 
											WHERE context = %s AND name = %s",
							'WooCommerce Endpoints', $endpoint_key )
					);

					if( $existing_wcml_string_id ){
						$wcml_string_translations = icl_get_string_translations_by_id( $existing_wcml_string_id );

						foreach( $wcml_string_translations as $language_code => $translation_data ){
							icl_add_string_translation( $existing_string_id, $language_code, $translation_data['value'], ICL_STRING_TRANSLATION_COMPLETE );
						}

						wpml_unregister_string_multi( $existing_wcml_string_id );
					}
				}else{

					$this->wpdb->query(
						$this->wpdb->prepare( "UPDATE {$this->wpdb->prefix}icl_strings
                                  SET context = %s
                                  WHERE context = 'WooCommerce Endpoints' AND name = %s",
							WPML_Endpoints_Support::STRING_CONTEXT, $endpoint_key )
					);

					// update domain_name_context_md5 value
					$string_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE context = %s AND name = %s", WPML_Endpoints_Support::STRING_CONTEXT, $endpoint_key ) );

					if ( $string_id ) {
						$this->wpdb->query(
							$this->wpdb->prepare( "UPDATE {$this->wpdb->prefix}icl_strings
                              SET domain_name_context_md5 = %s
                              WHERE id = %d",
								md5( $endpoint_key, WPML_Endpoints_Support::STRING_CONTEXT ), $string_id )
						);
					}
				}
			}
			update_option( 'wcml_endpoints_context_updated', true );
		}
	}

	public function reserved_requests( $requests ) {
		$cache_key   = 'reserved_requests';
		$cache_group = 'wpml-endpoints';

		$found             = null;
		$reserved_requests = wp_cache_get( $cache_key, $cache_group, false, $found );

		if ( ! $found || ! $reserved_requests ) {
			$reserved_requests = array();

			$current_language = $this->sitepress->get_current_language();
			$languages        = $this->sitepress->get_active_languages();
			$languages_codes  = array_keys( $languages );
			foreach ( $languages_codes as $language_code ) {
				$this->sitepress->switch_lang( $language_code );

				$my_account_page_id = get_option( 'woocommerce_myaccount_page_id' );
				if ( $my_account_page_id ) {
					$my_account_page = get_post( $my_account_page_id );
					if ( $my_account_page ) {
						$account_base = $my_account_page->post_name;

						$reserved_requests[] = $account_base;
						$reserved_requests[] = '/^' . $account_base . '/'; // regex version

						foreach ( $this->woocommerce_wpml->get_wc_query_vars() as $key => $endpoint ) {
							$translated_endpoint = apply_filters( 'wpml_get_endpoint_translation', $key, $endpoint, $language_code );

							$reserved_requests[] = $account_base . '/' . $translated_endpoint;
						}
					}
				}
			}
			$this->sitepress->switch_lang( $current_language );

			if ( $reserved_requests ) {
				wp_cache_set( $cache_key, $reserved_requests, $cache_group );
			}
		}

		if ( $reserved_requests ) {
			$requests = array_unique( array_merge( $requests, $reserved_requests ) );
		}

		return $requests;
	}

	public function add_wc_endpoints_translations( $language ) {

		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'ICL_SITEPRESS_VERSION' ) || ICL_PLUGIN_INACTIVE || version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
			return false;
		}

		$wc_vars = WC()->query->query_vars;

		if ( ! empty( $wc_vars ) ) {
			$query_vars = array(
				// Checkout actions
				'order-pay'          => $this->get_endpoint_translation( 'order-pay', $wc_vars['order-pay'], $language ),
				'order-received'     => $this->get_endpoint_translation( 'order-received', $wc_vars['order-received'], $language ),

				// My account actions
				'view-order'         => $this->get_endpoint_translation( 'view-order', $wc_vars['view-order'], $language ),
				'edit-account'       => $this->get_endpoint_translation( 'edit-account', $wc_vars['edit-account'], $language ),
				'edit-address'       => $this->get_endpoint_translation( 'edit-address', $wc_vars['edit-address'], $language ),
				'lost-password'      => $this->get_endpoint_translation( 'lost-password', $wc_vars['lost-password'], $language ),
				'customer-logout'    => $this->get_endpoint_translation( 'customer-logout', $wc_vars['customer-logout'], $language ),
				'add-payment-method' => $this->get_endpoint_translation( 'add-payment-method', $wc_vars['add-payment-method'], $language )
			);

			if ( isset( $wc_vars['orders'] ) ) {
				$query_vars['orders'] = $this->get_endpoint_translation( 'orders', $wc_vars['orders'], $language );
			}
			if ( isset( $wc_vars['downloads'] ) ) {
				$query_vars['downloads'] = $this->get_endpoint_translation( 'downloads', $wc_vars['downloads'], $language );
			}
			if ( isset( $wc_vars['payment-methods'] ) ) {
				$query_vars['payment-methods'] = $this->get_endpoint_translation( 'payment-methods', $wc_vars['payment-methods'], $language );
			}
			if ( isset( $wc_vars['delete-payment-method'] ) ) {
				$query_vars['delete-payment-method'] = $this->get_endpoint_translation( 'delete-payment-method', $wc_vars['delete-payment-method'], $language );
			}
			if ( isset( $wc_vars['set-default-payment-method'] ) ) {
				$query_vars['set-default-payment-method'] = $this->get_endpoint_translation( 'set-default-payment-method', $wc_vars['set-default-payment-method'], $language );
			}

			$query_vars = apply_filters( 'wcml_register_endpoints_query_vars', $query_vars, $wc_vars, $this );

			$query_vars             = array_merge( $wc_vars, $query_vars );
			WC()->query->query_vars = $query_vars;
		}

	}

	public function get_endpoint_translation( $endpoint_key, $endpoint, $language = null ) {
		return apply_filters( 'wpml_get_endpoint_translation', $endpoint_key, $endpoint, $language );
	}

	public function endpoint_permalink_filter( $data, $endpoint_key ) {

		$link     = $data[0];
		$endpoint = $data[1];

		if ( isset( $wp->query_vars[ $endpoint_key ] ) ) {
			if ( 'order-pay' === $endpoint_key ) {
				$endpoint = get_option( 'woocommerce_checkout_pay_endpoint' );
				$link     .= isset( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '';
			} elseif ( 'order-received' === $endpoint_key ) {
				$endpoint = get_option( 'woocommerce_checkout_order_received_endpoint' );
			} elseif ( 'customer-logout' === $endpoint_key ) {
				$endpoint = get_option( 'woocommerce_logout_endpoint' );
			} else {
				$endpoint = get_option( 'woocommerce_myaccount_' . str_replace( '-', '_', $endpoint_key ) . '_endpoint', $endpoint );
			}

			$endpoint = apply_filters( 'wcml_endpoint_permalink_filter', $endpoint, $endpoint_key );
		}

		return array( $link, $endpoint );
	}

	/*
	 * We need check special case - when you manually put in URL default not translated endpoint it not generated 404 error
	 */
	public function check_if_endpoint_exists( $q ) {
		global $wp_query;

		$my_account_id = wc_get_page_id( 'myaccount' );

		$current_id = $q->query_vars['page_id'];
		if ( ! $current_id ) {
			$current_id = $q->queried_object_id;
		}

		if ( ! $q->is_404 && $current_id == $my_account_id && $q->is_page ) {

			$uri_vars        = array_filter( explode( '/', $_SERVER['REQUEST_URI'] ) );
			$endpoints       = WC()->query->get_query_vars();
			$endpoint_in_url = urldecode( end( $uri_vars ) );

			$endpoints['shipping'] = urldecode( $this->get_translated_edit_address_slug( 'shipping' ) );
			$endpoints['billing']  = urldecode( $this->get_translated_edit_address_slug( 'billing' ) );

			$endpoint_not_pagename         = isset( $q->query['pagename'] ) && urldecode( $q->query['pagename'] ) != $endpoint_in_url;
			$endpoint_url_not_in_endpoints = ! in_array( $endpoint_in_url, $endpoints );
			$uri_vars_not_in_query_vars    = ! in_array( urldecode( prev( $uri_vars ) ), $q->query_vars );

			if ( $endpoint_not_pagename && $endpoint_url_not_in_endpoints && is_numeric( $endpoint_in_url ) && $uri_vars_not_in_query_vars ) {
				$wp_query->set_404();
				status_header( 404 );
				include( get_query_template( '404' ) );
				die();
			}
		}
	}

	private function get_translated_edit_address_slug( $slug, $language = false ) {

		$strings_language = $this->woocommerce_wpml->strings->get_string_language( $slug, 'woocommerce', 'edit-address-slug: ' . $slug );
		if ( $strings_language == $language ) {
			return $slug;
		}

		$translated_slug = apply_filters( 'wpml_translate_single_string', $slug, 'woocommerce', 'edit-address-slug: ' . $slug, $language );
		if ( $translated_slug == $slug ) {
			if ( $language ) {
				$translated_slug = $this->woocommerce_wpml->strings->get_translation_from_woocommerce_mo_file( 'edit-address-slug' . chr( 4 ) . $slug, $language );
			} else {
				$translated_slug = _x( $slug, 'edit-address-slug', 'woocommerce' );
			}
		}

		return $translated_slug;
	}

	public function filter_get_endpoint_url( $url, $endpoint, $value, $permalink ) {

		// return translated edit account slugs
		remove_filter( 'woocommerce_get_endpoint_url', array( $this, 'filter_get_endpoint_url' ), 10, 4 );
		if ( isset( WC()->query->query_vars['edit-address'] ) && WC()->query->query_vars['edit-address'] == $endpoint && in_array( $value, array(
				'shipping',
				'billing'
			) )
		) {
			$url = wc_get_endpoint_url( 'edit-address', $this->get_translated_edit_address_slug( $value ) );
		} elseif ( $endpoint === get_option( 'woocommerce_myaccount_lost_password_endpoint' ) ) {
			$translated_lost_password_endpoint = $this->get_endpoint_translation( 'lost-password', $endpoint );

			$wc_account_page_url = wc_get_page_permalink( 'myaccount' );
			$url                 = wc_get_endpoint_url( $translated_lost_password_endpoint, '', $wc_account_page_url );

		}
		add_filter( 'woocommerce_get_endpoint_url', array( $this, 'filter_get_endpoint_url' ), 10, 4 );

		return $url;
	}

	public function filter_endpoint_url_value( $value, $page_lang ) {

		if ( $page_lang ) {
			$edit_address_shipping = $this->get_translated_edit_address_slug( 'shipping', $page_lang );
			$edit_address_billing  = $this->get_translated_edit_address_slug( 'billing', $page_lang );

			if ( $edit_address_shipping == urldecode( $value ) ) {
				$value = $this->get_translated_edit_address_slug( 'shipping', $this->sitepress->get_current_language() );
			} elseif ( $edit_address_billing == urldecode( $value ) ) {
				$value = $this->get_translated_edit_address_slug( 'billing', $this->sitepress->get_current_language() );
			}
		}

		return $value;
	}

	public function update_original_endpoints_strings() {
		foreach ( WC()->query->query_vars as $endpoint_key => $endpoint ) {
			apply_filters( 'wpml_register_endpoint_string', $endpoint_key, $endpoint );
		}
	}

	public function add_endpoint_to_current_ls_language_url( $url, $post_lang, $data, $current_endpoint ){
		global $post;

		if (
			$current_endpoint &&
			$post &&
			$post_lang !== $data['code'] &&
			'page' == get_option( 'show_on_front' )
		) {

			$myaccount_page_id = wc_get_page_id( 'myaccount' );

			if (
				$myaccount_page_id === (int) get_option( 'page_on_front' ) &&
				$post->ID === $myaccount_page_id
			) {
				$url = apply_filters( 'wpml_get_endpoint_url', $current_endpoint['key'], $current_endpoint['value'], $url );
			}
		}

		return $url;
	}

}
