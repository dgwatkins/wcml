<?php

class Test_WCML_YIKES_Custom_Product_Tabs_Pro extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Element_Translation_Package */
	private $tp;


	public function setUp() {

		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->getMock();


		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();


		$this->tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'encode_field_data' ) )
		                 ->getMock();

	}

	/**
	 * @return WCML_YIKES_Custom_Product_Tabs_Pro
	 */
	private function get_subject() {
		return new WCML_YIKES_Custom_Product_Tabs_Pro( $this->woocommerce_wpml, $this->sitepress, $this->tp );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => true ) );

		\WP_Mock::expectActionAdded( 'wcml_gui_additional_box_html', array( $subject, 'custom_box_html' ), 10, 3 );
		\WP_Mock::expectFilterAdded( 'wcml_gui_additional_box_data', array( $subject, 'custom_box_html_data' ), 10, 4 );
		\WP_Mock::expectActionAdded( 'wcml_update_extra_fields', array( $subject, 'sync_tabs' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'wpml_duplicate_custom_fields_exceptions', array(
			$subject,
			'custom_fields_exceptions'
		) );
		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', array(
			$subject,
			'custom_fields_exceptions'
		) );

		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', array(
			$subject,
			'append_custom_tabs_to_translation_package'
		), 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_translation_job_saved', array(
			$subject,
			'save_custom_tabs_translation'
		), 10, 3 );

		\WP_Mock::expectActionAdded( 'woocommerce_product_data_panels', array( $subject, 'show_pointer_info' ) );
		\WP_Mock::expectActionAdded( 'init', array( $subject, 'maybe_remove_admin_language_switcher' ) );
		$subject->add_hooks();

	}

	/**
	 * @test
	 * @dataProvider tabs_to_translate_data
	 */
	public function it_should_add_tabs_data_to_additional_box( $original_tabs, $translated_tabs, $expected_data ) {

		$data = array();
		$product_id = 1;
		$translation = new stdClass();
		$translation->ID = 2;
		$lang = 'es';

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $product_id, 'yikes_woo_products_tabs', true ),
			'return' => $original_tabs
		));

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $translation->ID, 'yikes_woo_products_tabs', true ),
			'return' => $translated_tabs
		));

		$subject = $this->get_subject();

		$filtered_data = $subject->custom_box_html_data( $data, $product_id, $translation, $lang );

		$this->assertEquals( $expected_data, $filtered_data );
	}

	/**
	 * @return array
	 */
	public function tabs_to_translate_data() {

		return array(
			array(
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
					array( 'title' => 'title_2', 'content' => 'content_2' ),
				),
				array(
					array( 'title' => 'tr_title_1', 'content' => 'tr_content_1' ),
					array( 'title' => 'tr_title_2', 'content' => 'tr_content_2' ),

				),
				array(
					'tab_0_title'   => array( 'original' => 'title_1', 'translation' => 'tr_title_1' ),
					'tab_0_content' => array( 'original' => 'content_1', 'translation' => 'tr_content_1' ),
					'tab_1_title'   => array( 'original' => 'title_2', 'translation' => 'tr_title_2' ),
					'tab_1_content' => array( 'original' => 'content_2', 'translation' => 'tr_content_2' ),
				)
			),
			array(
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
					array( 'title' => 'title_2', 'content' => 'content_2' ),
				),
				array(
					array( 'title' => '', 'content' => '' ),
					array( 'title' => '', 'content' => '' ),
				),
				array(
					'tab_0_title'   => array( 'original' => 'title_1', 'translation' => '' ),
					'tab_0_content' => array( 'original' => 'content_1', 'translation' => '' ),
					'tab_1_title'   => array( 'original' => 'title_2', 'translation' => '' ),
					'tab_1_content' => array( 'original' => 'content_2', 'translation' => '' ),
				)
			),
			array(
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
				),
				array(),
				array(
					'tab_0_title'   => array( 'original' => 'title_1' ),
					'tab_0_content' => array( 'original' => 'content_1' )
				)
			),
			array(
				array(),
				array(),
				array()
			),
		);

	}

	/**
	 * @test
	 * @dataProvider data_to_sync
	 */
	public function it_should_sync_tabs( $icl_ajx_action, $is_icl_lang_duplicate_of, $original_tabs, $data, $expected_tabs ) {

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );
		\WP_Mock::wpPassthruFunction( 'wp_kses_post' );
		$product_id = 1;
		$translated_product_id = 2;
		$lang = 'es';

		$_POST['icl_ajx_action'] = $icl_ajx_action;

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, '_icl_lang_duplicate_of', true ),
			'return' => $is_icl_lang_duplicate_of
		));

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $product_id, 'yikes_woo_products_tabs', true ),
			'return' => $original_tabs
		));

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $translated_product_id, 'yikes_woo_products_tabs', $expected_tabs ),
			'return' => true
		));

		$subject = $this->get_subject();

		$filtered_data = $subject->sync_tabs( $product_id, $translated_product_id, $data, $lang );
	}

	/**
	 * @return array
	 */
	public function data_to_sync(){

		return array(
			array(
				'make_duplicates',
				false,
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
				),
				array(),
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
				),
				),
			array(
				'',
				true,
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
				),
				array(),
				array(
					array( 'title' => 'title_1', 'content' => 'content_1' ),
				),
			),
			array(
				'',
				false,
				array(
					array( 'id' => 'tab_1', 'title' => 'title_1', 'content' => 'content_1' ),
				),
				array(
					md5( 'tab_0_title' ) => 'tr_title_1',
					md5( 'tab_0_content' ) => 'tr_content_1',
				),
				array(
					array( 'id' => 'tab_1', 'title' => 'tr_title_1', 'content' => 'tr_content_1' ),
				),
			),
		);
	}

	/**
	 * @test
	 */
	public function it_should_add_tabs_filed_to_custom_fields_exceptions(){

		$expected_fields = array( 'yikes_woo_products_tabs' );

		$subject = $this->get_subject();

		$filtered_fields = $subject->custom_fields_exceptions( array() );

		$this->assertEquals( $expected_fields, $filtered_fields );
	}

	/**
	 * @test
	 */
	public function it_should_append_custom_tabs_to_translation_package(){

		$post = new stdClass();
		$post->ID = 1;
		$post->post_type = 'product';

		$original_tabs = array(
			array( 'title' => 'title_1', 'content' => 'content_1' )
		);

		$package = array();

		$base = 'base64';
		$expected_package = array();
		$expected_package['contents'][ 'yikes_woo_products_tabs:product_tab:0:title' ] = array(
			'translate' => 1,
			'data'      => 'title_1',
			'format'    => $base
		);
		$expected_package['contents'][ 'yikes_woo_products_tabs:product_tab:0:content' ] = array(
			'translate' => 1,
			'data'      => 'content_1',
			'format'    => $base
		);

		$this->tp->method( 'encode_field_data' )->willReturnCallback( function ( $value, $base ) {
			return $value;
		});

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $post->ID, 'yikes_woo_products_tabs', true ),
			'return' => $original_tabs
		));

		$subject = $this->get_subject();

		$filtered_package = $subject->append_custom_tabs_to_translation_package( $package, $post );

		$this->assertEquals( $expected_package, $filtered_package );
	}

	/**
	 * @test
	 */
	public function it_should_save_custom_tabs_translation(){

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );
		\WP_Mock::wpPassthruFunction( 'wp_kses_post' );

		$job = new stdClass();
		$job->original_doc_id = 1;
		$post_id = 2;

		$original_tabs = array(
			array( 'id' => '0_title', 'title' => 'title_1', 'content' => 'content_1' )
		);

		$data = array();
		$data[] = array(
			'field_type' => 'yikes_woo_products_tabs:product_tab:0:title',
			'data'       => 'tr_title_1',
		);
		$data[] = array(
			'field_type' => 'yikes_woo_products_tabs:product_tab:0:content',
			'data'       => 'tr_content_1',
		);

		$expected_tabs = array(
			array( 'id' => '0_title', 'title' => 'tr_title_1', 'content' => 'tr_content_1' )
		);

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $job->original_doc_id, 'yikes_woo_products_tabs', true ),
			'return' => $original_tabs
		));

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args' => array( $post_id, 'yikes_woo_products_tabs', true ),
			'return' => array()
		));

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args' => array( $post_id, 'yikes_woo_products_tabs', true ),
			'return' => $expected_tabs
		));

		$subject = $this->get_subject();

		$filtered_package = $subject->save_custom_tabs_translation( $post_id, $data, $job );
	}

	/**
	 * @test
	 */
	public function maybe_remove_admin_language_switcher(){
		$_GET['page'] = 'yikes-woo-settings';

		\WP_Mock::wpFunction( 'remove_action', array(
			'times' => 1,
			'args'  => array( 'wp_before_admin_bar_render', array( $this->sitepress, 'admin_language_switcher' ) )
		) );

		$subject = $this->get_subject();
		$subject->maybe_remove_admin_language_switcher();
	}

}
