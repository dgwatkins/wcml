<?php

namespace WCML\Utilities;

use WPML\FP\Relation;

class Jobs {

	/**
	 * @todo Use with \WPML\TM\API\Jobs::getPostJobs when WPML 4.5.0 is required.
	 *
	 * @param int    $productId
	 * @param string $lang
	 *
	 * @return \stdClass|false
	 */
	public static function getProductJob( $productId, $lang ) {
		/** @var \SitePress $sitepress */
		global $sitepress;

		$job_factory = wpml_tm_load_job_factory();
		$trid        = $sitepress->get_element_trid( $productId, 'post_' . get_post_type( $productId ) );

		return $job_factory->get_translation_job( $job_factory->job_id_by_trid_and_lang( $trid, $lang ) );
	}

	/**
	 * @param object|array|false $job
	 *
	 * @return bool
	 */
	public static function isAte( $job ) {
		return $job && Relation::propEq( 'editor', 'ate', $job );
	}
}
