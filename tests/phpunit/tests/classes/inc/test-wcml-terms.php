<?php

class Test_WCML_Terms extends OTGS_TestCase {

	function setUp(){
		parent::setUp();
	}

	/**
	 * @return woocommerce_wpml
	 */
	public function get_woocommerce_wpml(){

		return $this->getMockBuilder('woocommerce_wpml')
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return SitePress
	 */
	public function get_sitepress() {

		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return WCML_Terms
	 */
	private function get_subject( $woocommerce_wpml = null, $sitepress = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Terms( $woocommerce_wpml, $sitepress , $this->stubs->wpdb() );
	}

	/**
	 * @test
	 */
	function is_translatable_wc_taxonomy(){

		$subject = $this->get_subject();
		$taxonomy = rand_str();

		$this->assertTrue( $subject->is_translatable_wc_taxonomy( $taxonomy ) );

		$taxonomy = 'product_type';
		$this->assertFalse( $subject->is_translatable_wc_taxonomy( $taxonomy ) );

	}

}