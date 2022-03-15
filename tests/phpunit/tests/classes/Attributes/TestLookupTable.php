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

	private function getSubject( $sitepress = null ) {
		$this->sitepress = $sitepress ?: $this->getSitepress();

		return new LookupTable( $this->sitepress );
	}

	private function getSitepress() {
		return $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'is_original_content_filter' ] )
			->disableOriginalConstructor()
			->getMock();
	}

}
