<?php

namespace WCML\Rest\Wrapper\Products;

use WCML\Rest\Exceptions\Generic;
use WCML\Rest\Exceptions\InvalidLanguage;
use WCML\Rest\Exceptions\InvalidProduct;
use WCML\Rest\ProductSaveActions;
use WCML\Rest\Wrapper\Handler;
use WPML\FP\Fns;
use WPML\FP\Obj;

class Products extends Handler {

	/** @var \Sitepress */
	private $sitepress;
	/** @var \WPML_Post_Translation */
	private $wpmlPostTranslations;
	/** @var \WPML_Query_Filter */
	private $wpmlQueryFilter;
	/** @var ProductSaveActions $productSaveActions */
	private $productSaveActions;
	/** @var bool $locked */
	private $locked = false;

	public function __construct(
		\Sitepress $sitepress,
		\WPML_Post_Translation $wpmlPostTranslations,
		\WPML_Query_Filter $wpmlQueryFilter,
		ProductSaveActions $productSaveActions
	) {
		$this->sitepress              = $sitepress;
		$this->wpmlPostTranslations   = $wpmlPostTranslations;
		$this->wpmlQueryFilter        = $wpmlQueryFilter;
		$this->productSaveActions     = $productSaveActions;
	}

	/**
	 * @param array $args
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	public function query( $args, $request ) {
		$data = $request->get_params();
		if ( isset( $data['lang'] ) && $data['lang'] === 'all' ) {
			remove_filter( 'posts_join', [ $this->wpmlQueryFilter, 'posts_join_filter' ] );
			remove_filter( 'posts_where', [ $this->wpmlQueryFilter, 'posts_where_filter' ] );
		}

		return $args;
	}


	/**
	 * Appends the language and translation information to the get_product response
	 *
	 * @param \WP_REST_Response $response
	 * @param object $object
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare( $response, $object, $request ) {
		if ( ! $this->locked ) {
			$restController   = new \WC_REST_Products_V2_Controller();
			$restController->clear_transients( $object ); // Not enough, we need to clear the terms transient somewhere...
			$refreshedProduct = wc_get_product( $object->get_id() );

			$this->locked     = true;
			$data             = $restController->prepare_object_for_response( $refreshedProduct, $request );
			$this->locked     = false;

			$response         = rest_ensure_response( $data );
		} else {
			$response->data['translations'] = array();

			$trid = $this->wpmlPostTranslations->get_element_trid( $response->data['id'] );

			if ( $trid ) {
				$translations = $this->wpmlPostTranslations->get_element_translations( $response->data['id'], $trid );
				foreach ( $translations as $translation ) {
					$response->data['translations'][ $this->wpmlPostTranslations->get_element_lang_code( $translation ) ] = $translation;
				}
				$response->data['lang'] = $this->wpmlPostTranslations->get_element_lang_code( $response->data['id'] );
			}
		}

		return $response;
	}


	/**
	 * Sets the product information according to the provided language
	 *
	 * @param object $object
	 * @param \WP_REST_Request $request
	 * @param bool $creating
	 *
	 * @throws InvalidLanguage
	 * @throws InvalidProduct
	 * @throws Generic
	 *
	 */
	public function insert( $object, $request, $creating ) {
		$getParam = Obj::prop( Fns::__, $request->get_params() );

		$langCode       = $getParam( 'lang' );
		$translationOf  = $getParam( 'translation_of' );
		$trid           = null;
		$sourceLangCode = null;

		if ( $langCode ) {

			if ( ! apply_filters( 'wpml_language_is_active', false, $langCode ) ) {
				throw new InvalidLanguage( $langCode );
			}

			if ( $translationOf ) {
				$trid = $this->wpmlPostTranslations->get_element_trid( $translationOf );

				if ( ! $trid ) {
					throw new InvalidProduct( $translationOf );
				}
			}

		} elseif ( $translationOf ) {
			throw new Generic( __( 'Using "translation_of" requires providing a "lang" parameter too', 'woocommerce-multilingual' ) );
		}

		$this->productSaveActions->run( $object, $trid, $langCode, $translationOf );
	}
}
