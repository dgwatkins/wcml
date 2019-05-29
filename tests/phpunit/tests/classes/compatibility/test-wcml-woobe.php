<?php

class Test_WCML_Woobe extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	const FIELD_KEY = 'regular_price';

	private $post_translations;

	private $wp_api;

	public function setUp()
	{
		parent::setUp();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress_mock( $wp_api = null ) {

		$WPML_COPY_CUSTOM_FIELD = 1;

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_settings', 'get_wp_api' ) )
		                  ->getMock();

		if( null === $wp_api ){
			$this->wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->wp_api->method( 'constant' )->with( 'WPML_COPY_CUSTOM_FIELD' )->willReturn( $WPML_COPY_CUSTOM_FIELD );

		$settings['translation-management']['custom_fields_translation']['_' . self::FIELD_KEY] = $WPML_COPY_CUSTOM_FIELD;
		$sitepress->method( 'get_settings' )->willReturn( $settings );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'constant', 'version_compare' ) )
		            ->getMock();
	}

	private function get_post_translations_mock() {
		$this->post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_element_translations' ) )
			->getMock();

		return $this->post_translations;
	}

	private function get_subject( $sitepress = null, $post_translations = null  ){

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress_mock();
		}
		if ( null === $post_translations ) {
			$this->post_translations = $this->get_post_translations_mock();
		}

		return new WCML_Woobe( $sitepress, $this->post_translations );
	}

	/**
	 * @test
	 */
	public function add_hooks(){
		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'woobe_after_update_page_field', array( $subject, 'replace_price_in_translations' ), 10, 5 );
		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function it_should_replace_price_when_data_are_correct() {
		$price = 80.00;
		$product_id = 1;
		$translations = array(
			'pl' => 2
		);

		$subject = $this->get_subject();

		$this->post_translations->method( 'get_element_translations' )->willReturn( $translations );

		\WP_Mock::passthruFunction( 'update_post_meta', array(
			'times' => 1,
			'args'  => array( $translations['pl'], '_regular_price', $price ),
		));

		\WP_Mock::passthruFunction( 'update_post_meta', array(
			'times' => 1,
			'args'  => array( $translations['pl'], '_price', $price ),
		));


		$subject->replace_price_in_translations( $product_id, null, self::FIELD_KEY, $price, null );

	}

	/**
	 * @test
	 */
	public function it_should_not_run_logic_when_no_product_translations() {
		$price = 80.00;
		$product_id = 1;

		$subject = $this->get_subject();

		$translations = array(
		);
		$this->post_translations->method( 'get_element_translations' )->willReturn( $translations );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'times' => 0
		) );

		$subject->replace_price_in_translations( $product_id, null, self::FIELD_KEY, $price, null );
	}

	/**
	 * @test
	 */
	public function it_should_not_run_logic_when_product_id_invalid() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'update_post_meta', array(
			'times' => 0
		) );
		$subject->replace_price_in_translations( 'foo', null, self::FIELD_KEY, null, null );
	}

	/**
	 * @test
	 */
	public function it_should_not_run_logic_when_price_invalid() {
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'update_post_meta', array(
			'times' => 0
		) );
		$subject->replace_price_in_translations( 1, null, self::FIELD_KEY, 'foo', null );
	}

	/**
	 * @test
	 */
	public function it_should_not_run_logic_when_price_is_not_set_to_copy() {
		$this->get_sitepress_mock()->method( 'get_settings' )->willReturn( null );
		$subject = $this->get_subject();
		\WP_Mock::userFunction( 'update_post_meta', array(
			'times' => 0
		) );
		$subject->replace_price_in_translations( 1, null, self::FIELD_KEY, 10, null );
	}


}