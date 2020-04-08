<?php

namespace WCML\Multicurrency\UI;

class Hooks implements \IWPML_Backend_Action {

	const HANDLE = 'wcml-multicurrency-options';

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'loadJs' ] );
	}

	/**
	 * @param string $hook
	 */
	public function loadJs( $hook ) {
		if ( 'woocommerce_page_wpml-wcml' === $hook ) {
			wp_enqueue_script(
				self::HANDLE,
				WCML_PLUGIN_URL . '/dist/js/multicurrencyOptions/app.js',
				[],
				WCML_VERSION
			);

			wp_enqueue_style(
				self::HANDLE,
				WCML_PLUGIN_URL . '/dist/css/multicurrencyOptions/styles.css',
				[],
				WCML_VERSION
			);
		}
	}
}