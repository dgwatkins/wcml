<?php

namespace WCML\StandAlone;

use OTGS_TestCase;
use WCML\StandAlone\ActionFilterLoader;
use WPML_Action_Filter_Loader;

class TestActionFilterLoader extends OTGS_TestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function testLoadAllLoaders() {
		define( 'ICL_SITEPRESS_VERSION', '4.5.1' );

		$loaded = [];
		$subject = $this->getSubject( $loaded );
		$subject->load(
			[
				GeneralMockActionForTests::class,
				StandAloneMockActionForTests::class,
			]
		);
		$this->assertSame( [
			GeneralMockActionForTests::class,
			StandAloneMockActionForTests::class,
		], $loaded );
	}

	public function testLoadOnlyStandAloneLoaders() {
		$loaded = [];
		$subject = $this->getSubject( $loaded );
		$subject->load(
			[
				GeneralMockActionForTests::class,
				StandAloneMockActionForTests::class,
			]
		);
		$this->assertSame( [ StandAloneMockActionForTests::class ], $loaded );
	}

	/** @return ActionFilterLoader */
	private function getSubject( array &$loaded ) {
		$mockLoader = $this->getMockBuilder( WPML_Action_Filter_Loader::class )
			->disableOriginalConstructor()
			->setMethods( [ 'load' ] )
			->getMock();

		$mockLoader->expects( $this->any() )->method( 'load' )
			->willReturnCallback( function( $loaders ) use ( &$loaded ) {
				$loaded = $loaders;
			} );

		/** @var WPML_Action_Filter_Loader $mockLoader */
		return new ActionFilterLoader( $mockLoader );
	}
}

class GeneralMockActionForTests implements \IWPML_Action {
	public function add_hooks() {}
}

class StandAloneMockActionForTests implements \IWPML_Action, IStandAloneAction {
	public function add_hooks() {}
}