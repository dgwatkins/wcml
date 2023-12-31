<?php

class Test_WCML_Page_Builders extends OTGS_TestCase
{

	private $sitepress;

	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api' ) )
			->getMock();

	}

	/**
	 * @return WCML_Page_Builders
	 */
	private function get_subject()
	{
		return new WCML_Page_Builders( $this->sitepress );
	}

	/**
	 * @test
	 */
	public function page_builders()
	{
		$product_id = 1;
		$package_id = 10;

		$package = $this->getMockBuilder('WPML_Package')
			->disableOriginalConstructor()
			->setMethods(array('get_package_strings', 'get_translated_strings'))
			->getMock();

		$package->title = rand_str();
		$string_packages = array(
			$package_id => $package
		);

		\WP_Mock::onFilter('wpml_st_get_post_string_packages')->with(false, $product_id )->reply($string_packages);

		$string_id = 20;
		$target_language = rand_str();
		$string_value = rand_str();
		$string_name = rand_str();
		$translated_string_value = rand_str();

		$package_string = new stdClass();
		$package_string->id = $string_id;
		$package_string->name = $string_name;
		$package_string->value = $string_value;
		$package_string->translated_value = rand_str();

		$translated_strings = array(
			$string_name => array(
				$target_language => array(
					'value' => $translated_string_value
				),
			),
		);

		$package->method('get_package_strings')->willReturn(array($package_string));
		$package->method('get_translated_strings')->willReturn($translated_strings);

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$icl_tm_complete = 30;
		$wp_api->method( 'constant' )->with( 'ICL_TM_COMPLETE' )->willReturn( $icl_tm_complete );

		$subject = $this->get_subject();

		$builders_strings = $subject->get_page_builders_strings( $product_id, $target_language );

		$this->assertEquals(
			$translated_strings[ $string_name ][ $target_language ][ 'value' ],
			$builders_strings[ $package_id ][ 'strings' ][ 0 ]->translated_value
		);

		$second_product_id = 2;
		\WP_Mock::onFilter('wpml_st_get_post_string_packages')->with(false, $second_product_id )->reply(false);
		$builders_strings = $subject->get_page_builders_strings( $second_product_id, $target_language );
		$this->assertEquals( array(), $builders_strings );

		$builders_data = $subject->page_builders_data( array(), $product_id, $target_language );

		$this->assertEquals( $string_value, $builders_data[ $string_name ][ 'original' ] );
		$this->assertEquals( $translated_string_value, $builders_data[ $string_name ][ 'translation' ] );

		\WP_Mock::expectAction(
			'wpml_add_string_translation',
			$string_id,
			$target_language,
			$translated_string_value,
			$icl_tm_complete
		);

		$builders_data = $subject->save_page_builders_strings( array( md5( $string_name ) => $translated_string_value ), $product_id, $target_language );
	}

	/**
	 * @test
	 */
	function it_should_not_filter_element_data_without_strings(){
		$product_id = 2;
		$target_language = 'es';
		$string_packages = array();
		$element_data = array();

		\WP_Mock::onFilter('wpml_st_get_post_string_packages')->with( false, $product_id )->reply( $string_packages );

		$subject = $this->get_subject();
		$this->assertEmpty( $subject->page_builders_data( array(), $product_id, $target_language ) );
	}

}