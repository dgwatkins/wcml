<?php

namespace WCML\Rest;

use WC_Product;
use WCML_Helper;

/**
 * @group rest
 * @group rest-product-save-actions
 * @property \WCML_Helper $wcml_helper
 * @property \SitePress $sitepress
 */
class TestProductSaveActions extends \WCML_UnitTestCase {
	/**
	 * For action: 'woocommerce_rest_insert_product'
	 */
	public function testSyncsProductAfterSaveV1() {
		$this->setSitePressSettings();

		$data    = $this->wcml_helper->add_variable_product();
		$product = get_post( $data->id );

		$this->getSubject()->run( $product, null, 'en', null );
	}

	/**
	 * For action: 'woocommerce_rest_insert_product_object'
	 */
	public function testSyncsProductAfterSaveV3() {
		$this->setSitePressSettings();

		$data    = $this->wcml_helper->add_variable_product();
		$product = new WC_Product( $data->id );

		$this->getSubject()->run( $product, null, 'en', null );
	}

	/**
	 * @return ProductSaveActions
	 */
	private function getSubject() {
		$settings = [];
		return new ProductSaveActions( $settings, $this->wpdb, $this->sitepress, $this->woocommerce_wpml->sync_product_data );
	}

	private function setSitePressSettings() {
		$this->sitepress->set_setting(
			'translation-management',
			[
				'taxonomies_readonly_config' => [],
				'doc_translation_method'     => ICL_TM_TMETHOD_MANUAL,
			]
		);
	}
}
