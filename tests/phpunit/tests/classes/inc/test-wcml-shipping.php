<?php

class Test_WCML_Shipping extends OTGS_TestCase {

	private function get_subject( $sitepress ){

		return new WCML_WC_Shipping( $sitepress );

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
}
