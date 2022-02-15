<?php

namespace WCML\MultiCurrency\Resolver;

use tad\FunctionMocker\FunctionMocker;
use WC_Product;
use WCML\MultiCurrency\Settings;
use WP_Mock;
use WPML\FP\Fns;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestResolverForContext extends \OTGS_TestCase {

	const DEFAULT_WC_CURRENCY = 'EUR';

	/**
	 * @var array[]
	 */
	private $bkSuperGlobals;

	public function setUp() {
		parent::setUp();
		$this->bkSuperGlobals = [
			'SERVER'  => $_SERVER,
			'REQUEST' => $_REQUEST,
			'GET'     => $_GET,
		];

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option' )->andReturn( self::DEFAULT_WC_CURRENCY );
		self::mockIsDisplayOnlyCustomPrices( false );
	}

	public function tearDown() {
		$_SERVER  = $this->bkSuperGlobals['SERVER'];
		$_REQUEST = $this->bkSuperGlobals['REQUEST'];
		$_GET     = $this->bkSuperGlobals['GET'];
		unset( $_COOKIE );
		parent::tearDown();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyOnWooCommerceQuickEdit() {
		$_REQUEST['woocommerce_quick_edit'] = 'something';

		$this->assertEquals( self::DEFAULT_WC_CURRENCY, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 * @dataProvider dpShouldGetCurrencyIfMissingCustomPrice
	 *
	 * @param int    $productId
	 * @param string $productType
	 * @param array  $children
	 * @param array  $postMeta
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyIfMissingCustomPrice( $productId, $productType, $children, $postMeta ) {
		$postType                = 'product';
		$originalProductLanguage = 'fr';

		self::mockIsDisplayOnlyCustomPrices( true );

		WP_Mock::userFunction( 'is_product' )->andReturn( true );

		foreach ( array_merge( [ $productId ], $children ) as $itemId ) {
			WP_Mock::userFunction( 'get_post_type' )->with( $itemId )->andReturn( $postType );
		}

		$getOriginalProductLanguage = function( $id ) use ( $productId, $originalProductLanguage ) {
			return $productId === $id ? $originalProductLanguage : null;
		};

		WP_Mock::onFilter( 'wpml_object_id' )
		       ->with( $productId, $postType, true, $originalProductLanguage )
		       ->reply( $productId );

		foreach ( $postMeta as $metaPostId => $metaValue ) {
			WP_Mock::userFunction( 'get_post_meta' )
			       ->with( $metaPostId, '_wcml_custom_prices_status', true )
			       ->andReturn( $metaValue );
		}

		self::mockCurrentProduct( $productId, $productType, $children );

		$this->assertEquals(
			self::DEFAULT_WC_CURRENCY,
			self::getSubject( $getOriginalProductLanguage )->getClientCurrency()
		);
	}

	/**
	 * @param int    $id
	 * @param string $type
	 * @param int[]  $children
	 *
	 * @return void
	 */
	private function mockCurrentProduct( $id, $type = 'simple', $children = [] ) {
		$product = $this->getMockBuilder( WC_Product::class )
		                ->setMethods( [ 'get_type', 'get_children', 'get_id' ] )
		                ->getMock();
		$product->method( 'get_id' )->willReturn( $id );
		$product->method( 'get_type' )->willReturn( $type );
		$product->method( 'get_children' )->willReturn( $children );

		WP_Mock::userFunction( 'wc_get_product' )->andReturn( $product );
	}

	public function dpShouldGetCurrencyIfMissingCustomPrice() {
		$productId = 123;
		$child1    = 456;
		$child2    = 457;

		return [
			'simple product' => [
				$productId,
				'simple',
				[],
				[
					$productId => '',
				],
			],
			'variable product' => [
				$productId,
				'variable',
				[
					$child1,
					$child2,
				],
				[
					$productId => '',
					$child1    => 'something',
					$child2    => '',
				],
			],
		];
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyOnPayForOrder() {
		$key           = 'some_key';
		$cacheKey      = 'order' . $key;
		$cacheGroup    = 'wcml_client_currency';
		$orderId       = 987654;
		$orderCurrency = 'USD';

		$_GET = [
			'pay_for_order' => true,
			'key'           => $key,
		];

		WP_Mock::passthruFunction( 'sanitize_text_field' );
		WP_Mock::passthruFunction( 'wc_clean' );
		WP_Mock::passthruFunction( 'wp_unslash' );

		WP_Mock::userFunction( 'wp_cache_get' )->with( $cacheKey, $cacheGroup )->andReturn( false );

		WP_Mock::userFunction( 'wc_get_order_id_by_order_key' )->with( $key )->andReturn( $orderId );
		WP_Mock::userFunction( 'get_post_meta' )->with( $orderId, '_order_currency', true )->andReturn( $orderCurrency );

		WP_Mock::userFunction( 'wp_cache_set' )->once()->with( $cacheKey, $orderCurrency, $cacheGroup );

		$this->assertEquals( $orderCurrency, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyOnPayForOrderFromCache() {
		$key           = 'some_key';
		$cacheKey      = 'order' . $key;
		$cacheGroup    = 'wcml_client_currency';
		$orderCurrency = 'USD';

		$_GET = [
			'pay_for_order' => true,
			'key'           => $key,
		];

		WP_Mock::passthruFunction( 'sanitize_text_field' );

		WP_Mock::userFunction( 'wp_cache_get' )->with( $cacheKey, $cacheGroup )->andReturn( $orderCurrency );
		WP_Mock::userFunction( 'wp_cache_set' )->never();

		$this->assertEquals( $orderCurrency, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyOnSearchProductsFromOrderCurrencyCookie() {
		$orderCurrency = 'USD';

		$_GET['action']                  = 'woocommerce_json_search_products_and_variations';
		$_COOKIE['_wcml_order_currency'] = $orderCurrency;

		$this->assertEquals( $orderCurrency, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyFromHttpRefererShopOrderCurrency() {
		$orderId       = 123;
		$orderCurrency = 'USD';

		$_SERVER['HTTP_REFERER'] = "https://example.com/foo/bar/?foo=bar&post=$orderId";

		WP_Mock::userFunction( 'get_post_type' )->with( $orderId )->andReturn( 'shop_order' );
		WP_Mock::userFunction( 'get_post_meta' )->with( $orderId, '_order_currency', true )->andReturn( $orderCurrency );

		$this->assertEquals( $orderCurrency, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCurrencyAndReturnNullIfNoConditionPasses() {
		$this->assertNull( self::getSubject()->getClientCurrency() );
	}

	/**
	 * @param callable|null $getOriginalProductLanguage
	 *
	 * @return ResolverForContext
	 */
	private static function getSubject( callable $getOriginalProductLanguage = null ) {
		$getOriginalProductLanguage = $getOriginalProductLanguage ?: Fns::always( null );

		return new ResolverForContext( $getOriginalProductLanguage );
	}

	/**
	 * @param bool $bool
	 *
	 * @return void
	 */
	private static function mockIsDisplayOnlyCustomPrices( $bool ) {
		FunctionMocker::replace( Settings::class . '::isDisplayOnlyCustomPrices', $bool );
	}
}
