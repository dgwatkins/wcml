<?php

/**
 * Class Test_WCML_Store_Pages
 */
class Test_WCML_Store_Pages extends OTGS_TestCase {

	public function setUp(){
		parent::setUp();

	}

	private function get_sitepress(){
		return $this->getMockBuilder('SitePress')
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder('woocommerce_wpml')
		            ->disableOriginalConstructor()
		            ->getMock();
	}


	private function get_subject( $woocommerce_wpml = null, $sitepress = null ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Store_Pages( $woocommerce_wpml, $sitepress );
	}

	/**
	 * @test
	 */
	public function filter_shop_archive_link(){

		$link = rand_str();
		$expected_link = rand_str();
		$post_type = 'product';

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_default_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( rand_str( 5 ) );
		$sitepress->method( 'get_default_language' )->willReturn( rand_str( 6 ) );

		\WP_Mock::wpFunction( 'home_url', array( 'return' => $expected_link ) );

		$subject = $this->get_subject( null, $sitepress );
		$shop_page_id = mt_rand( 1, 10 );
		$subject->front_page_id = $shop_page_id;
		$subject->shop_page_id = $shop_page_id;

		$this->assertSame( $expected_link, $subject->filter_shop_archive_link( $link, $post_type ) );
	}

}
