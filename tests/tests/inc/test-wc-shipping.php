<?php

class Test_WCML_WC_Shipping extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;
	private $settings_helper;
	private $orig_shipp_class_id;
	private $tr_shipp_class_id;

	function setUp(){
		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';

		$this->settings_helper = wpml_load_settings_helper();
		$this->settings_helper->set_taxonomy_translatable( 'product_shipping_class' );

		$this->orig_shipp_class_id = wpml_test_insert_term( $this->default_language, 'product_shipping_class', false, random_string() );
		$trid = $this->sitepress->get_element_trid( $this->orig_shipp_class_id[ 'term_id' ], 'tax_product_shipping_class' );
		$this->tr_shipp_class_id = wpml_test_insert_term( $this->second_language, 'product_shipping_class', $trid, random_string() );
	}

	function test_sync_flat_rate_class_cost() {

		$data = array(
			'woocommerce_flat_rate_class_cost_'.$this->orig_shipp_class_id[ 'term_id' ] => 10
		);

		$expected = array(
			'class_cost_'.$this->orig_shipp_class_id[ 'term_id' ] => 10,
			'class_cost_'.$this->tr_shipp_class_id[ 'term_id' ] => 10
		);

		//insert new settings
		$upd_settings = $this->woocommerce_wpml->shipping->sync_flat_rate_class_cost( $data, true );

		$this->assertEquals( $expected, $upd_settings );

		//update existing
		$db_data_emulation = array(
			'class_cost_'.$this->orig_shipp_class_id[ 'term_id' ] => 10
		);

		$upd_settings = $this->woocommerce_wpml->shipping->sync_flat_rate_class_cost( $data, $db_data_emulation );

		$this->assertEquals( $expected, $upd_settings );
	}

}