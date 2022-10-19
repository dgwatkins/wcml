<?php

namespace WCML\AdminTexts;

use WPML\FP\Lst;

class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = new Hooks();

		\WP_Mock::expectFilterAdded( 'wpml_st_blacklisted_options', Lst::append( 'woocommerce_permalinks' ) );

		$subject->add_hooks();
	}

}
