<?php

namespace WCML\Attributes;

use WPML\FP\Obj;
use WPML\LIB\WP\OnActionMock;
use Automattic\WooCommerce\Container as WcContainer;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore as ProductAttributesLookupDataStore;

/**
 * @group wcml-3916
 */
class TestLookupTable extends \OTGS_TestCase {

	use OnActionMock;

	/** @var SitePress $sitepress */
	private $sitepress;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itTriggersUpdateForPublishedProductTranslations() {
		$subject = $this->getSubject();

		$productId = 11;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $productId,
			'return' => 'product',
		] );

		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => $productId,
			'return' => 'publish',
		] );

		$this->sitepress->method( 'is_original_content_filter' )
			->with( false, $productId, 'post_product' )
			->willReturn( false );

		\WP_Mock::expectFilterAdded( 'woocommerce_product_get_attributes', [ $subject, 'translateAttributeOptions' ], 10, 2 );

		\WP_Mock::userFunction( 'remove_filter', [
			'args'   => [ 'terms_clauses', [ $this->sitepress, 'terms_clauses' ] ],
			'return' => true,
		] );

		$lookupDataStore = $this->getMockBuilder( ProductAttributesLookupDataStore::class )
			->setMethods( [ 'on_product_changed' ] )
			->disableOriginalConstructor()
			->getMock();

		$lookupDataStore->expects( $this->once() )
			->method( 'on_product_changed' )
			->with( $productId );

		$container = $this->getMockBuilder( WcContainer::class )
			->setMethods( [ 'get' ] )
			->disableOriginalConstructor()
			->getMock();

		$container->method( 'get' )
			->with( ProductAttributesLookupDataStore::class )
			->willReturn( $lookupDataStore );

		\WP_Mock::userFunction( 'wc_get_container', [
			'return' => $container,
		] );

		\WP_Mock::expectFilterAdded( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ], 10, 3 );

		\WP_Mock::userFunction( 'remove_filter', [
			'args'   => [ 'woocommerce_product_get_attributes', [ $subject, 'translateAttributeOptions' ] ],
			'return' => true,
		] );

		$subject->add_hooks();

		$this->runAction( 'save_post', $productId );
		$this->runAction( 'shutdown' );
	}

	/**
	 * @test
	 */
	public function itDoesNotTriggersUpdateForNonProducts() {
		$subject = $this->getSubject();

		$productId = 11;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $productId,
			'return' => 'post',
		] );

		\WP_Mock::userFunction( 'wc_get_container' )->never();

		$subject->add_hooks();

		$this->runAction( 'save_post', $productId );
		$this->runAction( 'shutdown' );
	}

	/**
	 * @test
	 */
	public function itDoesNotTriggersUpdateForNonPublishedProducts() {
		$subject = $this->getSubject();

		$productId = 11;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $productId,
			'return' => 'product',
		] );

		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => $productId,
			'return' => 'draft',
		] );

		\WP_Mock::userFunction( 'wc_get_container' )->never();

		$subject->add_hooks();

		$this->runAction( 'save_post', $productId );
		$this->runAction( 'shutdown' );
	}

	/**
	 * @test
	 */
	public function itDoesNotTriggersUpdateForOriginalProducts() {
		$subject = $this->getSubject();

		$productId = 11;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $productId,
			'return' => 'product',
		] );

		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => $productId,
			'return' => 'publish',
		] );

		$this->sitepress->method( 'is_original_content_filter' )
			->with( false, $productId, 'post_product' )
			->willReturn( true );

		\WP_Mock::userFunction( 'wc_get_container' )->never();

		$subject->add_hooks();

		$this->runAction( 'save_post', $productId );
		$this->runAction( 'shutdown' );
	}

	/**
	 * @test
	 */
	public function itRegeneratesTable() {
		$steps   = 10;
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'woocommerce_product_object_query_args', Obj::assoc( 'suppress_filters', true ) );

		$subject->add_hooks();

		$this->assertEquals( $steps, $this->runFilter( 'woocommerce_attribute_lookup_regeneration_step_size', $steps ) );
	}

	/**
	 * @test
	 */
	public function itTranslatesAttributeOptions() {
		$productId = 11;
		$lang      = 'it';
		$taxonomy  = 'pa_colors';
		$termId    = 123;
		$transTerm = 456;
		$options   = [
			$termId,
		];
		$expected  = [
			$transTerm,
		];

		$attribute = $this->getMockBuilder( 'WC_Product_Attribute' )
			->setMethods( [ 'set_options', 'get_options' ] )
			->disableOriginalConstructor()
			->getMock();

		$attribute->method( 'get_options' )
			->willReturnCallback( function() {
				return $this->options;
			} );

		$attribute->method( 'set_options' )
			->willReturnCallback( function( $set ) {
				$this->options = $set;
			} );
		$attribute->set_options( $options );

		$attrs = [
			$taxonomy => $attribute,
		];

		$product = $this->getMockBuilder( 'WC_Product' )
			->setMethods( [ 'get_id' ] )
			->disableOriginalConstructor()
			->getMock();

		$product->method( 'get_id' )
			->willReturn( $productId );

		$subject = $this->getSubject();

		$this->sitepress->method( 'get_language_for_element' )
			->with( $productId, 'post_product' )
			->willReturn( $lang );
		$this->sitepress->method( 'get_object_id' )
			->with( $termId, $taxonomy, false, $lang )
			->willReturn( $transTerm );

		$result = $subject->translateAttributeOptions( $attrs, $product );

		$this->assertSame( $expected, reset($result)->get_options() );
	}

	private function getSubject( $sitepress = null ) {
		$this->sitepress = $sitepress ?: $this->getSitepress();

		return new LookupTable( $this->sitepress );
	}

	private function getSitepress() {
		return $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'is_original_content_filter', 'get_language_for_element', 'get_object_id' ] )
			->disableOriginalConstructor()
			->getMock();
	}

}
