<?php

/**
 * Class Test_WCML_Multi_Currency_Reports
 * @group wcml-2957
 */
class Test_WCML_Multi_Currency_Reports extends OTGS_TestCase {

	public function tearDown() {
		unset( $_COOKIE['_wcml_reports_currency'], $_GET['page'] );

		return parent::tearDown();
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

	/**
	 * @test
	 */
	public function it_inits_reports() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb             = $this->get_wpdb_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $wpdb );

		$_GET['page'] = 'wc-reports';

		\WP_Mock::expectFilterAdded(
			'woocommerce_reports_get_order_report_query',
			[ $subject, 'admin_reports_query_filter', ]
		);

		$wcml_reports_set_currency_nonce = 'some_nonce';
		\WP_Mock::userFunction(
			'wp_create_nonce',
			[
				'args' => [ 'reports_set_currency' ],
				'times' => 1,
				'return' => $wcml_reports_set_currency_nonce,
			]
		);

		$js = "
                jQuery('#dropdown_shop_report_currency').on('change', function(){
                    jQuery('#dropdown_shop_report_currency_chosen').after('&nbsp;' + icl_ajxloaderimg);
                    jQuery('#dropdown_shop_report_currency_chosen a.chosen-single').css('color', '#aaa');
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '" . $wcml_reports_set_currency_nonce . "'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
            ";
		\WP_Mock::userFunction(
			'wc_enqueue_js',
			[
				'args' => [ $js ],
				'times' => 1,
			]
		);

		unset( $_COOKIE['_wcml_reports_currency'] );
		\WP_Mock::userFunction(
			'wcml_get_woocommerce_currency_option',
			[
				'args' => [],
				'times' => 1,
				'return' => 'EUR',
			]
		);

		$currency_code = 'EUR';

		$subject->reports_init();
	}

	/**
	 * @test
	 */
	public function it_inits_reports_and_get_reports_currency_from_cookie() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb             = $this->get_wpdb_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $wpdb );

		$_GET['page'] = 'wc-reports';

		\WP_Mock::expectFilterAdded(
			'woocommerce_reports_get_order_report_query',
			[ $subject, 'admin_reports_query_filter', ]
		);

		$wcml_reports_set_currency_nonce = 'some_nonce';
		\WP_Mock::userFunction(
			'wp_create_nonce',
			[
				'args' => [ 'reports_set_currency' ],
				'times' => 1,
				'return' => $wcml_reports_set_currency_nonce,
			]
		);

		$js = "
                jQuery('#dropdown_shop_report_currency').on('change', function(){
                    jQuery('#dropdown_shop_report_currency_chosen').after('&nbsp;' + icl_ajxloaderimg);
                    jQuery('#dropdown_shop_report_currency_chosen a.chosen-single').css('color', '#aaa');
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '" . $wcml_reports_set_currency_nonce . "'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
            ";
		\WP_Mock::userFunction(
			'wc_enqueue_js',
			[
				'args' => [ $js ],
				'times' => 1,
			]
		);

		$_COOKIE['_wcml_reports_currency'] = 'EUR';
		\WP_Mock::userFunction(
			'wcml_get_woocommerce_currency_option',
			[
				'args' => [],
				'times' => 0,
			]
		);

		$currency_code = 'EUR';

		$subject->reports_init();
	}

	/**
	 * @test
	 */
	public function it_inits_reports_and_uses_default_currency() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb             = $this->get_wpdb_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $wpdb );

		$_GET['page'] = 'wc-reports';

		\WP_Mock::expectFilterAdded(
			'woocommerce_reports_get_order_report_query',
			[ $subject, 'admin_reports_query_filter', ]
		);

		$wcml_reports_set_currency_nonce = 'some_nonce';
		\WP_Mock::userFunction(
			'wp_create_nonce',
			[
				'args' => [ 'reports_set_currency' ],
				'times' => 1,
				'return' => $wcml_reports_set_currency_nonce,
			]
		);

		$js = "
                jQuery('#dropdown_shop_report_currency').on('change', function(){
                    jQuery('#dropdown_shop_report_currency_chosen').after('&nbsp;' + icl_ajxloaderimg);
                    jQuery('#dropdown_shop_report_currency_chosen a.chosen-single').css('color', '#aaa');
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '" . $wcml_reports_set_currency_nonce . "'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
            ";
		\WP_Mock::userFunction(
			'wc_enqueue_js',
			[
				'args' => [ $js ],
				'times' => 1,
			]
		);

		unset( $_COOKIE['_wcml_reports_currency'] );
		\WP_Mock::userFunction(
			'wcml_get_woocommerce_currency_option',
			[
				'args' => [],
				'times' => 1,
				'return' => 'RUB',
			]
		);

		$currency_code = 'USD';

		$subject->reports_init();
	}

	/**
	 * @test
	 */
	public function it_does_NOT_init_reports_not_on_wc_reports_page() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb             = $this->get_wpdb_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $wpdb );

		unset( $_GET['page'] );

		\WP_Mock::expectFilterNotAdded(
			'woocommerce_reports_get_order_report_query',
			[ $subject, 'admin_reports_query_filter', ]
		);

		\WP_Mock::userFunction( 'wp_create_nonce', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'wc_enqueue_js', [ 'times' => 0 ] );

		unset( $_COOKIE['_wcml_reports_currency'] );
		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [ 'times' => 0 ] );
		$woocommerce_wpml->multi_currency = $this->get_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->never() )->method( 'get_currency_codes' );
		$woocommerce_wpml->multi_currency->expects( $this->never() )->method( 'get_default_currency' );

		\WP_Mock::expectFilterNotAdded(
			'woocommerce_currency_symbol',
			[ $subject, '_set_reports_currency_symbol', ]
		);

		$subject->reports_init();
	}

	/**
	 * @test
	 *
	 * @throws ReflectionException
	 */
	public function it_creates_reports_currency_selector() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$wpdb             = $this->get_wpdb_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $wpdb );

		$currency_codes = [ 'EUR', 'USD' ];

		$woocommerce_wpml->multi_currency = $this->get_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->once() )->method( 'get_currency_codes' )->willReturn( $currency_codes );

		\WP_Mock::userFunction(
			'get_woocommerce_currencies',
			[
				'args' => [],
				'times' => 1,
				'return' => [ 'USD' => 'Us Dollar', 'EUR' => 'Euro']
			]
		);

		\WP_Mock::userFunction(
			'remove_filter',
			[
				'args' => [ 'woocommerce_currency_symbol', [ $subject, '_set_reports_currency_symbol' ] ],
				'times' => 1,
			]
		);

		$reports_currency = 'EUR';
		$this->mock_property( $subject, 'reports_currency', $reports_currency );

		\WP_Mock::userFunction(
			'selected',
			[
				'args' => [ 'USD', $reports_currency ],
				'times' => 1,
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'selected',
			[
				'args' => [ 'EUR', $reports_currency ],
				'times' => 1,
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'get_woocommerce_currency_symbol',
			[
				'args' => [ 'USD' ],
				'times' => 1,
				'return' => '$',
			]
		);

		\WP_Mock::userFunction(
			'get_woocommerce_currency_symbol',
			[
				'args' => [ 'EUR' ],
				'times' => 1,
				'return' => '€',
			]
		);

		\WP_Mock::expectFilterAdded( 'woocommerce_currency_symbol', [ $subject, '_set_reports_currency_symbol' ] );

		$expected_selector = '        <select id="dropdown_shop_report_currency" style="margin-left:5px;">
							                    <option value="EUR" >
						Euro (€)                    </option>
				                    <option value="USD" >
						Us Dollar ($)                    </option>
							        </select>
		';

		ob_start();
		$subject->reports_currency_selector();
		$selector = ob_get_clean();

		$this->assertSame( $expected_selector, $selector );
	}

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
	 * Mock an object property.
	 *
	 * @param object $object        Object.
	 * @param string $property_name Property name.
	 * @param mixed  $value         Property vale.
	 *
	 * @throws ReflectionException Reflection exception.
	 */
	private function mock_property( $object, $property_name, $value ) {
		$reflection = new \ReflectionClass( $object );

		$property = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $value );
		$property->setAccessible( false );
	}
}
