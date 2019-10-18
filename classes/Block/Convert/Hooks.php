<?php

namespace WCML\Block\Convert;

use IWPML_DIC_Action;
use IWPML_Frontend_Action;
use SitePress;
use WPML_Cookie;

class Hooks implements IWPML_Frontend_Action, IWPML_DIC_Action {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_Cookie $cookie */
	private $cookie;

	public function __construct( SitePress $sitepress, WPML_Cookie $cookie ) {
		$this->sitepress = $sitepress;
		$this->cookie    = $cookie;
	}

	public function add_hooks() {
		add_filter( 'render_block_data', [ $this, 'filterIdsInBlock' ] );
		add_action( 'parse_query', [ $this, 'addCurrentLangToQueryVars' ] );

		if ( ! is_admin() ) {
			add_filter( 'rest_request_before_callbacks', [ $this, 'useLanguageFromCookie' ], 10, 3 );
		}
	}

	public function filterIdsInBlock( array $block ) {
		return ConverterProvider::get( $block['blockName'] )->convert( $block );
	}

	/**
	 * WC is caching query results in transients which name
	 * is based on the query vars hash.
	 *
	 * @param \WP_Query $query
	 */
	public function addCurrentLangToQueryVars( $query ) {
		if ( $query instanceof \Automattic\WooCommerce\Blocks\Utils\BlocksWpQuery ) {
			$query->query_vars['wpml_language'] = $this->sitepress->get_current_language();
		}
	}

	/**
	 * @param \WP_HTTP_Response|\WP_Error $response
	 * @param array                       $handler
	 * @param \WP_REST_Request            $request
	 *
	 * @return \WP_HTTP_Response|\WP_Error
	 */
	public function useLanguageFromCookie( $response, $handler, $request ) {
		if ( $this->isWcRestRequest( $request ) ) {
			/** @see \WPML_Frontend_Request::COOKIE_NAME */
			$lang = $this->cookie->get_cookie( 'wp-wpml_current_language' );

			if ( $lang ) {
				$this->sitepress->switch_lang( $lang );
			}
		}

		return $response;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	private function isWcRestRequest( \WP_REST_Request $request ) {
		return strpos( $request->get_route(), '/wc/blocks/' ) === 0
			|| strpos( $request->get_route(), '/wc/store/' ) === 0;
	}
}
