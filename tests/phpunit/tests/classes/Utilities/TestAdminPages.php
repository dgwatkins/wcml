<?php

namespace WCML\Utilities;

/**
 * @group wcml-3848
 */
class TestAdminPages extends \OTGS_TestCase {

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
}
