<?php

/**
 * @author OnTheGo Systems
 * @group  endpoints
 * @group  wpmlsl-73
 */
class Test_Endpoints extends OTGS_TestCase {
	private $active_languages = array();
	private $current_language;
	private $endpoints_translations = array();
	private $my_account_page_title;
	private $query_vars = array();

	function setUp() {
		parent::setUp();

		$this->current_language      = 'en';
		$this->my_account_page_title = 'my-account';

		if ( ! defined( 'WCML_MULTI_CURRENCIES_DISABLED' ) ) {
			define( 'WCML_MULTI_CURRENCIES_DISABLED', false );
		}
		if ( ! defined( 'WCML_CART_SYNC' ) ) {
			define( 'WCML_CART_SYNC', true );
		}

		$this->active_languages = array(
			'en' => array(),
			'it' => array(),
			'fr' => array(),
		);

		$this->query_vars = array(
			'endpoint-1' => 'endpoint-1',
			'endpoint-2' => 'endpoint-2',
			'endpoint-3' => 'endpoint-3',
			'endpoint-4' => 'endpoint-4',
		);

		$this->build_endpoints();
		$this->mock_generic_functions();
		$this->mock_my_account_page();
	}

	/**
	 * @test
	 */
	function it_adds_blacklisted_endpoints() {
		$expected = array();
		foreach ( $this->endpoints_translations as $code => $translated_query_vars ) {
			if ( $code !== 'en' ) {
				$expected[] = $this->my_account_page_title . '-' . $code;
				$expected[] = '/^' . $this->my_account_page_title . '-' . $code . '/';
			} else {
				$expected[] = $this->my_account_page_title;
				$expected[] = '/^' . $this->my_account_page_title . '/';
			}

			/** @var array $translated_query_vars */
			foreach ( $translated_query_vars as $key => $endpoint ) {
				if ( $code !== 'en' ) {
					$expected[] = $this->my_account_page_title . '-' . $code . '/' . $endpoint;
				} else {
					$expected[] = $this->my_account_page_title . '/' . $endpoint;
				}
			}
		}

		$subject = $this->get_subject();

		$that = $this;

		foreach ( $this->query_vars as $key => $value ){
			foreach( $this->active_languages as $language => $lang_data ){
				\WP_Mock::onFilter( 'wpml_get_endpoint_translation' )->with( $key, $value, $language )->reply( $this->get_endpoint_translation( $key, $language ) );
			}
		}

		$actual = $subject->reserved_requests( array() );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @test
	 */
	function it_adds_no_blacklisted_endpoints() {

		$this->query_vars = array();

		$expected = array();
		foreach ( $this->endpoints_translations as $code => $translated_query_vars ) {
			if ( $code !== 'en' ) {
				$expected[] = $this->my_account_page_title . '-' . $code;
				$expected[] = '/^' . $this->my_account_page_title . '-' . $code . '/';
			} else {
				$expected[] = $this->my_account_page_title;
				$expected[] = '/^' . $this->my_account_page_title . '/';
			}
		}

		$subject = $this->get_subject();

		$actual = $subject->reserved_requests( array() );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @test
	 */
	function it_adds_no_blacklisted_endpoints_for_display_pages_as_translated() {

		$this->query_vars = array();

		$expected = array();
		foreach ( $this->endpoints_translations as $code => $translated_query_vars ) {
			if ( $code !== 'en' ) {
				$expected[] = $this->my_account_page_title . '-' . $code;
				$expected[] = '/^' . $this->my_account_page_title . '-' . $code . '/';
			} else {
				$expected[] = $this->my_account_page_title;
				$expected[] = '/^' . $this->my_account_page_title . '/';
			}
		}

		$subject = $this->get_subject( null, $this->get_sitepress( true ) );

		$actual = $subject->reserved_requests( array() );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @test
	 */
	public function it_should_filter_get_endpoint_url() {

		$url                     = rand_str();
		$endpoint                = 'endpoint-1';
		$this->current_language  = 'fr';
		$translated_endpoint     = $this->get_endpoint_translation( $endpoint, $this->current_language );
		$value                   = rand_str();
		$permalink               = rand_str();
		$translated_endpoint_url = rand_str();
		$wc_account_page_url     = rand_str();


		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods(
			array(
				'convert_url'
			)
		)->getMock();

		$sitepress->method( 'convert_url' )->willReturn( $permalink );

		$subject = $this->get_subject( null, $sitepress );

		WP_Mock::onFilter( 'wpml_get_endpoint_translation' )->with( $endpoint, $endpoint, null )->reply( $translated_endpoint );
		WP_Mock::wpFunction( 'remove_filter', array( 'times' => 1, 'return' => true ) );
		WP_Mock::wpFunction(
			'wc_get_endpoint_url',
			array(
				'args'   => array( $translated_endpoint, $value, $permalink ),
				'return' => $translated_endpoint_url
			)
		);

		\WP_Mock::expectFilterAdded( 'woocommerce_get_endpoint_url', array(
			$subject,
			'filter_get_endpoint_url'
		), 10, 4 );

		$filtered_endpoint_url = $subject->filter_get_endpoint_url( $url, $endpoint, $value, $permalink );

		$this->assertEquals( $translated_endpoint_url, $filtered_endpoint_url );
	}

	private function get_subject( $woocommerce_wpml = null, $sitepress = null, $wpdb = null ) {
		if ( ! $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}
		if ( ! $sitepress ) {
			$sitepress = $this->get_sitepress();
		}
		if ( ! $wpdb ) {
			$wpdb = $this->stubs->wpdb();
		}

		return new WCML_Endpoints( $woocommerce_wpml, $sitepress, $wpdb );
	}


	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|woocommerce_wpml
	 */
	private function get_woocommerce_wpml() {
		/** @var woocommerce_wpml|PHPUnit_Framework_MockObject_MockObject $wcml */
		$wcml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->setMethods(
			array(
				'get_wc_query_vars',
			)
		)->getMock();
		$wcml->method( 'get_wc_query_vars' )->willReturn( $this->query_vars );

		return $wcml;
	}

	/**
	 * @return SitePress|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_sitepress( $is_display_as_translated = false ) {
		$that = $this;
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods(
			array(
				'get_current_language',
				'get_active_languages',
				'switch_lang',
				'is_display_as_translated_post_type'
			)
		)->getMock();
		$sitepress->method( 'get_current_language' )->willReturnCallback(
			function () use ( $that ) {
				return $that->current_language;
			}
		);
		$sitepress->method( 'get_active_languages' )->willReturn( $this->active_languages );
		$sitepress->method( 'is_display_as_translated_post_type' )->willReturn( $is_display_as_translated );
		$sitepress->method( 'switch_lang' )->willReturnCallback(
			function ( $language ) use ( $that ) {
				$that->current_language = $language;
			}
		);

		return $sitepress;
	}

	private function mock_my_account_page() {
		$that = $this;

		$woocommerce_my_account_page_id = mt_rand( 1, 100 );
		/** @noinspection SpellCheckingInspection */
		WP_Mock::wpFunction(
			'wc_get_page_id',
			array(
				'args'   => array( 'myaccount' ),
				'return' => $woocommerce_my_account_page_id,
			)
		);

		WP_Mock::wpFunction(
			'get_post',
			array(
				'args'   => array( $woocommerce_my_account_page_id ),
				'return' => function () use ( $that ) {
					$my_account_page = $that->getMockBuilder( 'WP_Post' )->disableOriginalConstructor();
					/** @noinspection PhpUndefinedFieldInspection */
					$my_account_page->post_name = $that->my_account_page_title;
					if ( $that->current_language !== 'en' ) {
						/** @noinspection PhpUndefinedFieldInspection */
						$my_account_page->post_name = $that->my_account_page_title . '-' . $that->current_language;
					}

					return $my_account_page;
				},
			)
		);
	}


	function get_endpoint_translation( $key, $language ) {

		return $this->endpoints_translations[ $language ] [ $key ];
	}

	private function build_endpoints() {
		$this->endpoints_translations = array(
			'en' => $this->query_vars,
		);

		foreach ( $this->active_languages as $code => $language ) {
			if ( $code !== 'en' ) {
				$translated_query_vars = array();
				foreach ( $this->query_vars as $key => $endpoint ) {
					$translated_query_vars[ $key ] = $code . '-' . $endpoint;
				}
				$this->endpoints_translations[ $code ] = $translated_query_vars;
			}
		}

	}

	private function mock_generic_functions() {
		/** @noinspection PhpUnusedParameterInspection */
		/** @noinspection MoreThanThreeArgumentsInspection */
		WP_Mock::wpFunction(
			'wp_cache_get',
			array(
				'args'   => array( 'reserved_requests', 'wpml-endpoints', false, '*' ),
				'return' => function ( $cache_key, $cache_group, $force, $found ) {
					return null;
				}
			)
		);
		WP_Mock::wpFunction(
			'is_admin',
			array( 'return' => false )
		);
		WP_Mock::wpFunction(
			'get_option',
			array(
				'args'   => array( 'flush_rules_for_endpoints_translations' ),
				'return' => false,
			)
		);
		WP_Mock::wpFunction( 'wp_cache_set' );
	}
}
