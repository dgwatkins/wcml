<?php

namespace WCML\UI\WC;

class Hooks implements \IWPML_Backend_Action {

	/**
	 * @see \Automattic\WooCommerce\Admin\Loader::register_scripts()
	 */
	const PRIORITY_BEFORE_WC_ADMIN = 9;

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ], self::PRIORITY_BEFORE_WC_ADMIN );
	}

	public function enqueueScripts() {
		wp_enqueue_script( 'wcmlUiWc', WCML_PLUGIN_URL . '/dist/js/adminUiWc/app.js', [ 'wp-hooks' ], WCML_VERSION );
	}
}