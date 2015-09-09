<?php
/**
 * Compatibility class for plugin WooCommerce Bulk Stock Management
 * http://www.woothemes.com/products/bulk-stock-management/
 *
 * @author konrad
 */
class WCML_Bulk_Stock_Management {
	function __construct() {
		if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'woocommerce-bulk-stock-management') {
			global $sitepress;
			remove_action('admin_enqueue_scripts', array($sitepress, 'language_filter'));
		}
		
		add_action( 'wc_bulk_stock_after_process_qty', array($this, 'wc_bulk_stock_after_process_qty_action'), 10, 1 );
	}
	
	function wc_bulk_stock_after_process_qty_action($id) {
		global $sitepress;
		
		$new_quantity = get_post_meta($id, '_stock', true);
		
		$trid = $sitepress->get_element_trid( $id, 'post_product' );
		$translations = $sitepress->get_element_translations( $trid, 'post_product' );
		
		foreach ($translations as $translation) {
			if ($translation->element_id == $id) {
				continue;
			}
			update_post_meta($translation->element_id, '_stock', $new_quantity);
		}
		
	}
}
