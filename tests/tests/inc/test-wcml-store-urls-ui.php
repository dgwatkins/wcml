<?php

class Test_WCMLStore_Urls_UI extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();

		set_current_screen( 'admin' );

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
	}



}