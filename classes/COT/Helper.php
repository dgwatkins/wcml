<?php

namespace WCML\COT;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WPML\FP\Maybe;
use function WPML\FP\invoke;

class Helper {

	/**
	 * Determines if the HPOS table is already created or not.
	 *
	 * @return bool
	 */
	public static function getTableExists() {
		return self::callStaticMethod( DataSynchronizer::class, 'get_table_exists', false ); // @phpstan-ignore-line
	}

	/**
	 * The name of the custom order table.
	 *
	 * @return string|null
	 */
	public static function getTableName() {
		return self::callStaticMethod( OrdersTableDataStore::class, 'get_orders_table_name', null ); // @phpstan-ignore-line
	}

	/**
	 * The name of the custom order meta table.
	 *
	 * @return string|null
	 */
	public static function getMetaTableName() {
		return self::callStaticMethod( OrdersTableDataStore::class, 'get_meta_table_name', null ); // @phpstan-ignore-line
	}

	/**
	 * Determine if the custom order table is in usage.
	 *
	 * @return bool
	 */
	public static function isUsageEnabled() {
		return self::callStaticMethod( CustomOrdersTableController::class, 'custom_orders_table_usage_is_enabled', false ); // @phpstan-ignore-line
	}

	/**
	 * @param string $wcClass
	 * @param string $method
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	private static function callStaticMethod( $wcClass, $method, $default ) {
		return Maybe::fromNullable( self::getFromContainer( $wcClass ) )
			->map( invoke( $method ) )
			->getOrElse( $default );
	}

	/**
	 * @param string $wcClass
	 *
	 * @return mixed|object|null
	 */
	private static function getFromContainer( $wcClass ) {
		try {
			$object = \wc_get_container()->get( $wcClass );
		} catch ( \Exception $e ) {
			return null;
		}

		return $object;
	}

	/**
	 * Checks if the current screen is an admin screen for WooCommerce orders with the HPOS.
	 *
	 * This method is designed to safeguard the code by first checking if the necessary class
	 * and method are available in the current environment.
	 *
	 * @return bool
	 */
	public static function isOrderAdminScreen() {
		static $isOrderAdminScreen;

		if ( ! isset( $isOrderAdminScreen ) ) {
			$isOrderAdminScreen = false;

			if ( is_admin() ) {
				$currentScreen = get_current_screen();
				if ( $currentScreen ) {
					$isOrderAdminScreen = self::callStaticMethod( OrderUtil::class, 'get_order_admin_screen', null ) === $currentScreen->id; // @phpstan-ignore-line
				}
			}
		}

		return $isOrderAdminScreen;
	}
}
