<?php

class WCML_Setup_Introduction_UI extends WCML_Templates_Factory {

	/** @var string */
	private $next_step_url;

	public function __construct( $next_step_url ) {
		parent::__construct();

		$this->next_step_url = $next_step_url;
	}

	public function get_model() {

		$model = [
			'strings'      => [
				'step_id'     => 'introduction_step',
				'heading'     => __( "Let's make your WooCommerce shop multilingual", 'woocommerce-multilingual' ),
				'description' => [

					'title' => __( 'To get started, we need to set up the following:', 'woocommerce-multilingual' ),
					'step1' => __( "Create store pages in all your site's languages", 'woocommerce-multilingual' ),
					'step2' => __( 'Choose which product attributes you want to translate', 'woocommerce-multilingual' ),
					'step3' => __( 'Set your translation options', 'woocommerce-multilingual' ),
					'step4' => __( 'Decide if you want to add multiple currencies to your store', 'woocommerce-multilingual' ),

				],
				/* translators: %1$s and %2$s are opening and closing HTML strong tags */
				'footer'      => sprintf( __( 'You can change these settings later by going to %1$sWooCommerce &raquo; WooCommerce Multilingual & Multicurrency%2$s.', 'woocommerce-multilingual' ), '<strong>', '</strong>' ),
				'continue'    => __( "Let's continue", 'woocommerce-multilingual' ),
				'later'       => __( "I'll do the setup later", 'woocommerce-multilingual' ),
			],
			'later_url'    => admin_url( 'admin.php?page=wpml-wcml&src=setup_later' ),
			'continue_url' => $this->next_step_url,
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/introduction.twig';
	}


}
