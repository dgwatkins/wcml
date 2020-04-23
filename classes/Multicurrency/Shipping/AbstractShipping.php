<?php

namespace WCML\Multicurrency\Shipping;

abstract class AbstractShipping {
	/**
	 * Returns shipping method id (shipping option key).
	 *
	 * @return string
	 */
	public function getMethodId() {
		return $this->methodId;
	}

	/**
	 * Returns field title.
	 *
	 * This value is visible on shipping method configuration screen, on the left.
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	abstract public function getFieldTitle( $currencyCode );

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
	abstract public function getFieldDescription( $currencyCode );
}