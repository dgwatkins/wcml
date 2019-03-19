<?php

/**
 * @group ate
 */
class WCML_ATE_Activate_SynchronizationTest extends OTGS_TestCase {
	public function tearDown() {
		unset( $_GET['page'] );
		parent::tearDown();
	}


	/**
	 * @test
	 * @dataProvider dp
	 *
	 * @param string $page
	 */
	public function it_does_not_add_hooks_if_this_is_not_wcml_products_page( $page ) {
		if ( $page ) {
			$_GET['page'] = $page;
		}

		$subject = new WCML_ATE_Activate_Synchronization();

		\WP_Mock::expectFilterNotAdded( 'wpml_tm_load_ate_jobs_synchronization', '__return_true' );

		$subject->add_hooks();
	}

	public function dp() {
		return [
			'No page'        => [ null ],
			'Different page' => [ 'dashboard' ],
		];
	}

	/**
	 * @test
	 */
	public function it_adds_hooks_on_wcml_products_page() {
		$_GET['page'] = 'wpml-wcml';

		$subject = new WCML_ATE_Activate_Synchronization();

		\WP_Mock::expectFilterAdded( 'wpml_tm_load_ate_jobs_synchronization', '__return_true' );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function the_create_method_returns_the_class_itself() {
		$subject = new WCML_ATE_Activate_Synchronization();
		$this->assertSame( $subject, $subject->create() );
	}
}