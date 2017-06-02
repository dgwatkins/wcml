<?php

class Test_WCML_Synchronize_Product_Data extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods(array( 'get_current_language' ))
			->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();

		$this->wpdb = $this->stubs->wpdb();
	}


	private function get_subject(){
		return new WCML_Synchronize_Product_Data( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function get_translated_custom_field_values(){

		$subject = $this->get_subject( );

		$custom_field = rand_str();
		$custom_field_index = 0;
		$translated_title = rand_str();
		$translated_id = rand_str();
		$translated_content = rand_str();

		$custom_field_value = array(
			'title'   => rand_str(),
			'id'	  => rand_str(),
			'content' => rand_str()
		);

		$custom_fields_values = array();
		$custom_fields_values[ $custom_field_index ] = $custom_field_value;

		$translation_data = array();
		$translation_data[ md5( 'field-'.$custom_field.'-0-title' ) ] = $translated_title;
		$translation_data[ md5( 'field-'.$custom_field.'-0-id' ) ] = $translated_id;
		$translation_data[ md5( 'field-'.$custom_field.'-0-content' ) ] = $translated_content;

		$translated_values = $subject->get_translated_custom_field_values( $custom_fields_values, $translation_data, $custom_field, $custom_field_value, $custom_field_index );

		$this->assertEquals( $translated_title, $translated_values[ $custom_field_index ][ 'title' ] );
		$this->assertEquals( $translated_id, $translated_values[ $custom_field_index ][ 'id' ] );
		$this->assertEquals( $translated_content, $translated_values[ $custom_field_index ][ 'content' ] );

	}

}
