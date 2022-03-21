<?php

namespace WCML\Utilities;

use tad\FunctionMocker\FunctionMocker;
use WCML_Capabilities;
use WP_Mock;

/**
 * @group wcml-3848
 */
class TestAdminPages extends \OTGS_TestCase {

	public function setUp() {
		parent::setUp();
		WP_Mock::passthruFunction( 'sanitize_key' );
	}

	public function tearDown() {
		$_GET = [];
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpIsTab
	 *
	 * @param array        $_get
	 * @param string|array $tabs
	 * @param bool         $expected
	 *
	 * @return void
	 */
	public function testIsTab( $_get, $tabs, $expected ) {
		$_GET = $_get;
		$this->assertSame( $expected, AdminPages::isTab( $tabs ) );
	}

	public function dpIsTab() {
		return [
			[ [ 'tab' => 'foo' ], 'foo', true ],
			[ [ 'tab' => 'foo' ], [ 'foo', 'bar' ], true ],
			[ [], 'foo', false ],
			[ [ 'tab' => 'other' ], 'foo', false ],
			[ [ 'tab' => 'other' ], [ 'foo', 'bar' ], false ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpIsPage
	 *
	 * @param array        $_get
	 * @param string|array $page
	 * @param bool         $expected
	 *
	 * @return void
	 */
	public function testIsPage( $_get, $page, $expected ) {
		$_GET = $_get;
		$this->assertSame( $expected, AdminPages::isPage( $page ) );
	}

	public function dpIsPage() {
		return [
			[ [ 'page' => 'foo' ], 'foo', true ],
			[ [], 'foo', false ],
			[ [ 'page' => 'other' ], 'foo', false ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpIsMultiCurrency
	 *
	 * @param array $_get
	 * @param bool  $isStandalone
	 * @param bool  $expected
	 *
	 * @return void
	 */
	public function testIsMultiCurrency( $_get, $isStandalone, $expected ) {
		$_GET = $_get;

		WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( $isStandalone );

		$this->assertSame( $expected, AdminPages::isMultiCurrency() );
	}

	public function dpIsMultiCurrency() {
		return [
			'full mode, not wcml page' => [
				[ 'page' => 'other' ],
				false,
				false,
			],
			'full mode, wcml page, not mc' => [
				[ 'page' => 'wpml-wcml', 'tab'  => 'other' ],
				false,
				false,
			],
			'full mode, wcml page, mc' => [
				[ 'page' => 'wpml-wcml', 'tab'  => 'multi-currency' ],
				false,
				true,
			],
			'standalone mode, wcml page, mc' => [
				[ 'page' => 'wpml-wcml', 'tab'  => 'multi-currency' ],
				true,
				true,
			],
			'standalone mode, wcml page, no tab' => [
				[ 'page' => 'wpml-wcml' ],
				true,
				true,
			],
			'standalone mode, wcml page, other tab' => [
				[ 'page' => 'wpml-wcml', 'tab' => 'other' ],
				true,
				false,
			],
		];
	}

	/**
	 * @test
	 * @dataProvider dpGetTabToDisplay
	 *
	 * @param bool   $hasCapabilities
	 * @param bool   $isStandalone
	 * @param string $expected
	 *
	 *
	 * @return void
	 */
	public function testGetTabToDisplay( $hasCapabilities, $isStandalone, $_get, $expected ) {
		$_GET = $_get;

		FunctionMocker::replace( WCML_Capabilities::class . '::canAccessAllWcmlTabs', $hasCapabilities );
		WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( $isStandalone );

		$this->assertEquals( $expected, AdminPages::getTabToDisplay() );
	}

	public function dpGetTabToDisplay() {
		return [
			'no capability and standalone mode' => [
				false,
				true,
				[ 'tab' => 'foo' ],
				AdminPages::TAB_MULTICURRENCY,
			],
			'has capability with standalone mode and no tab' => [
				true,
				true,
				[],
				AdminPages::TAB_MULTICURRENCY,
			],
			'no capability and full mode' => [
				false,
				false,
				[ 'tab' => 'foo' ],
				AdminPages::TAB_PRODUCTS,
			],
			'has capability with full mode and no tab' => [
				true,
				false,
				[],
				AdminPages::TAB_PRODUCTS,
			],
			'has capability and valid tab' => [
				true,
				false, // not relevant
				[ 'tab' => 'foo' ],
				'foo',
			],
		];
	}
}
