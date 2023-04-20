<?php

use WCML\Options\WPML;

class WCML_Setup_Ready_UI extends WCML_Templates_Factory {

	public function get_model() {
		$model = [
			'strings' => [
				'step_id'      => 'ready_step',
				'description2' => __( "For your convenience, we've marked items that require your attention with a notice icon. You can see a list of everything that you should complete in the %1\$sStatus%2\$s tab.", 'woocommerce-multilingual' ),
				'continue'     => __( 'Close setup', 'woocommerce-multilingual' ),
			],
		];

		if ( WPML::isAutomatic( 'product' ) ) {
			$model['strings']['heading']      = __( 'WPML is translating your products!', 'woocommerce-multilingual' );
			$model['strings']['description1'] = __( 'Sit tight while we make your store completely multilingual.', 'woocommerce-multilingual' );
			if ( wcml_is_multi_currency_on() ) {
				/* translators: %1$s and %2$s are opening and closing HTML strong tags */
				$model['strings']['description2'] = __( 'Don\'t forget to go to %1$sWooCommerce &raquo; WooCommerce Multilingual & Multicurrency%2$s to add currencies to your site.', 'woocommerce-multilingual' );
			} else {
				$model['strings']['description2'] = '';
			}
			$model['continue_url'] = admin_url( 'admin.php?page=tm/menu/main.php' );
		} else {
			$model['strings']['heading'] = __( 'Your multilingual shop is ready to be translated!', 'woocommerce-multilingual' );
			if ( wcml_is_multi_currency_on() ) {
				/* translators: %1$s and %2$s are opening and closing HTML strong tags */
				$model['strings']['description1'] = __( 'Go to %1$sWooCommerce &raquo; WooCommerce Multilingual & Multicurrency%2$s to add currencies to your site and translate your products, taxonomies, shipping classes, and more.', 'woocommerce-multilingual' );
			} else {
				/* translators: %1$s and %2$s are opening and closing HTML strong tags */
				$model['strings']['description1'] = __( 'Go to %1$sWooCommerce &raquo; WooCommerce Multilingual & Multicurrency%2$s to translate your products, taxonomies, shipping classes, and more.', 'woocommerce-multilingual' );
			}
			/* translators: %1$s and %2$s are opening and closing HTML strong tags */
			$model['strings']['description2'] = __( 'Check the %1$sStatus%2$s tab to see items that require your attention.', 'woocommerce-multilingual' );
			$model['continue_url']            = admin_url( 'admin.php?page=wpml-wcml&tab=status&src=setup' );
		}

		return $model;
	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/ready.twig';
	}


}
