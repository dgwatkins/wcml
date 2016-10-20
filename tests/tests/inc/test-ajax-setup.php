<?php
/**
 * Class Test_WCML_Ajax_Setup
 */
class Test_WCML_Ajax_Setup extends WCML_UnitTestCase {

	private $WCML_Ajax_Setup;

	function setUp(){

		parent::setUp();

		$this->WCML_Ajax_Setup = new WCML_Ajax_Setup( $this->sitepress );

	}


	/**
	 * @test
	 */
	public function test_add_language_to_endpoint(){

		$endpoint_uri = '/product/dummy/?wc-ajax=%%endpoint%%' ;

		$language = 'fr';

		// Add lang parameter when different domains and non-default language
		icl_set_setting( 'language_negotiation_type', 2, true );
		$this->sitepress->switch_lang( $language );
		$this->assertEquals( $endpoint_uri . '&lang=' . $language, $this->WCML_Ajax_Setup->add_language_to_endpoint( $endpoint_uri ) );

		// Add lang parameter when different domains and non-default language
		icl_set_setting( 'language_negotiation_type', 2, true );
		$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
		$this->assertEquals( $endpoint_uri, $this->WCML_Ajax_Setup->add_language_to_endpoint( $endpoint_uri ) );

		// Don't add lang parameter when language_negotiation_type is 1 or 3
		//
		icl_set_setting( 'language_negotiation_type', 1, true );
		$this->sitepress->switch_lang( $language );
		$this->assertEquals( $endpoint_uri , $this->WCML_Ajax_Setup->add_language_to_endpoint( $endpoint_uri ) );
		//
		icl_set_setting( 'language_negotiation_type', 3, true );
		$this->sitepress->switch_lang( $language );
		$this->assertEquals( $endpoint_uri , $this->WCML_Ajax_Setup->add_language_to_endpoint( $endpoint_uri ) );


	}


}