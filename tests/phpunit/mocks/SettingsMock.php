<?php

namespace WCML\PHPUnit;

use tad\FunctionMocker\FunctionMocker;
use WPML\FP\Obj;

trait SettingsMock {

	private $wcmlSettings = [];

	public function setUpSettings() {
		FunctionMocker::setUp();

		\WP_Mock::userFunction( 'WCML\functions\getSetting', [
			'return' => function( $key, $default = null ) {
				return Obj::propOr( $default, $key, $this->wcmlSettings );
			}
		] );

		\WP_Mock::userFunction( 'WCML\functions\updateSetting', [
			'return' => function( $key, $value, $autoload = false ) {
				$this->wcmlSettings[ $key ] = $value;
			}
		] );
	}
}
