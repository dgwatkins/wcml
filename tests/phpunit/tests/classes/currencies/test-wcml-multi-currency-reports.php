<?php

class Test_WCML_Multi_Currency_Reports extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_Cache */
	private $wpml_cache;

	private $cached_data = array();

	public function setUp() {
		parent::setUp();

		$that = $this;

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array() )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();

		$this->wpml_cache = $this->getMockBuilder( 'WPML_WP_Cache' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get', 'set' ) )
		                         ->getMock();

		$this->wpml_cache->method( 'get' )->willReturnCallback( function ( $key, &$found ) use ( $that ) {
			if ( isset( $that->cached_data[ $key ] ) ) {
				$found = true;

				return $that->cached_data[ $key ];
			} else {
				return false;
			}
		} );

		$this->wpml_cache->method( 'set' )->willReturnCallback( function ( $key, $value ) use ( $that ) {
			$that->cached_data[ $key ] = $value;
		} );

	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @return WCML_Multi_Currency_Reports
	 */
	private function get_subject(){
		return new WCML_Multi_Currency_Reports( $this->woocommerce_wpml, $this->sitepress, $this->wpdb, $this->wpml_cache );
	}

	/**
	 * @test
	 */
	public function get_dashboard_currency_list_of_orders_ids(){

		$dashboard_currency = rand_str();

		$this->woocommerce_wpml->multi_currency                          = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                                                        ->disableOriginalConstructor()
		                                                                        ->getMock();
		$this->woocommerce_wpml->multi_currency->admin_currency_selector = $this->getMockBuilder( 'WCML_Admin_Currency_Selector' )
		                                                                        ->disableOriginalConstructor()
		                                                                        ->setMethods( array(
			                                                                        'get_cookie_dashboard_currency'
		                                                                        ) )
		                                                                        ->getMock();
		$this->woocommerce_wpml->multi_currency->admin_currency_selector->method( 'get_cookie_dashboard_currency' )->willReturn( $dashboard_currency );

		$order_id_1       = rand( 1, 100 );
		$order_id_2       = rand( 1, 100 );
		$orders_ids_array = array( $order_id_1, $order_id_2 );
		$this->wpdb->method( 'get_col' )->willReturn( $orders_ids_array );

		$subject = $this->get_subject();

		$orders_ids_list = $subject->get_dashboard_currency_list_of_orders_ids();

		$this->assertEquals( $order_id_1 . ',' . $order_id_2, $orders_ids_list );
	}
}
