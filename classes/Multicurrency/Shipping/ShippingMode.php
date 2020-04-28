<?php

namespace WCML\Multicurrency\Shipping;

interface ShippingMode {
	/**
	 * Returns shipping method id (shipping option key).
	 *
	 * @return string
	 */
	public function getMethodId();

	/**
	 * Returns field title.
	 *
	 * This value is visible on shipping method configuration screen, on the left.
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getFieldTitle( $currencyCode );

	/**
	 * Returns field description.
	 *
	 * This value is visible on shipping method configuration screen, when mouse over
	 * the question mark icon, next to field title.
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getFieldDescription( $currencyCode );

	/**
	 * Returns key used in shipping options for cost in given currency.
	 *
	 * @param $currencyCode
	 *
	 * @return mixed
	 */
	public function getCostKey( $currencyCode );
}