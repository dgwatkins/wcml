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

	/**
	 * @test
	 */
	public function it_does_not_adjust_shop_page_if_WC_version_3_3_and_above(){

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api', 'get_default_language' ) )
		                  ->getMock();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant', 'version_compare' ) )
		               ->getMock();

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$subject = $this->get_subject( null, $sitepress );

		$wc_version = '3.3.3';
		$check_version = '3.3';

		$wp_api->expects( $this->once() )
		             ->method( 'constant' )
		             ->with( 'WC_VERSION' )
		             ->willReturn( $wc_version );
		$wp_api->expects( $this->once() )
		             ->method( 'version_compare' )
		             ->with( $wc_version, $check_version, '<' )
		             ->willReturn( false );

		$subject->adjust_shop_page( array() );

		$sitepress->expects( $this->never() )->method( 'get_default_language' );
	}


	/**
	 * @test
	 */
	public function it_adjusts_the_shop_page_if_WC_version_prior_to_3_3(){

		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api', 'get_default_language', 'get_current_language' ) )
		                  ->getMock();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant', 'version_compare' ) )
		               ->getMock();

		$default_language = rand_str( 2 );
		$current_language = rand_str( 2 );

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );
		$sitepress->method( 'get_default_language' )->willReturn( $default_language );
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$subject = $this->get_subject( null, $sitepress );

		$wc_version = '3.2';
		$check_version = '3.3';

		$wp_api->expects( $this->once() )
		             ->method( 'constant' )
		             ->with( 'WC_VERSION' )
		             ->willReturn( $wc_version );
		$wp_api->expects( $this->once() )
		             ->method( 'version_compare' )
		             ->with( $wc_version, $check_version, '<' )
		             ->willReturn( true );
		$q = new stdClass();
		$q->query_vars['pagename'] = '/shop';

		$shop_id = mt_rand( 1, 10 );
		WP_Mock::userFunction( 'wc_get_page_id', array(
			'args' => array( 'shop' ),
			'return' => $shop_id
		));

		$shop_page = new stdClass();
		$shop_page->post_name = 'shop';
		WP_Mock::userFunction( 'get_post', array(
			'args' => array( $shop_id ),
			'return' => $shop_page
		));

		$subject->adjust_shop_page( $q );

		$expected_query_vars['post_type'] = 'product';
		$this->assertEquals( $expected_query_vars, $q->query_vars );
	}

}
