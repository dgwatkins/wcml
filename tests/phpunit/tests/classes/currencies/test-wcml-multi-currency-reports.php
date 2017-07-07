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
	public function get_dashboard_currency_list_of_orders_ids() {

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb = $this->get_wpdb_mock();
		$subject = $this->get_subject( $woocommerce_wpml, $wpdb );

		$dashboard_currency = rand_str();

		$woocommerce_wpml->multi_currency = $this->get_multi_currency_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector = $this->get_wcml_admin_currency_selector_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector->method( 'get_cookie_dashboard_currency' )->willReturn( $dashboard_currency );

		$order_ids   = array();
		$order_ids[] = random_int( 1, 100 );
		$order_ids[] = random_int( 101, 200 );

		$wpdb->method( 'get_col' )->willReturn( $order_ids );

		\WP_Mock::wpFunction( 'wpml_prepare_in', array(
			'times' => 1,
			'args'  => array( $order_ids, '%d' ),
			'return' => implode(',' , $order_ids )
		) );

		$orders_ids_list = $subject->get_dashboard_currency_list_of_orders_ids();

		$this->assertEquals( implode( ',', $order_ids ), $orders_ids_list );
	}

	/**
	 * @test
	 */
	public function filter_dashboard_status_widget_sales_query_with_posts(){

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$woocommerce_wpml->multi_currency = $this->get_multi_currency_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector = $this->get_wcml_admin_currency_selector_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector
			->method( 'get_cookie_dashboard_currency' )
			->willReturn( rand_str() );

		$order_ids   = array();
		$order_ids[] = random_int( 1, 100 );
		$order_ids[] = random_int( 101, 200 );

		$wpdb = $this->get_wpdb_mock();
		$wpdb->method( 'get_col' )->willReturn( $order_ids );


		$subject = $this->get_subject( $woocommerce_wpml, $wpdb );

		$query = ['where' => rand_str() ];

		\WP_Mock::wpFunction( 'wpml_prepare_in', array(
			'times' => 1,
			'args'  => array( $order_ids, '%d' ),
			'return' => implode(',' , $order_ids )
		) );

		$fitered_query = $subject->filter_dashboard_status_widget_sales_query( $query );

		$this->assertSame( $query['where'] . ' AND posts.ID IN (' . implode(',', $order_ids ) . ') ', $fitered_query['where'] );

	}


	/**
	 * @test
	 * @group wcml-2064
	 */
	public function filter_dashboard_status_widget_sales_query_without_posts(){

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$woocommerce_wpml->multi_currency = $this->get_multi_currency_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector = $this->get_wcml_admin_currency_selector_mock();
		$woocommerce_wpml->multi_currency->admin_currency_selector
			->method( 'get_cookie_dashboard_currency' )
			->willReturn( rand_str() );

		$order_ids   = array();

		$wpdb = $this->get_wpdb_mock();
		$wpdb->method( 'get_col' )->willReturn( $order_ids );

		$subject = $this->get_subject( $woocommerce_wpml, $wpdb );

		$query = ['where' => rand_str() ];

		$fitered_query = $subject->filter_dashboard_status_widget_sales_query( $query );

		$this->assertSame( $query['where'], $fitered_query['where'] );
	}

}
