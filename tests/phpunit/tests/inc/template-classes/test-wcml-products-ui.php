<?php

class Test_WCML_Products_UI extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;

	private $default_language = 'en';

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_active_languages',
		                        ) )
		                        ->getMock();

		$this->sitepress->method( 'get_active_languages' )
		                ->wilLReturn(
		                	array( $this->default_language => 1, 'fr' => 1, 'de' => 1 )
		                );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

	}

	public function tearDown() {
		unset( $this->sitepress, $this->woocommerce );
		parent::tearDown();
	}

	/**
	 * @return WCML_Products_UI
	 */
	private function get_subject(){
		return new WCML_Products_UI( $this->woocommerce_wpml, $this->sitepress );
	}

	/**
	 * @test
	 */
	public function test_get_products_from_filter(){
		$subject = $this->get_subject();

		// false without filters
		$this->assertFalse( $subject->get_products_from_filter() );

		$_GET['cat'] = 0;
		$_GET['trst'] = 'not';
		$_GET['st'] = 'all';
		$_GET['slang'] = 'all';

		global $wpdb;
		$wpdb = $this->stubs->wpdb();

		$result = [ rand_str() ];
		$wpdb->expects( $this->once() )
		     ->method( 'get_results' )
		     ->willReturn( $result );

		$found = rand(1, 100);
		$wpdb->expects( $this->once() )
		     ->method( 'get_var' )
		     ->willReturn( $found );

		$products = $subject->get_products_from_filter();

		$this->assertEquals( $result, $products['products'] );
		$this->assertEquals( $found, $products['products_count'] );

	}


}

if ( ! class_exists( 'WCML_Templates_Factory' ) ) {
	/**
	 * Class WCML_Templates_Factory
	 * Stub for Test_WCML_Products_UI
	 */
	abstract class WCML_Templates_Factory {

		public function __construct() { /*silence is golden*/
		}

		public function show() { /*silence is golden*/
		}

	}
}
