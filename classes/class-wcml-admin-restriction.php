<?php

class WCML_Admin_Restriction {

	const URL = 'admin.php?page=wpml-wcml&tab=products';

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var string */
	private $wpdb_prefix;
	/** @var SitePress */
	private $sitepress;
	/** @var string */
	private $pagenow;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param string $wpdb_prefix
	 * @param SitePress $sitepress
	 * @param string $pagenow
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, $wpdb_prefix, SitePress $sitepress = null, $pagenow ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb_prefix      = $wpdb_prefix;
		$this->sitepress        = $sitepress;
		$this->pagenow          = $pagenow;
	}


	public function add_hooks() {
		if ( ! $this->woocommerce_wpml->check_dependencies ) {
			return;
		}

		if ( is_admin() && null !== $this->sitepress ) {
			add_action( 'admin_init', array( $this, 'redirect_if_rules_are_not_fulfilled' ), 10, 0 );
		}

		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'check_user_admin_access' ) );
	}

	public function redirect_if_rules_are_not_fulfilled() {
		if ( ! $this->woocommerce_wpml->settings['trnsl_interface'] ) {
			return;
		}

		if ( $this->should_redirect_to_single_product() ) {
			$prid = (int) $_GET['post'];
			wp_redirect( admin_url( self::URL . '&prid=' . $prid ) );
			return wp_die();
		}

		if ( $this->is_duplicating_product_with_non_default_language() ) {
			wp_redirect( admin_url( self::URL ) );
			return wp_die();
		}
	}

	private function should_redirect_to_single_product() {
		if ( 'post.php' === $this->pagenow &&
		     ! is_ajax() &&
		     isset( $_GET['post'] ) &&
		     ! $this->woocommerce_wpml->products->is_original_product( $_GET['post'] ) &&
		     'product' === get_post_type( $_GET['post'] ) &&
		     (
			     ! isset( $_GET['action'] ) ||
			     ( isset( $_GET['action'] ) && ! in_array( $_GET['action'], array( 'trash', 'delete', 'untrash' ) ) )
		     )
		) {
			return true;
		}

		return false;
	}

	private function is_duplicating_product_with_non_default_language() {
		return 'admin.php' === $this->pagenow &&
		       isset( $_GET['action'], $_GET['post'] ) &&
		       'duplicate_product' === $_GET['action'] &&
		       $this->sitepress->get_default_language() !== $this->sitepress->get_language_for_element( $_GET['post'], 'post_product' );
	}

	public function check_user_admin_access( $prevent_access ) {
		if ( current_user_can( 'wpml_manage_woocommerce_multilingual' )) {
			return false;
		}

		$user_lang_pairs = get_user_meta( get_current_user_id(), $this->wpdb_prefix . 'language_pairs', true );
		if ( ! empty( $user_lang_pairs ) ) {
			return false;
		}

		return $prevent_access;
	}
}
