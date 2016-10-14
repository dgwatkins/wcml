<?php

class WCML_UnitTestCase extends WPML_UnitTestCase {

	public $sitepress;
	public $woocommerce_wpml;
	public $woocommerce;

	public $wpdb;

	/**
	 * @uses \WPML_UnitTestCase::setUp to setup a WPML installation and remove query filters added by WP core unit tests
	 *                                 that interfere with WPML's unit tests
	 */
	function setUp() {
		parent::setUp();

		global $woocommerce_wpml, $sitepress, $wpdb, $woocommerce;

		$this->clear_db();
		icl_cache_clear();

		$this->sitepress        =& $sitepress;
		$this->woocommerce_wpml =& $woocommerce_wpml;
		$this->wpdb             =& $wpdb;
		$this->woocommerce      =& $woocommerce;
		$this->wcml_helper      = new WCML_Helper();
		$this->wcml_helper->init( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
		require_once WC_PATH . '/woocommerce.php';

		$wc_unit = new WC_Unit_Test_Case();
		$wc_unit->setUp();


	}

	private function clear_db() {
		global $wpdb;

		$tables_to_empty = array(
			"{$wpdb->prefix}woocommerce_api_keys",
			"{$wpdb->prefix}woocommerce_attribute_taxonomies",
			"{$wpdb->prefix}woocommerce_downloadable_product_permissions",
			"{$wpdb->prefix}woocommerce_order_itemmeta",
			"{$wpdb->prefix}woocommerce_order_items",
			"{$wpdb->prefix}woocommerce_payment_tokenmeta",
			"{$wpdb->prefix}woocommerce_payment_tokens",
			"{$wpdb->prefix}woocommerce_sessions",
			"{$wpdb->prefix}woocommerce_shipping_zone_locations",
			"{$wpdb->prefix}woocommerce_shipping_zone_methods",
			"{$wpdb->prefix}woocommerce_shipping_zones",
			"{$wpdb->prefix}woocommerce_tax_rate_locations",
			"{$wpdb->prefix}woocommerce_tax_rates",
			"{$wpdb->prefix}wc_booking_relationships",
		);
		foreach ( $tables_to_empty as $table_name ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
				$wpdb->query( "DELETE FROM {$table_name}" );
			}

		}
	}

	protected function make_current_user_wcml_admin() {
		global $current_user;

		if ( ! isset( $current_user ) || (bool) get_current_user_id() === false ) {
			$user_factory = new WP_UnitTest_Factory_For_User();
			$current_user = $user_factory->create_and_get();
		}

		$current_user->add_cap( 'manage_options' );
		$current_user->add_cap( 'activate_plugins' );
		$current_user->add_cap( 'wpml_operate_woocommerce_multilingual' );
		$current_user->get_role_caps();
		$current_user->update_user_level_from_caps();

		return $current_user->ID;
	}

	protected function make_current_user_wcml_manager() {
		global $current_user;

		if ( ! isset( $current_user ) || (bool) get_current_user_id() === false ) {
			$user_factory = new WP_UnitTest_Factory_For_User();
			$current_user = $user_factory->create_and_get();
		}

		$current_user->add_cap( 'shop_manager' );
		$current_user->add_cap( 'wpml_manage_woocommerce_multilingual' );
		$current_user->get_role_caps();
		$current_user->update_user_level_from_caps();

		return $current_user->ID;
	}

	function get_wcml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return mixed
	 */
	function get_wcml_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )->disableOriginalConstructor()->getMock();
	}

	function get_wcml_products_mock() {
		return $this->getMockBuilder( 'WCML_Products' )->disableOriginalConstructor()->getMock();
	}

	function get_wcml_multi_currency_prices_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )->disableOriginalConstructor()->getMock();
	}
}
