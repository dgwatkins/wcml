<?php

/**
 * Class Test_WCML_Shipping
 */
class Test_WCML_Shipping extends OTGS_TestCase {

	private function get_subject( $sitepress = null ){

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress_mock();
		}

		return new WCML_WC_Shipping( $sitepress );
	}

	private function get_sitepress_mock() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( array(
			            'get_current_language',
			            'get_element_trid',
			            'get_element_translations'
		            ) )
		            ->getMock();

	}

	private function get_wp_term_mock() {
		return $this->getMockBuilder( 'WP_Term' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @test
	 */
	public function translate_shipping_method_title(){

		$title = rand_str();
		$trnsl_title = rand_str();
		$shipping_id = rand_str();
		$language = 'en';

		$sitepress = $this->getMockBuilder( 'SitePress' )
			->disableOriginalConstructor()
			->setMethods( array(
				'get_current_language'
			) )
			->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $language );

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );

		$subject = $this->get_subject( $sitepress );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
			->with( $title, 'woocommerce', $shipping_id .'_shipping_method_title', $language )
			->reply( $trnsl_title );

		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $trnsl_title, $filtered_title );

		$language = 'de';
		$trnsl_title = rand_str();

		WP_Mock::onFilter( 'wpml_translate_single_string' )
			->with( $title, 'woocommerce', $shipping_id .'_shipping_method_title', $language )
			->reply( $trnsl_title );

		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id, $language );
		$this->assertEquals( $trnsl_title, $filtered_title );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
			->with( $title, 'woocommerce', $shipping_id .'_shipping_method_title', $language )
			->reply( '' );

		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id, $language );
		$this->assertEquals( $title, $filtered_title );
	}

	/**
	 * @test
	 */
	public function translate_shipping_method_title_on_admin(){

		$title            = rand_str();
		$translated_title = rand_str();
		$shipping_id      = rand_str();
		$language         = 'en';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'get_current_language'
		                  ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $language );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $title, 'woocommerce', $shipping_id .'_shipping_method_title', $language )
		       ->reply( $translated_title );

		$subject = $this->get_subject( $sitepress );


		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		\WP_Mock::wpFunction( 'did_action', array(
			'args' => array( 'admin_init' ),
			'return' => true
		) );

		$screen = $this->getMockBuilder( 'WP_Screen' )->disableOriginalConstructor()->getMock();
		\WP_Mock::wpFunction( 'get_current_screen', array( 'return' => $screen ) );

		$screen->id = 'NOT_shop_order';
		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $title, $filtered_title );

		$screen->id = 'shop_order';
		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $translated_title, $filtered_title );

	}

	/**
	 * @test
	 * @group wcml-2061
	 */
	public function translate_shipping_method_title_on_admin_before_admin_init(){

		$title            = rand_str();
		$translated_title = rand_str();
		$shipping_id      = rand_str();
		$language         = 'en';

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'get_current_language'
		                  ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $language );

		WP_Mock::onFilter( 'wpml_translate_single_string' )
		       ->with( $title, 'woocommerce', $shipping_id .'_shipping_method_title', $language )
		       ->reply( $translated_title );

		$subject = $this->get_subject( $sitepress );


		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );
		\WP_Mock::wpFunction( 'did_action', array(
			'times' => 1,
			'args' => array( 'admin_init' ),
			'return' => false
		) );

		$filtered_title = $subject->translate_shipping_method_title( $title, $shipping_id );
		$this->assertEquals( $title, $filtered_title );

	}
}
