<?php

namespace WCML\COT;

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
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
	 * @return string|null
	 */
	public static function getTableName() {
		return self::callStaticMethod( OrdersTableDataStore::class, 'get_orders_table_name', null ); // @phpstan-ignore-line
	}

	/**
	 * @return string|null
	 */
	public static function getMetaTableName() {
		return self::callStaticMethod( OrdersTableDataStore::class, 'get_meta_table_name', null ); // @phpstan-ignore-line
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
}
