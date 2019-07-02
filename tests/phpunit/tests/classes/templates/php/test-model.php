<?php

/**
 * @author OnTheGo Systems
 * @group  templates
 */
class Test_Model extends OTGS_TestCase {
	/**
	 * @test
	 */
	public function it_builds_a_model_of_primitives_attributes() {
		$data = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 'a',
			'e' => 'b',
			'f' => 'c',
			'g' => true,
			'h' => false,
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		foreach ( $data as $property => $value ) {
			$this->assertSame( $value, $model->$property );
		}
	}

	/**
	 * @test
	 */
	public function it_access_non_defined_attributes() {
		$data = [
			'a' => 1,
			'b' => 'a',
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		/**
		 * When a non defined property is used in templates with `echo`, it returns an empty string.
		 */
		$this->assertEmpty( (string) $model->c );
		$this->assertEmpty( (string) $model->c->c1 );

		/**
		 * When a non defined property is used as a boolean, it returns false
		 */
		$this->assertFalse( true === $model->c );
		$this->assertFalse( true === $model->c->c1 );
	}

	/**
	 * @test
	 */
	public function it_tells_if_an_attributes_has_a_value() {
		$data = [
			'a' => 1,
			'b' => 'a',
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertTrue( $model->hasValue( 'a' ) );
		$this->assertTrue( $model->hasValue( 'b' ) );
		$this->assertFalse( $model->hasValue( 'c' ) );
		$this->assertFalse( $model->c->hasValue( 'c1' ) );
	}

	/**
	 * @test
	 */
	public function it_tells_if_an_attributes_value_is_null() {
		$data = [
			'a' => 1,
			'b' => 'a',
			'c' => null,
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertFalse( $model->isNull( 'a' ) );
		$this->assertFalse( $model->isNull( 'b' ) );
		$this->assertTrue( $model->isNull( 'c' ) );
	}

	/**
	 * @test
	 */
	public function it_tells_if_an_attributes_value_is_empty() {
		$data = [
			'a' => 1,
			'b' => 'a',
			'c' => null,
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertFalse( $model->isEmpty( 'a' ) );
		$this->assertFalse( $model->isEmpty( 'b' ) );
		$this->assertFalse( $model->isEmpty( 'c' ) );
		$this->assertTrue( $model->d->isEmpty( 'd1' ) );
	}

	/**
	 * @test
	 */
	public function it_gets_the_attributes_of_a_model() {
		$data = [
			'a' => 1,
			'b' => 'a',
			'c' => null,
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertSame( $data, $model->getAttributes() );
	}

	/**
	 * @test
	 */
	public function it_casts_an_empty_model_to_an_empty_string() {
		$data = [];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertEmpty( (string) $model );
	}

	/**
	 * @test
	 */
	public function it_casts_a_single_attribute_model_to_a_string_representation_of_that_attribute() {
		$data = [
			'a' => 'a single value',
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertSame( $data['a'], (string) $model );
	}

	/**
	 * @test
	 */
	public function it_casts_a_multiple_attributes_model_to_a_JSON_representation_of_the_attributes() {
		$data = [
			'a' => 'a value',
			'b' => 'another value',
		];

		WP_Mock::userFunction( 'wp_json_encode', [
			'return' => function ( $data ) {
				return json_encode( $data );
			}
		] );

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertSame( json_encode( $data ), (string) $model );
	}

	/**
	 * @test
	 */
	public function it_builds_a_model_of_complex_attributes() {
		$testObject      = new stdClass();
		$testObject->foo = 'bar';
		$data            = [
			'a' => [ 1, 2, 3 ],
			'b' => $testObject,
			'c' => 'just a string'
		];

		$model = new \WPML\Templates\PHP\Model( $data );

		$this->assertSame( $data['a'], $model->a );
		$this->assertSame( $data['b']->foo, $model->b->foo );
		$this->assertSame( $data['c'], $model->c );
	}
}
