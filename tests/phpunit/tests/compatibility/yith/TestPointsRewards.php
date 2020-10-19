<?php

namespace WCML\Compatibility\YITH;

class TestPointsRewards extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$subject = new PointsRewards();

		\WP_Mock::expectFilterAdded( 'option_rewrite_rules', [ $subject, 'addActionOnRewriteRules' ] );
		\WP_Mock::expectActionAdded( 'generate_rewrite_rules', [ $subject, 'removeEndpointsTranslations' ], 1 );

		$subject->addHooks();
		$subject->addActionOnRewriteRules( false );
	}
}