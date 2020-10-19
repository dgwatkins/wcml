<?php

namespace WCML\Compatibility\YITH;

class PointsRewards {
	public function addHooks() {
		add_filter( 'option_rewrite_rules', [ $this, 'addActionOnRewriteRules' ] );
	}

	public function addActionOnRewriteRules( $rules ) {
		if ( ! $rules ) {
			add_action( 'generate_rewrite_rules', [ $this, 'removeEndpointsTranslations' ], 1 );
		}
		return $rules;
	}

	public function removeEndpointsTranslations( &$wpRewrite ) {
		/**
		 * Removes translations of endpoints and rewrite rules WP_Rewrite object before saving rewrite options
		 *
		 * @param \WP_Rewrite $wp_rewrite Current WP_Rewrite instance (passed by reference).
		 */
		do_action_ref_array( 'wpml_remove_endpoints_translations', [ &$wpRewrite ] );
	}
}