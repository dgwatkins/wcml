<?php

use function WCML\functions\getId;

/**
 * @group wcml-functions
 */
class TestWcmlFunctions extends OTGS_TestCase {
	/**
	 * @test
	 * @dataProvider getObjects
	 * @param mixed $object
	 * @param mixed $expectedId
	 */
	public function itGetsId( $object, $expectedId ) {
		$this->assertEquals( $expectedId, getId( $object ) );
	}

	/**
	 * @test
	 * @dataProvider getObjects
	 * @param mixed $object
	 * @param mixed $expectedId
	 */
	public function itGetsIdWithNullaryArity( $object, $expectedId ) {
		$getId = getId();
		$this->assertEquals( $expectedId, $getId( $object ) );
	}

	/**
	 * @return [ $object, $expectedId ]
	 */
	public function getObjects() {
		$makeMock = function ( $class, $id ) {
			$mock = $this->getMockBuilder( $class )->disableOriginalConstructor()->setMethods( [ 'get_id' ] )->getMock();
			$mock->expects( $this->exactly( 1 ) )->method( 'get_id' )->willReturn( $id );
			return $mock;
		};

		return [
			[ null, null ],
			[ [ 'ID' => 4 ], 4 ],
			[ (object) [ 'ID' => 4 ], 4 ],
			[ $makeMock( WP_Post::class, 9 ), 9 ],
			[ $makeMock( WC_Data::class, 6 ), 6 ],
			[ $makeMock( WC_Order::class, 5 ), 5 ],
		];
	}
}
