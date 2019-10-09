<?php

namespace WCML\Email;

use wpdb;

class Translation {

	/** @var wpdb */
	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param string      $context
	 * @param string      $name
	 * @param false|int   $orderId
	 * @param null|string $lang
	 *
	 * @return mixed|void
	 */
	public function get( $context, $name, $orderId = false, $lang = null ) {
		if ( $orderId && ! $lang ) {
			$order_language = get_post_meta( $orderId, 'wpml_language', true );

			if ( $order_language ) {
				$lang = $order_language;
			}
		}

		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT value FROM {$this->wpdb->prefix}icl_strings WHERE context = %s AND name = %s ",
				$context,
				$name
			)
		);

		return apply_filters( 'wpml_translate_single_string', $result, $context, $name, $lang );
	}
}
