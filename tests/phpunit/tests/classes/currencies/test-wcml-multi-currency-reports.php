<?php

/**
 * Class Test_WCML_Multi_Currency_Reports
 */
class Test_WCML_Multi_Currency_Reports extends OTGS_TestCase {

	private $cached_data = array();

	private function get_subject( $woocommerce_wpml = null, $wpdb = null ){

		if( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if( null === $wpdb ) {
			$wpdb = $this->get_wpdb_mock();
		}

		return new WCML_Multi_Currency_Reports(
			$woocommerce_wpml,
			$this->get_sitepress_mock(),
			$wpdb,
			$this->get_wpml_cache_mock()
		);

	}

	private function get_woocommerce_wpml_mock(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->setMethods( array() )
		            ->getMock();
	}

	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( array() )
		            ->getMock();
	}

	private function get_wpdb_mock() {
		return $this->stubs->wpdb();
	}

	private function get_wpml_cache_mock() {
		$that = $this;

		$wpml_cache_mock = $this->getMockBuilder( 'WPML_Cache' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get', 'set' ) )
		                        ->getMock();

		$wpml_cache_mock->method( 'get' )->willReturnCallback(
			function ( $key ) use ( $that ) {
				if ( isset( $that->cached_data[ $key ] ) ) {
					$found = true;

					return $that->cached_data[ $key ];
				} else {
					return false;
				}
			}
		);

		$wpml_cache_mock->method( 'set' )->willReturnCallback(
			function ( $key, $value ) use ( $that ) {
				$that->cached_data[ $key ] = $value;
			}
		);

		return $wpml_cache_mock;
	}

	private function get_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->setMethods( array() )
		            ->getMock();
	}

	private function get_wcml_admin_currency_selector_mock() {
		return $this->getMockBuilder( 'WCML_Admin_Currency_Selector' )
		            ->disableOriginalConstructor()
		            ->setMethods( array(
			            'get_cookie_dashboard_currency'
		            ) )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function it_should_filter_dashboard_status_widget_sales_query() {

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb = $this->get_wpdb_mock();
		$dashboard_currency = 'EUR';

		$woocommerce_wpml->multi_currency = $this->get_multi_currency_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector = $this->get_wcml_admin_currency_selector_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector->method( 'get_cookie_dashboard_currency' )->willReturn( $dashboard_currency );

		$expected_query = array(
			'join' => " INNER JOIN {$wpdb->postmeta} AS currency_postmeta ON posts.ID = currency_postmeta.post_id",
			'where' => $wpdb->prepare( " AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = %s", $dashboard_currency )
		);

		$subject = $this->get_subject( $woocommerce_wpml, $wpdb );
		$filtered_query = $subject->filter_dashboard_status_widget_sales_query( array( 'join' => '', 'where' => '' ) );

		$this->assertEquals( $expected_query, $filtered_query );
	}

}
