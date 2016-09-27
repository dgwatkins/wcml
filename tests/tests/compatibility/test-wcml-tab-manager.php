<?php

/**
 * Class Test_WCML_Tab_Manager
 */
class Test_WCML_Tab_Manager extends WCML_UnitTestCase {

	public $sitepress;

	public $wpdb;

	public $woocommerce_wpml;

	public $woocommerce;

	public $default_language;

	public $second_language;

	public $tp;

	function setUp() {
		global $woocommerce, $sitepress, $woocommerce_wpml, $wpdb;
		parent::setUp();
		$this->sitepress = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->woocommerce = $woocommerce;
		$this->wpdb = $wpdb;
		$this->default_language = $this->sitepress->get_default_language();
		$active_languages = $this->sitepress->get_active_languages();
		unset( $active_languages[ $this->default_language ] );
		$this->second_language = array_rand( $active_languages );

		wpml_test_reg_custom_post_type( 'wc_product_tab', true );
		$this->tp = new WPML_Element_Translation_Package;
	}

	/**
	 * @test
	 */
	public function make__product_tabs_not_translatable_by_default() {
		$tab_manager = $this->get_test_subject();
		$wpml_config_array = new stdClass();
		$wpml_config_array->plugins = array( 'Test plugin' );
		$this->assertEquals( (array) $wpml_config_array, (array) $tab_manager->make__product_tabs_not_translatable_by_default( $wpml_config_array ) );
		$wpml_config_array->plugins['WooCommerce Tab Manager'] = '<custom-field action="translate">_product_tabs</custom-field>';
		$output = $tab_manager->make__product_tabs_not_translatable_by_default( $wpml_config_array );
		$this->assertFalse( strpos( $wpml_config_array->plugins['WooCommerce Tab Manager'], 'action="translate"' ) );
		$this->assertTrue( false !== strpos( $wpml_config_array->plugins['WooCommerce Tab Manager'], 'action="nothing"' ) );
	}

	/**
	 * @test
	 */
	public function sync_tabs() {
		$product_title      = random_string();
		$product            = wpml_test_insert_post( $this->default_language, 'product', false, $product_title );
		$trid               = $this->sitepress->get_element_trid( $product, 'post_product' );
		$translated_product_title = random_string();
		$translated_product = wpml_test_insert_post( $this->second_language, 'product', $trid, $translated_product_title );
		update_post_meta( $product, '_override_tab_layout', 'yes' );
		$tab_manager = $this->get_test_subject();
		$this->setup_and_check_global_tab_type( $product, $translated_product, $tab_manager, $translated_product_title );
		$this->setup_and_check_product_tab_type( $product, $translated_product, $tab_manager, $product_title );
		$this->setup_and_check_core_tab_type( $product, $translated_product, $tab_manager, $product_title );
	}

	/**
	 * @return WCML_Tab_Manager
	 */
	private function get_test_subject() {
		return new WCML_Tab_Manager( $this->sitepress, $this->woocommerce, $this->woocommerce_wpml, $this->wpdb );
	}

	/**
	 * @param $product
	 * @param $translated_product
	 * @param $tab_manager
	 * @param $title
	 */
	private function setup_and_check_global_tab_type( $product, $translated_product, $tab_manager, $title ) {
		$tab_data = array(
			'position' => 'top',
			'type'     => 'global',
			'id'       => $translated_product,
			'name'     => sanitize_title( $title ),
		);
		update_post_meta( $product, '_product_tabs', array( 'key' => $tab_data ) );
		$tab_manager->sync_tabs( $product, $translated_product, array(), $this->second_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$expected = array(
			'global_tab_' . $translated_product => $tab_data,
		);
		$this->assertEquals( $output, $expected );

		$_POST['icl_ajx_action'] = 'make_duplicates';
		$tab_manager->sync_tabs( $product, $translated_product, array(), $this->second_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$expected = array(
			'global_tab_' . $translated_product => $tab_data,
		);
		$this->assertEquals( $output, $expected );
		unset( $_POST['icl_ajx_action'] );
	}

	/**
	 * @param $product
	 * @param $translated_product
	 * @param $tab_manager
	 * @param $title
	 */
	private function setup_and_check_product_tab_type( $product, $translated_product, $tab_manager, $title ) {
		$tab_data = array(
			'position' => 'top',
			'type'     => 'product',
			'id'       => null,
			'name'     => sanitize_title( $title ),
		);
		update_post_meta( $product, '_product_tabs', array( 'key' => $tab_data ) );

		$data = array(
			md5( 'tab_' . $tab_data['position'] . '_title' )   => random_string(),
			md5( 'tab_' . $tab_data['position'] . '_heading' ) => random_string(),
		);

		// Perform tab sync.
		$tab_manager->sync_tabs( $product, $translated_product, $data, $this->second_language );
		$wc_product_tab = get_posts(
			array(
				'posts_per_page'   => 1,
				'post_status'      => 'publish',
				'post_type'        => 'wc_product_tab',
				'suppress_filters' => true,
			)
		);
		$tab_data = array(
			'position' => 'top',
			'type'     => 'product',
			'id'       => $wc_product_tab[0]->ID,
			'name'     => sanitize_title( $data[ md5( 'tab_' . $tab_data['position'] . '_title' ) ] ),
		);
		$expected = array(
			'product_tab_' . $wc_product_tab[0]->ID => $tab_data,
		);
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$this->assertEquals( $output, $expected );

		// insert product tab
		$tab_id         = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, random_string() );
		$trid           = $this->sitepress->get_element_trid( $tab_id, 'post_wc_product_tab' );
		$translated_tab = wpml_test_insert_post( $this->second_language, 'wc_product_tab', $trid, random_string() );
		$tab_data = array(
			'position' => 'top',
			'type'     => 'product',
			'id'       => $tab_id,
			'name'     => sanitize_title( $title ),
		);
		update_post_meta( $product, '_product_tabs', array( 'key' => $tab_data ) );

		// Perform tab sync.
		$tab_manager->sync_tabs( $product, $translated_product, $data, $this->second_language );
		wp_cache_init();
		$wc_product_tab = get_post( $translated_tab );
		$this->assertEquals( $wc_product_tab->post_title, $data[ md5( 'tab_' . $tab_data['position'] . '_title' ) ] );
		$this->assertEquals( $wc_product_tab->post_content, $data[ md5( 'tab_' . $tab_data['position'] . '_heading' ) ] );

		//test non-ascii symbols in product tab name
		$data = array(
			md5( 'tab_' . $tab_data['position'] . '_title' )   => 'Тестовий таб',
			md5( 'tab_' . $tab_data['position'] . '_heading' ) => random_string(),
		);
		// Perform tab sync.
		$tab_manager->sync_tabs( $product, $translated_product, $data, $this->second_language );
		wp_cache_init();

		$trnsl_product_tabs = maybe_unserialize( get_post_meta( $translated_product, '_product_tabs', true ) );
		foreach( $trnsl_product_tabs as $key => $trnsl_product_tab ){
			$this->assertEquals( $trnsl_product_tab[ 'name' ], str_replace( '_', '-', $key ) );
		}

	}

	/**
	 * @param $product
	 * @param $translated_product
	 * @param $tab_manager
	 * @param $title
	 */
	private function setup_and_check_core_tab_type( $product, $translated_product, $tab_manager, $title ) {
		$tab_id         = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, random_string() );
		$trid           = $this->sitepress->get_element_trid( $tab_id, 'post_wc_product_tab' );
		$translated_tab = wpml_test_insert_post( $this->second_language, 'wc_product_tab', $trid, random_string() );

		$data = array(
			md5( 'coretab_' . $tab_id . '_title' )   => random_string(),
			md5( 'coretab_' . $tab_id . '_heading' ) => random_string(),
		);

		$tab_data = array(
			'position' => 'top',
			'type'     => 'core',
			'id'       => $tab_id,
			'name'     => sanitize_title( $title ),
			'title'    => $data[ md5( 'coretab_' . $tab_id . '_title' ) ],
			'heading'  => $data[ md5( 'coretab_' . $tab_id . '_heading' ) ],
		);
		update_post_meta( $product, '_product_tabs', array( 'key' => $tab_data ) );

		// Perform tab sync.
		$tab_manager->sync_tabs( $product, $translated_product, $data, $this->default_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$expected = array(
			'key' => array_merge(
				$tab_data,
				array(
					'title'   => $data[ md5( 'coretab_' . $tab_id . '_title' ) ],
					'heading' => $data[ md5( 'coretab_' . $tab_id . '_heading' ) ],
				)
			),
		);
		$this->assertEquals( $expected, $output );


		$tab_manager->sync_tabs( $product, $translated_product, $data, $this->second_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$this->assertEquals( $expected, $output );

		// Empty data.
		$tab_manager->sync_tabs( $product, $translated_product, array(), $this->second_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$this->assertEquals( $expected, $output );

		// Get data from $_POST
		$_POST['product_tab_title'][ $tab_data['position'] ] = random_string();
		$_POST['product_tab_heading'][ $tab_data['position'] ] = random_string();
		$tab_manager->sync_tabs( $product, $translated_product, array(), $this->second_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$expected['key']['title'] = $_POST['product_tab_title'][ $tab_data['position'] ];
		$expected['key']['heading'] = $_POST['product_tab_heading'][ $tab_data['position'] ];
		$this->assertEquals( $expected, $output );

		$_POST['icl_ajx_action'] = 'make_duplicates';
		$tab_manager->sync_tabs( $product, $translated_product, array(), $this->second_language );
		$output = get_post_meta( $translated_product, '_product_tabs', true );
		$this->assertEquals( $expected, $output );
		unset( $_POST['icl_ajx_action'] );
	}

	/**
	 * @test
	 */
	public function duplicate_custom_fields_exceptions() {
		$tab_manager = $this->get_test_subject();
		$expected = array(
			'test',
			'_product_tabs',
		);
		$output = $tab_manager->duplicate_custom_fields_exceptions( array( 'test' ) );
		$this->assertEquals( $expected, $output );
	}

	/**
	 * @test
	 */
	public function force_set_language_information_on_product_tabs() {
		$tab_id         = wpml_test_insert_post( $this->second_language, 'product', false, random_string() );
		$child_tab_id = wp_insert_post(
			array(
				'post_type'   => 'wc_product_tab',
				'post_title'  => random_string(),
				'post_parent' => $tab_id,
			)
		);
		$tab_manager = $this->get_test_subject();
		$tab_manager->force_set_language_information_on_product_tabs( $child_tab_id, get_post( $child_tab_id ) );
		$this->assertEquals( $this->second_language, $this->sitepress->get_language_for_element( $child_tab_id, 'post_wc_product_tab' ) );
	}

	/**
	 * @test
	 */
	public function append_custom_tabs_to_translation_package() {
		$title   = random_string();
		$product = wpml_test_insert_post( $this->default_language, 'product', false, $title );
		update_post_meta( $product, '_override_tab_layout', 'yes' );
		$tab_data = array(
			'title'   => random_string(),
			'heading' => random_string(),
		);
		update_post_meta( $product, '_product_tabs', array( 'product_tab_' . $product => $tab_data ) );
		$product = get_post( $product );
		$expected = array(
			'contents' => array(
				'product_tabs:product_tab:' . $product->ID . ':title' => array(
					'translate' => 1,
					'data'      => $this->tp->encode_field_data( $product->post_title, 'base64' ),
					'format'    => 'base64',
				),
				'product_tabs:product_tab:' . $product->ID . ':description' => array(
					'translate' => 1,
					'data'      => $this->tp->encode_field_data( $product->post_content, 'base64' ),
					'format'    => 'base64',
				),
			),
		);
		set_current_screen( 'admin' );
		$tab_manager = $this->get_test_subject();
		$this->assertEquals( $expected, $tab_manager->append_custom_tabs_to_translation_package( array(), $product ) );

		update_post_meta( $product->ID, '_product_tabs', array( 'core_tab_' . $product->ID => $tab_data ) );
		$expected = array(
			'contents' => array(
				'product_tabs:core_tab_title:' . $product->ID => array(
					'translate' => 1,
					'data'      => $this->tp->encode_field_data( $tab_data['title'], 'base64' ),
					'format'    => 'base64',
				),
				'product_tabs:core_tab_heading:' . $product->ID => array(
					'translate' => 1,
					'data'      => $this->tp->encode_field_data( $tab_data['heading'], 'base64' ),
					'format'    => 'base64',
				),
			),
		);
		$this->assertEquals( $expected, $tab_manager->append_custom_tabs_to_translation_package( array(), $product ) );
		set_current_screen( 'front' );
	}

	/**
	 * @test
	 */
	public function save_custom_tabs_translation() {
		$title   = random_string();
		$tab_id  = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, random_string() );
		$product = wpml_test_insert_post( $this->default_language, 'product', false, $title );
		update_post_meta( $product, '_override_tab_layout', 'yes' );
		$orig_tab_data = array(
			'product_tab_' . $tab_id => array( 'position' => 'top' ),
			'core_tab_' . $tab_id    => array( 'position' => 'bottom' ),
		);
		update_post_meta( $product, '_product_tabs', $orig_tab_data );
		$title = random_string();
		$heading = random_string();
		$data = array(
			array(
				'field_type' => 'product_tabs:product_tab:' . $tab_id . ':title',
				'data'       => $title,
			),
			array(
				'field_type' => 'product_tabs:product_tab:' . $tab_id . ':description',
				'data'       => random_string(),
			),
			array(
				'field_type' => 'product_tabs:core_tab_title:' . $tab_id,
				'data'       => $title,
			),
			array(
				'field_type' => 'product_tabs:core_tab_heading:' . $tab_id,
				'data'       => $heading,
			),
		);

		$job = new stdClass();
		$job->original_doc_id = $product;
		$job->language_code = $this->second_language;

		$tab_manager = $this->get_test_subject();
		$tab_manager->save_custom_tabs_translation( $product, $data, $job );
		wp_cache_init();
		$trid = $this->sitepress->get_element_trid( $tab_id, 'post_wc_product_tab' );
		$tr_tab_id = $this->sitepress->get_element_translations( $trid, 'post_wc_product_tab' );
		$expected = array_merge(
			$orig_tab_data,
			array(
				'product_tab_' .  $tr_tab_id[ $this->second_language ]->element_id => array(
					'position' => $orig_tab_data[ 'product_tab_' . $tab_id ]['position'],
					'type'     => 'product',
					'id'       => $tr_tab_id[ $this->second_language ]->element_id,
					'name'     => sanitize_title( $title ),
				),
				'core_tab_' .  $tab_id => array(
					'type'     => 'core',
					'position' => $orig_tab_data[ 'core_tab_' . $tab_id ]['position'],
					'id'       => $tab_id,
					'title'    => $title,
					'heading'  => $heading,
				),
			)
		);
		$output = get_post_meta( $product, '_product_tabs', true );
		$this->assertEquals( $expected, $output );
	}

	/**
	 * @test
	 */
	public function wc_tab_manager_tab_id() {
		$tab_id         = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, random_string() );
		$trid           = $this->sitepress->get_element_trid( $tab_id, 'post_wc_product_tab' );
		$translated_tab = wpml_test_insert_post( $this->second_language, 'wc_product_tab', $trid, random_string() );
		$this->assertEquals( $tab_id, $this->get_test_subject()->wc_tab_manager_tab_id( $translated_tab ) );
	}

	/**
	 * @test
	 */
	public function get_product_tabs() {
		$tab_id = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, random_string() );
		update_option( 'wc_tab_manager_default_layout', 'test_value' );
		$this->assertEquals( 'test_value', $this->get_test_subject()->get_product_tabs( $tab_id ) );
		update_post_meta( $tab_id, '_override_tab_layout', 'yes' );
		update_post_meta( $tab_id, '_product_tabs', 'another_value' );
		$this->assertEquals( 'another_value', $this->get_test_subject()->get_product_tabs( $tab_id ) );
	}

	/**
	 * @test
	 */
	public function custom_box_html_data() {
		$title   = random_string();
		$heading = random_string();
		$product = wpml_test_insert_post( $this->default_language, 'product', false, $title );
		$trid = $this->sitepress->get_element_trid( $product, 'post_product' );
		$tr_product = wpml_test_insert_post( $this->second_language, $trid, false, $title );

		$tab_id = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, $title );
		$trid = $this->sitepress->get_element_trid( $tab_id, 'post_wc_product_tab' );
		$tr_tab_id = wpml_test_insert_post( $this->second_language, 'wc_product_tab', $trid, $title );
		$tab_object = get_post( $tab_id );

		update_post_meta( $product, '_override_tab_layout', 'yes' );
		update_post_meta( $tr_product, '_override_tab_layout', 'yes' );
		$tab_data = array(
			'product_tab_' .  $tab_id => array(
				'position' => 'top',
				'type'     => 'product',
				'id'       => $tab_id,
				'name'     => sanitize_title( $title ),
			),
			'core_tab_' . $tab_id => array(
				'type'     => 'core',
				'position' => 'top',
				'id'       => $tab_id,
				'title'    => $title,
				'heading'  => $heading,
			),
		);
		update_post_meta( $product, '_product_tabs', $tab_data );

		$heading_translate = random_string();
		$title_translate = random_string();
		$tab_data = array(
			'product_tab_' .  $tr_tab_id => array(
				'type'     => 'product',
				'position' => 'top',
				'id'       => $tr_tab_id,
				'name'     => sanitize_title( $title ),
			),
			'core_tab_' . $tr_tab_id => array(
				'type'     => 'core',
				'position' => 'top',
				'id'       => $tab_id,
				'title'    => $title_translate,
				'heading'  => $heading_translate,
			),
		);
		update_post_meta( $tr_product, '_product_tabs', $tab_data );

		$tab_manager = $this->get_test_subject();
		$tr_product = get_post( $tr_product );
		$tr_tab_object = get_post( $tr_tab_id );
		$expected = array(
			'tab_top_title' => array(
				'original'    => $tab_object->post_title,
				'translation' => $tr_tab_object->post_title,
			),
			'tab_top_heading' => array(
				'original'    => $tab_object->post_content,
				'translation' => $tr_tab_object->post_content,
			),
			'coretab_' . $tab_id . '_title' => array(
				'original'    => $title,
				'translation' => $title_translate,
			),
			'coretab_' . $tab_id . '_heading' => array(
				'original'    => $heading,
				'translation' => $heading_translate,
			),
		);

		$this->assertEquals( $expected, $tab_manager->custom_box_html_data( array(), $product, $tr_product, $this->second_language ) );
		$expected = array(
			'tab_top_title' => array(
				'original'    => $tab_object->post_title,
			),
			'tab_top_heading' => array(
				'original'    => $tab_object->post_content,
			),
			'coretab_' . $tab_id . '_title' => array(
				'original'    => $title,
			),
			'coretab_' . $tab_id . '_heading' => array(
				'original'    => $heading,
			),
		);
		$this->assertEquals( $expected, $tab_manager->custom_box_html_data( array(), $product, null, $this->second_language ) );
	}

	/**
	 * @test
	 */
	public function custom_box_html() {
		$product = wpml_test_insert_post( $this->default_language, 'product', false );
		update_post_meta( $product, '_override_tab_layout', 'yes' );
		$job_details = array(
			'job_type'             => 'product',
			'job_id'               => $product,
			'target'               => $this->second_language,
			'translation_complete' => true,
		);
		update_post_meta( $product, 'test_key', 'test_meta' );
		$title = random_string();
		$heading = random_string();
		$tab_id = wpml_test_insert_post( $this->default_language, 'wc_product_tab', false, $title );
		$trid = $this->sitepress->get_element_trid( $tab_id, 'post_wc_product_tab' );
		$tr_tab_id = wpml_test_insert_post( $this->second_language, 'wc_product_tab', $trid, $title );
		$tab_data = array(
			'product_tab_' .  $tab_id => array(
				'type'     => 'product',
				'position' => 'top',
				'id'       => $tab_id,
				'name'     => sanitize_title( $title ),
			),
			'core_tab_' . $tab_id => array(
				'type'     => 'core',
				'position' => 'top',
				'id'       => $tab_id,
				'title'    => $title,
				'heading'  => $heading,
			),
		);
		update_post_meta( $product, '_product_tabs', $tab_data );

		$obj = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
		$tab_manager = $this->get_test_subject();
		$data = array(
			'coretab_' . $tab_id . '_heading' => array(
				'original'    => random_string(),
				'translation' => random_string(),
				'is_complete' => true,
			),
			'coretab_' . $tab_id . '_title' => array(
				'original'    => random_string(),
				'translation' => random_string(),
				'is_complete' => true,
			),
			'tab_top_title' => array(
				'original'    => random_string(),
				'translation' => random_string(),
				'is_complete' => true,
			),
			'tab_top_heading' => array(
				'original'    => random_string(),
				'translation' => random_string(),
				'is_complete' => true,
			),
		);
		$tab_manager->custom_box_html( $obj, $product, $data );
		$product_obj = get_post( $product );
		$expected = array(
			array(
				'title'                 => 'Title',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'title',
				'field_data'            => $product_obj->post_title,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Slug',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'slug',
				'field_data'            => $product_obj->post_name,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Content / Description',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'product_content',
				'field_data'            => $product_obj->post_content,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'product_excerpt',
				'field_data'            => $product_obj->post_excerpt,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '1',
				'field_type'            => '_purchase_note',
				'field_data'            => '',
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Title',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'tab_top_title',
				'field_data'            => $data['tab_top_title']['original'],
				'field_data_translated' => $data['tab_top_title']['translation'],
				'field_finished'        => '1',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'tab_top_heading',
				'field_data'            => $data['tab_top_heading']['original'],
				'field_data_translated' => $data['tab_top_heading']['translation'],
				'field_finished'        => '1',
			),
			array(
				'title'                 => 'Title',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'coretab_' . $tab_id . '_title',
				'field_data'            => $data[ 'coretab_' . $tab_id . '_title' ]['original'],
				'field_data_translated' => $data[ 'coretab_' . $tab_id . '_title' ]['translation'],
				'field_finished'        => '1',
			),
			array(
				'title'                 => 'Heading',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'coretab_' . $tab_id . '_heading',
				'field_data'            => $data[ 'coretab_' . $tab_id . '_heading' ]['original'],
				'field_data_translated' => $data[ 'coretab_' . $tab_id . '_heading' ]['translation'],
				'field_finished'        => '1',
			),
		);
		$this->assertEquals( $expected, $obj->get_all_fields() );
	}
}
