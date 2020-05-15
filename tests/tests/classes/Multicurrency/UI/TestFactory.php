<?php

namespace WCML\Multicurrency\UI;

/**
 * @group multicurrency
 * @group wcml-3178
 */
class TestFactory extends \WCML_UnitTestCase {

	private $originalSettings;

	public function setUp() {
		global $woocommerce_wpml;
		parent::setUp();
		$this->originalSettings = $woocommerce_wpml->settings;
	}

	public function tearDown() {
		global $woocommerce_wpml;
		$woocommerce_wpml->settings = $this->originalSettings;
		unset( $_GET );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itLoadsInBackendOnInit() {
		$subject = new Factory();
		$this->assertInstanceOf( \IWPML_Backend_Action_Loader::class, $subject );
		$this->assertEquals( 'init', $subject->get_load_action() );
	}

	/**
	 * @test
	 * @dataProvider dpShouldReturnNull
	 *
	 * @param array $_get
	 */
	public function itShouldReturnNull( $_get ) {
		$_GET = $_get;
		$subject = new Factory();
		$this->assertNull( $subject->create() );
	}

	public function dpShouldReturnNull() {
		return [
			'no param'          => [ [] ],
			'not WCML settings' => [ [ 'page' => 'something', 'tab' => 'multi-currency' ] ],
			'not MC tab'        => [ [ 'page' => 'wpml-wcml', 'tab' => 'something' ] ],
		];
	}

	/**
	 * @test
	 */
	public function itShouldCreateAndReturnHooks() {
		global $woocommerce_wpml;

		$woocommerce_wpml->settings['default_currencies'] = [ 'something default currencies' ];

		$_GET = [
			'page' => 'wpml-wcml',
			'tab'  => 'multi-currency',
		];

		$subject = new Factory();
		$this->assertInstanceOf( Hooks::class, $subject->create() );
	}
}
