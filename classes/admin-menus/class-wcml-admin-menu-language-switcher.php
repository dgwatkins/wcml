<?php

class WCML_Admin_Menu_Language_Switcher {
	/** @var string */
	private $pagenow;

	/** @var SitePress */
	private $sitepress;

	/**
	 * @param string $pagenow
	 * @param SitePress $sitepress
	 */
	public function __construct( $pagenow, SitePress $sitepress ) {
		$this->pagenow   = $pagenow;
		$this->sitepress = $sitepress;
	}


	public function remove_hook() {
		if ( $this->is_page_without_admin_language_switcher() ) {
			$this->remove_wpml_admin_language_switcher();
		}
	}

	private function is_page_without_admin_language_switcher() {
		$get_post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : false;
		$get_post      = isset( $_GET['post'] ) ? $_GET['post'] : false;
		$get_page      = isset( $_GET['page'] ) ? $_GET['page'] : false;

		$is_page_wpml_wcml = 'wpml-wcml' === $get_page;

		$is_new_order_or_coupon = in_array( $this->pagenow, array( 'edit.php', 'post-new.php' ), true ) &&
		                          $get_post_type &&
		                          in_array( $get_post_type, array( 'shop_coupon', 'shop_order' ), true );

		$is_edit_order_or_coupon    = 'post.php' === $this->pagenow && $get_post &&
		                              in_array( get_post_type( $get_post ), array( 'shop_coupon', 'shop_order' ), true );

		$is_shipping_zones          = 'shipping_zones' === $get_page;

		$is_attributes_page = apply_filters( 'wcml_is_attributes_page', 'product_attributes' === $get_page );


		return is_admin() && (
				$is_page_wpml_wcml ||
				$is_new_order_or_coupon ||
				$is_edit_order_or_coupon ||
				$is_shipping_zones ||
				$is_attributes_page
			);

	}

	private function remove_wpml_admin_language_switcher() {
		remove_action( 'wp_before_admin_bar_render', array( $this->sitepress, 'admin_language_switcher' ) );
	}
}