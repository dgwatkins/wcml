<?php

class Test_WCML_Emails extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
	}

	function test_icl_job_edit_url() {
		$link_missing_job = 'http://' . rand_str( 10 );

		$this->assertEquals( $link_missing_job, $this->woocommerce_wpml->emails->icl_job_edit_url( $link_missing_job, 0 ) );
	}

	function test_set_locale_for_emails() {
		$locale_dummy = rand_str( 5 );
		$domain_dummy = rand_str( 20 );
		$this->assertEquals( $locale_dummy, $this->woocommerce_wpml->emails->set_locale_for_emails( $locale_dummy, $domain_dummy ) );

		$this->woocommerce_wpml->emails->change_email_language( 'fr' );
		$this->assertEquals( 'fr_FR', $this->woocommerce_wpml->emails->set_locale_for_emails( $locale_dummy, 'woocommerce' ) );
	}

}