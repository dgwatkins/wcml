<?php

use WCML\Options\WPML;

class WCML_Setup_Translation_Options_UI extends WCML_Templates_Factory {

	/** @var string */
	private $next_step_url;

	public function __construct( $next_step_url ) {
		parent::__construct();

		$this->set_next_step_url( $next_step_url );
	}

	public function set_next_step_url( $next_step_url ) {
		$this->next_step_url = $next_step_url;
	}

	public function get_model() {

		$custom_posts_unlocked = apply_filters( 'wpml_get_setting', false, 'custom_posts_unlocked_option' );
		$custom_posts_sync     = apply_filters( 'wpml_get_setting', false, 'custom_posts_sync_option' );

		$is_display_as_translated_checked = isset( $custom_posts_unlocked['product'], $custom_posts_sync['product'] )
											&& 1 === $custom_posts_unlocked['product']
											&& WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED === $custom_posts_sync['product'];

		$model = [
			'strings'                => [
				'step_id'                          => 'translation_options_step',
				'heading'                          => __( 'How do you want to translate your products?', 'woocommerce-multilingual' ),
				'tooltip_translate_everything'     => sprintf(
					/* translators: %1$s/%2$s are opening and closing HTML strong tags and %3$s/%4$s are opening and closing HTML link tags */
					__( 'You can only choose this when you have WPML set to %1$sTranslate Everything Automatically%2$s. %3$sRead More →%4$s', 'woocommerce-multilingual' ),
					'<strong>',
					'</strong>',
					'<a target="blank" class="wpml-external-link" rel="noopener" href="' . WCML_Tracking_Link::getWcmlMainDoc( '#translating-your-products-automatically' ) . '">',
					'</a>'
				),
				'label_translate_everything'       => __( 'Translate all products automatically', 'woocommerce-multilingual' ),
				'description_translate_everything' => __( 'WPML will start translating all your products for you right away.', 'woocommerce-multilingual' ),
				'label_translate_some'             => __( 'Choose which products to translate', 'woocommerce-multilingual' ),
				'description_translate_some'       => __( 'You can still use automatic translation, but you decide what gets translated and how.', 'woocommerce-multilingual' ),
				/* translators: %1$s and %2$s are opening and closing HTML strong tags */
				'description_footer'               => __( 'You can change these settings later by going to %1$sWPML &raquo; Settings.%2$s', 'woocommerce-multilingual' ),
				'label_choose'                     => __( 'Choose', 'woocommerce-multilingual' ),
				'continue'                         => __( 'Continue', 'woocommerce-multilingual' ),
			],
			'is_translate_some_mode' => ! WPML::shouldTranslateEverything(),
			'continue_url'           => $this->next_step_url,
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/translation-options.twig';
	}

}
