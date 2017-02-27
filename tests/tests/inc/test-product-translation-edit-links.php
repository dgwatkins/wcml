<?php

if( ! defined('WCML_TRANSLATION_METHOD_MANUAL') ){
	define('WCML_TRANSLATION_METHOD_MANUAL', 0);
}

if( ! defined('WCML_TRANSLATION_METHOD_EDITOR') ){
	define('WCML_TRANSLATION_METHOD_EDITOR', 1);
}

class Test_WCML_Product_Translation_Edit_Links extends WCML_UnitTestCase {

	private $test_data = array();

	function setUp(){
		set_current_screen( 'admin' );

		parent::setUp();

		$this->woocommerce_wpml->translation_editor = new WCML_Translation_Editor( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );


		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->test_data['orig_product_id'] = $orig_product->id;

		$this->test_data['trid'] = $orig_product->trid;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->test_data['es_product_id'] = $es_product->id;

		$this->test_data['expected'] = array(
			'manual_translation_url' => sprintf(home_url('/wp-admin/post.php?post=%d&amp;action=edit&amp;lang=es'), $this->test_data['es_product_id']),
			'translation_editor_url' => sprintf('admin.php?page=wpml-translation-management%%2Fmenu%%2Ftranslations-queue.php&return_url=%%2Fwp-admin%%2Fadmin.php&trid=%d&language_code=es&source_language_code=en',
				$this->test_data['trid'])
		);

		wp_set_current_user( 1 );
		set_current_screen('product');

		wpml_tm_load_status_display_filter();

	}

	private function set_wpml_translation_method( $method ){
		icl_set_setting('doc_translation_method', $method, true);
	}

	private function set_wcml_translation_method( $method ){
		$this->woocommerce_wpml->settings[ 'trnsl_interface' ] = $method;
		$this->woocommerce_wpml->update_settings();

		// Run this again to load the is_admin() filters
		if( $method == WCML_TRANSLATION_METHOD_EDITOR ){
			global $wpml_tm_status_display_filter;
			add_filter( 'wpml_link_to_translation', array( $wpml_tm_status_display_filter, 'filter_status_link' ), 10, 4 );
		}else{
			remove_filter( 'wpml_use_tm_editor', array( $this->woocommerce_wpml->translation_editor, 'force_woocommerce_native_editor'), 100 );
			remove_action( 'wpml_pre_status_icon_display', array( $this->woocommerce_wpml->translation_editor, 'force_remove_wpml_translation_editor_links'), 100 );
			$this->woocommerce_wpml->translation_editor = new WCML_Translation_Editor( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
		}
	}

	private function get_translation_link( $post_id, $language, $trid ){
		//$translated_post_id = apply_filters( 'translate_object_id', $post_id , 'product', true, $language ); // NOT WORKING

		$translations = $this->sitepress->get_element_translations($trid, 'post_product');
		$translated_post_id = $translations[ $language ]->element_id;
		$edit_post_link = get_edit_post_link( $translated_post_id );

		$link = apply_filters( 'wpml_link_to_translation', $edit_post_link, $post_id, $language, $trid );

		return $link;
	}

	public function test_product_translation_edit_links(){

		// WPML and WCML translation method set to manual
		$this->set_wpml_translation_method( ICL_TM_TMETHOD_MANUAL );
		$this->set_wcml_translation_method( WCML_TRANSLATION_METHOD_MANUAL );
		$this->assertEquals(
			$this->test_data['expected']['manual_translation_url'],
			$this->get_translation_link( $this->test_data['orig_product_id'], 'es',  $this->test_data['trid'])
		);

		//add_filter( 'wpml_use_tm_editor', array( $this->woocommerce_wpml->translation_editor, 'force_woocommerce_native_editor'), 100 );
		//add_action( 'wpml_pre_status_icon_display', array( $this->woocommerce_wpml->translation_editor, 'force_remove_wpml_translation_editor_links'), 100 );

		// WPML editor and WCML manual
		$this->set_wpml_translation_method( ICL_TM_TMETHOD_EDITOR );
		$this->set_wcml_translation_method( WCML_TRANSLATION_METHOD_MANUAL );
		$this->assertEquals(
			$this->test_data['expected']['manual_translation_url'],
			$this->get_translation_link( $this->test_data['orig_product_id'], 'es',  $this->test_data['trid'])
		);

		// WPML manual and WCML editor
		$this->set_wpml_translation_method( ICL_TM_TMETHOD_MANUAL );
		$this->set_wcml_translation_method( WCML_TRANSLATION_METHOD_EDITOR );

		$edit_url = $this->get_translation_link( $this->test_data['orig_product_id'], 'es',  $this->test_data['trid']);
		if( empty($_SERVER['REQUEST_URI']) ){
			$edit_url = str_replace( 'return_url', 'return_url=' . urlencode('/wp-admin/admin.php') , $edit_url );
		}
		$this->assertEquals( $this->test_data['expected']['translation_editor_url'], $edit_url );

		// WPML and WCML translation method set to editor
		$this->set_wpml_translation_method( ICL_TM_TMETHOD_EDITOR );
		$this->set_wcml_translation_method( WCML_TRANSLATION_METHOD_EDITOR );

		$edit_url = $this->get_translation_link( $this->test_data['orig_product_id'], 'es',  $this->test_data['trid']);
		if( empty($_SERVER['REQUEST_URI']) ){
			$edit_url = str_replace( 'return_url', 'return_url=' . urlencode('/wp-admin/admin.php') , $edit_url );
		}
		$this->assertEquals( $this->test_data['expected']['translation_editor_url'], $edit_url );

	}

}