<?php

class WCML_Notices_Product_Edit {
	/** @var bool */
	private $is_sitepress_defined;
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var string */
	private $pagenow;

	/**
	 * @param bool $is_sitepress_defined
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param string $pagenow
	 */
	public function __construct( $is_sitepress_defined, woocommerce_wpml $woocommerce_wpml, $pagenow ) {
		$this->is_sitepress_defined = $is_sitepress_defined;
		$this->woocommerce_wpml     = $woocommerce_wpml;
		$this->pagenow              = $pagenow;
	}


	public function add_hooks() {
		if ( is_admin() && $this->is_sitepress_defined && $this->woocommerce_wpml->check_dependencies ) {
			add_action( 'admin_init', array( $this, 'add_notice' ) );
		}
	}

	public function add_notice() {
		if ( $this->is_editing_product_in_non_default_language_page_displayed() ) {
			add_action( 'admin_notices', array( $this, 'inf_editing_product_in_non_default_lang' ) );
		}
	}

	/**
	 * @return bool
	 */
	private function is_editing_product_in_non_default_language_page_displayed() {
		return ! $this->woocommerce_wpml->settings['trnsl_interface'] &&
		       'post.php' === $this->pagenow &&
		       isset( $_GET['post'] ) &&
		       'product' === get_post_type( $_GET['post'] ) &&
		       ! $this->woocommerce_wpml->products->is_original_product( $_GET['post'] );
	}

	public function inf_editing_product_in_non_default_lang() {
		if ( ! $this->woocommerce_wpml->settings['dismiss_tm_warning'] ) {
			$url = $_SERVER['REQUEST_URI'];

			$message = '<div class="message error otgs-is-dismissible"><p>';
			$message .= sprintf(
				__( 'The recommended way to translate WooCommerce products is using the %sWooCommerce Multilingual products translation%s page.
					Please use this page only for translating elements that are not available in the WooCommerce Multilingual products translation table.',
					'woocommerce-multilingual'
				),
				'<strong><a href="' . admin_url( 'admin.php?page=wpml-wcml&tab=products' ) . '">', '</a></strong>'
			);
			$message .= '</p><a class="notice-dismiss" href="' . $url . '&wcml_action=dismiss_tm_warning"><span class="screen-reader-text">'
			            . esc_html__( 'Dismiss', 'woocommerce-multilingual' ) . '</a>';
			$message .= '</div>';

			echo $message;
		}
	}
}
