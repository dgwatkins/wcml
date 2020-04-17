<?php

namespace WCML\AdminDashboard;

/**
 * @group admin-dashboard
 */
class TestHooks extends \OTGS_TestCase {

	private function getSubject( $sitepress = null, $wpdb = null ) {
		$sitepress = $sitepress ?: $this->getSitepress();
		$wpdb      = $wpdb ?: $this->getWpdb();

		return new Hooks( $sitepress, $wpdb );
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
		            ->setMethods(
			            [
				            'get_current_language',
			            ]
		            )->disableOriginalConstructor()->getMock();
	}

	private function getWpdb() {
		return $this->stubs->wpdb();
	}

	/**
	 * @test
	 */
	public function itShouldLoadOnBackend() {
		$subject = $this->getSubject();
		$this->assertInstanceOf( \IWPML_Backend_Action::class, $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();
		\WP_Mock::expectActionAdded( 'wp_dashboard_setup', [ $subject, 'clearStockTransients' ] );

		\WP_Mock::expectFilterAdded( 'woocommerce_status_widget_low_in_stock_count_query', [ $subject, 'addLanguageQuery' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_status_widget_out_of_stock_count_query', [ $subject, 'addLanguageQuery' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldClearStockTransients() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'delete_transient', [
			'args'   => [ 'wc_outofstock_count' ],
			'times'  => 1,
			'return' => true,
		] );

		\WP_Mock::userFunction( 'delete_transient', [
			'args'   => [ 'wc_low_stock_count' ],
			'times'  => 1,
			'return' => true,
		] );

		$subject->clearStockTransients();
	}

	/**
	 * @test
	 */
	public function itShouldAddLanguageQuery() {

		$currentLanguage = 'en';
		$wpdb            = $this->getWpdb();
		$sitepress       = $this->getSitepress();

		$query         = "SELECT COUNT( product_id ) FROM {$wpdb->prefix}wc_product_meta_lookup WHERE stock_quantity < 0";
		$languageQuery = $wpdb->prepare( " INNER JOIN {$wpdb->prefix}icl_translations AS t
                ON posts.ID = t.element_id AND t.element_type IN ( 'post_product', 'post_product_variation' )
                WHERE t.language_code = %s AND ",
			$currentLanguage );

		$expectedQuery = str_replace( "WHERE", $languageQuery, $query );

		$sitepress->method( 'get_current_language' )->willReturn( $currentLanguage );

		$subject = $this->getSubject( $sitepress, $wpdb );

		$this->assertEquals( $expectedQuery, $subject->addLanguageQuery( $query ) );
	}

	/**
	 * @test
	 */
	public function itShouldNotAddLanguageQuery() {

		$currentLanguage = 'all';
		$sitepress       = $this->getSitepress();
		$query           = rand_str();

		$sitepress->method( 'get_current_language' )->willReturn( $currentLanguage );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals( $query, $subject->addLanguageQuery( $query ) );
	}

}
