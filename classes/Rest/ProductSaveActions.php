<?php

namespace WCML\Rest;

class ProductSaveActions extends \WPML_Post_Translation {

	/** @var \WCML_Synchronize_Product_Data $productDataSync */
	private $productDataSync;

	public function __construct(
		array $settings,
		\wpdb $wpdb,
		\WCML_Synchronize_Product_Data $productDataSync
	) {
		parent::__construct( $settings, $wpdb );
		$this->productDataSync = $productDataSync;
	}

	/**
	 * @param \WC_Abstract_Legacy_Product    $product
	 * @param int|null    $trid
	 * @param string      $langCode
	 * @param int|null    $translationOf
	 */
	public function run( $product, $trid, $langCode, $translationOf ) {
		global $sitepress;

		$productId      = $product->get_id();
		$trid           = $trid ? $trid : $this->get_save_post_trid( $productId, null );
		$langCode       = $langCode ? $langCode : parent::get_save_post_lang( $productId, $sitepress );
		$sourceLangCode = $this->get_element_lang_code( $translationOf );

		$this->after_save_post( $trid, get_post( $productId, ARRAY_A ), $langCode, $sourceLangCode );
		$this->productDataSync->synchronize_products( $productId, get_post( $productId ) );
	}

	public function save_post_actions( $postId, $post ) {
		throw new \Exception( 'This method should not be called, use `run` instead.' );
	}

	/**
	 * @inheritDoc
	 */
	function get_save_post_trid( $postId, $post_status ) {
		return $this->get_element_trid( $postId );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_save_post_source_lang( $trid, $language_code, $default_language ) {
		$post_id = $this->get_element_id( $language_code, $trid );

		return $post_id ? $this->get_source_lang_code( $post_id ) : null;
	}
}
