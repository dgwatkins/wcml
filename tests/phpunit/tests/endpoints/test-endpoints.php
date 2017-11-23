<?php

/**
 * @author OnTheGo Systems
 * @group  endpoints
 * @group  wpmlsl-73
 */
class Test_Endpoints extends OTGS_TestCase {
	private $active_languages       = array();
	private $current_language;
	private $endpoints_translations = array();
	private $my_account_page_title;
	private $query_vars             = array();

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
		$this->mock_wpml_translate_single_string_filter();
		$this->mock_generic_functions();
		$this->mock_my_account_page();
		$this->mock_icl_get_string_id();
		$this->mock_wpml_translate_single_string();
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

		$wcml      = $this->get_wcml();
		$sitepress = $this->get_sitepress();

		$subject = new WCML_Endpoints( $wcml );

		$actual = $subject->reserved_requests( array(), $sitepress );

		$this->assertSame( $expected, $actual );
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

		$wcml      = $this->get_wcml();
		$sitepress = $this->get_sitepress();

		$subject = new WCML_Endpoints( $wcml );

		$actual = $subject->reserved_requests( array(), $sitepress );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @test
	 */
	public function lost_password_filter_get_endpoint_url(){

		$wcml      = $this->get_wcml();
		$subject = new WCML_Endpoints( $wcml );

		$url = rand_str();
		$endpoint = rand_str();
		$translated_endpoint = rand_str();
		$value = rand_str();
		$permalink = rand_str();
		$translated_endpoint_url = rand_str();
		$wc_account_page_url = rand_str();

		WP_Mock::wpFunction(
			'get_option',
			array(
				'args'   => array( 'woocommerce_myaccount_lost_password_endpoint' ),
				'return' => $endpoint
			)
		);

		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 1, 'return' => true ) );
		\WP_Mock::wpFunction( 'WC', array( 'times' => 1, 'return' => false ) );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $endpoint, 'WooCommerce Endpoints', 'lost-password' )
		       ->reply( $translated_endpoint );

		WP_Mock::wpFunction(
			'wc_get_page_permalink',
			array(
				'args'   => array( 'myaccount' ),
				'return' => $wc_account_page_url
			)
		);

		WP_Mock::wpFunction(
			'wc_get_endpoint_url',
			array(
				'args'   => array( $translated_endpoint, '', $wc_account_page_url ),
				'return' => $translated_endpoint_url
			)
		);

		\WP_Mock::expectFilterAdded( 'woocommerce_get_endpoint_url', array( $subject, 'filter_get_endpoint_url' ), 10, 4 );

		$filtered_endpoint_url = $subject->filter_get_endpoint_url( $url, $endpoint, $value, $permalink );

		$this->assertEquals( $translated_endpoint_url, $filtered_endpoint_url );
	}

	/**
	 * @test
	 */
	public function default_endpoint_filter_get_endpoint_url(){

		$url = rand_str();
		$endpoint = rand_str();
		$value = rand_str();
		$permalink = rand_str();

		$wcml      = $this->get_wcml();
		$subject = new WCML_Endpoints( $wcml );

		WP_Mock::wpFunction(
			'get_option',
			array(
				'args'   => array( 'woocommerce_myaccount_lost_password_endpoint' ),
				'return' => rand_str()
			)
		);

		$filtered_endpoint_url = $subject->filter_get_endpoint_url( $url, $endpoint, $value, $permalink );

		$this->assertEquals( $url, $filtered_endpoint_url );

	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|woocommerce_wpml
	 */
	private function get_wcml() {
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
	private function get_sitepress() {
		$that = $this;
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $sitepress */
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->setMethods(
			array(
				'get_current_language',
				'get_active_languages',
				'switch_lang',
			)
		)->getMock();
		$sitepress->method( 'get_current_language' )->willReturnCallback(
			function () use ( $that ) {
				return $that->current_language;
			}
		);
		$sitepress->method( 'get_active_languages' )->willReturn( $this->active_languages );
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
			'get_option',
			array(
				'args'   => array( 'woocommerce_myaccount_page_id' ),
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

	private function mock_icl_get_string_id() {
		$that = $this;

		WP_Mock::wpFunction( 'icl_t' );
		/** @noinspection PhpUnusedParameterInspection */
		WP_Mock::wpFunction(
			'icl_get_string_id',
			array(
				'return' => function ( $string, $context, $name ) use ( $that ) {
					return $that->get_endpoint_translation( $name, $that->current_language );
				},
			)
		);
	}

	private function mock_wpml_translate_single_string() {
		$that = $this;
		/** @noinspection PhpUnusedParameterInspection */
		/** @noinspection MoreThanThreeArgumentsInspection */
		WP_Mock::onFilter( 'wpml_translate_single_string' )->with( Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any() )->reply(
			function ( $endpoint, $context, $key, $language ) use ( $that ) {
				return $that->get_endpoint_translation( $key, $language );
			}
		);
	}

	private function get_endpoint_translation( $key, $language ) {
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

	private function mock_wpml_translate_single_string_filter() {
		foreach ( $this->endpoints_translations as $code => $translated_query_vars ) {
			/** @var array $translated_query_vars */
			foreach ( $translated_query_vars as $key => $endpoint ) {
				WP_Mock::onFilter( 'wpml_translate_single_string' )->with(
					$this->endpoints_translations['en'][ $key ],
					'WooCommerce Endpoints',
					$key,
					$code
				)->reply( $endpoint );
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
