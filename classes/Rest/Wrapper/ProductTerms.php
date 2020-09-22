<?php

namespace WCML\Rest\Wrapper;

use WCML\Rest\Exceptions\InvalidLanguage;

class ProductTerms extends Handler {

	public function __construct(
		\SitePress $sitepress
	) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @param array $args
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 *
	 * @throws InvalidLanguage
	 */
	public function query( $args, $request ) {
		$data = $request->get_params();

		if ( isset( $data['lang'] ) ) {
			if ( 'all' === $data['lang'] ) {
				remove_filter( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ], 10 );
				remove_filter( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1, 1 );
			} elseif ( ! apply_filters( 'wpml_language_is_active', false, $data['lang'] ) ) {
				throw new InvalidLanguage( $data['lang'] );
			}
		}

		return $args;
	}

}