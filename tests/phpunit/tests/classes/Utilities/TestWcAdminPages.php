<?php

namespace WCML\Utilities;

/**
 * @group wcml-3890
 */
class TestWcAdminPages extends \OTGS_TestCase {

	public function tearDown() {
		$_GET = [];
		unset( $GLOBALS['pagenow'] );
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider dpIsSection
	 *
	 * @param array        $_get
	 * @param string|array $sections
	 * @param bool         $expected
	 *
	 * @return void
	 */
	public function testIsTab( $_get, $sections, $expected ) {
		$_GET = $_get;
		$this->assertSame( $expected, WcAdminPages::isSection( $sections ) );
	}

	public function dpIsSection() {
		return [
			[ [ 'section' => 'foo' ], 'foo', true ],
			[ [ 'section' => 'foo' ], [ 'foo', 'bar' ], true ],
			[ [], 'section', false ],
			[ [ 'section' => 'other' ], 'foo', false ],
			[ [ 'section' => 'other' ], [ 'foo', 'bar' ], false ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpIsPaymentSettings
	 *
	 * @param bool   $isAdmin
	 * @param string $pagenow
	 * @param array  $_get
	 * @param bool   $expected
	 *
	 * @return void
	 */
	public function testIsPaymentSettings( $isAdmin, $pagenow, $_get, $expected ) {
		$GLOBALS['pagenow'] = $pagenow;
		$_GET               = $_get;

		\WP_Mock::userFunction( 'is_admin' )->andReturn( $isAdmin );

		$this->assertSame( $expected, WcAdminPages::isPaymentSettings() );
	}

	public function dpIsPaymentSettings() {
		return [
			'not in backend'      => [ false, 'admin.php', [ 'wc-settings' => 'checkout' ], false ],
			'not admin.php'       => [ true, 'something.php', [ 'wc-settings' => 'checkout' ], false ],
			'no page'             => [ true, 'admin.php', [], false ],
			'not settings page'   => [ true, 'admin.php', [ 'page' => 'something' ], false ],
			'not payment tab'     => [ true, 'admin.php', [ 'page' => 'wc-settings', 'tab' => 'something' ], false ],
			'correct payment tab' => [ true, 'admin.php', [ 'page' => 'wc-settings', 'tab' => 'checkout' ], true ],
		];
	}
}
