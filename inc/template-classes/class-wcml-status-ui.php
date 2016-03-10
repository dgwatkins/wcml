<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Status_UI extends WPML_Templates_Factory {

	private $woocommerce_wpml;
	private $sitepress;
	private $sitepress_settings;


	function __construct( &$woocommerce_wpml, &$sitepress, $sitepress_settings ){
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress = $sitepress;
		$this->sitepress_settings = $sitepress_settings;

	}

	public function init_twig_functions() {
		$function = new Twig_SimpleFunction( 'get_flag_url', array( $this, 'get_flag_url' ) );
		$this->get_twig()->addFunction( $function );
	}

	public function get_model() {
		$this->init_twig_functions();

		$model = array(
			'plugins_status' => array(
				'icl_version' => defined( 'ICL_SITEPRESS_VERSION' ),
				'media_version' => defined( 'WPML_MEDIA_VERSION' ),
				'tm_version' => defined( 'WPML_TM_VERSION' ),
				'st_version' => defined( 'WPML_ST_VERSION' ),
				'wc' => class_exists( 'Woocommerce' ),
				'icl_setup' => $this->sitepress->setup(),
				'strings' => array(
					'status' => __( 'Plugins Status', 'woocommerce-multilingual' ),
					'inst_active' => __( '%s is installed and active.', 'woocommerce-multilingual' ),
					'is_setup' => __( '%s is set up.', 'woocommerce-multilingual' ),
					'not_setup' => __( '%s is not set up.', 'woocommerce-multilingual' ),
					'wpml' => '<strong>WPML</strong>',
					'media' => '<strong>WPML Media</strong>',
					'tm' => '<strong>WPML Translation Management</strong>',
					'st' => '<strong>WPML String Translation</strong>',
					'wc' => '<strong>WooCommerce</strong>',
					'depends' => __( 'WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'woocommerce-multilingual' )
				)
			),
			'conf_warnings' => array(
				'default_language' => $this->sitepress->get_default_language(),
				'miss_slug_lang' => $this->get_missed_product_slug_translations_languages(),
				'prod_slug' => $this->woocommerce_wpml->strings->product_permalink_slug(),
				'support_st' => WPML_SUPPORT_STRINGS_IN_DIFF_LANG,
				'dismiss_non_default' => isset( $this->woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ? true : false,
				'xml_config_errors' => isset( $this->woocommerce_wpml->dependencies->xml_config_errors ) ? $this->woocommerce_wpml->dependencies->xml_config_errors : false,
				'slugs_tab' => admin_url( 'admin.php?page=wpml-wcml&tab=slugs' ),
				'st_lang' => $this->sitepress_settings['st']['strings_language'],
				'not_en_doc_page' => 'https://wpml.org/?page_id=355545',
				'strings' => array(
					'conf' => __( 'Configuration warnings', 'woocommerce-multilingual' ),
					'report' => __( 'Reporting miscelaneous configuration issues that can make WooCommerce Multilingual not run normally', 'woocommerce-multilingual' ),
					'base_not_trnsl' => __( 'Your product permalink base is not translated to:', 'woocommerce-multilingual' ),
					'url_not_work' => __( 'The urls for the translated products will not work.', 'woocommerce-multilingual' ),
					'trsl_urls' => __( 'Translate URLs', 'woocommerce-multilingual' ),
					'def_and_st_not_en' => __( "Your site's default language is not English and the strings language is also not English.", 'woocommerce-multilingual' ),
					'run_not_en' => __( 'Running WooCommerce multilingual with default language other than English.', 'woocommerce-multilingual' ),
					'url_problems' => __( 'This may cause problems with URLs in different languages.', 'woocommerce-multilingual' ),
					'change_def_lang' => __( 'Change default language', 'woocommerce-multilingual' ),
					'def_not_en' => __( "Your site's default language is not English.", 'woocommerce-multilingual' ),
					'attent_sett' => __( 'There are some settings that require careful attention.', 'woocommerce-multilingual' ),
					'over_sett' => __( 'Some settings from the WooCommerce Multilingual wpml-config.xml file have been overwritten.', 'woocommerce-multilingual' ),
					'check_conf' => __( 'You should check WPML configuration files added by other plugins or manual settings on the %s section.', 'woocommerce-multilingual' ),
					'cont_set' => '<a href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup' ) . '">'. __( 'Multilingual Content Setup', 'woocommerce-multilingual' ) .'</a>'
				),
				'nonces' => array(
					'fix_strings' => wp_create_nonce( 'wcml_fix_strings_language' )
				)
			),
			'store_page' => array(
				'miss_lang' => $this->woocommerce_wpml->store->get_missing_store_pages(),
				'install_link' => admin_url( 'admin.php?page=wc-status&tab=tools' ),
				'request_uri' => $_SERVER["REQUEST_URI"],
				'strings' => array(
					'store_pages' => __( 'WooCommerce Store Pages', 'woocommerce-multilingual' ),
					'pages_trnsl' => __( 'To run a multilingual e-commerce site, you need to have the WooCommerce shop pages translated to all the site\'s languages. Once all the pages are installed you can add the translations for them from this menu.', 'woocommerce-multilingual' ),
					'store_pages' => __( 'WooCommerce Store Pages', 'woocommerce-multilingual' ),
					'not_created' => __( 'One or more WooCommerce pages have not been created.', 'woocommerce-multilingual' ),
					'install' => __( 'Install WooCommerce Pages', 'woocommerce-multilingual' ),
					'not_exist' => __( 'WooCommerce store pages do not exist for these languages:', 'woocommerce-multilingual' ),
					'create_transl' => __( 'Create missing translations', 'woocommerce-multilingual' ),
					'translated_wpml' => __( 'These pages are currently being translated by translators via WPML: ', 'woocommerce-multilingual' ),
					'translated' => __( 'WooCommerce store pages are translated to all the site\'s languages.', 'woocommerce-multilingual' )
				),
				'nonces' => array(
					'create_pages' => wp_nonce_field( 'create_pages', 'wcml_nonce' )
				)
			),
			'taxonomies' => array(
				'taxonomies' => $this->get_taxonomies_data(),
				'strings' => array(
					'tax_missing' => __( 'Taxonomies missing translations', 'woocommerce-multilingual' ),
					'run_site' => __( 'To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'woocommerce-multilingual' ),
					'not_req_trnsl' => __( '%s do not require translation.', 'woocommerce-multilingual' ),
					'req_trnsl' => __( 'This taxonomy requires translation.', 'woocommerce-multilingual' ),
					'incl_trnsl' => __( 'Include in translation', 'woocommerce-multilingual' ),
					'miss_trnsl' => __( '%d %s are missing translations.', 'woocommerce-multilingual' ),
					'trnsl' => __( 'Translate %s', 'woocommerce-multilingual' ),
					'doesnot_req_trnsl' => __( 'This taxonomy does not require translation.', 'woocommerce-multilingual' ),
					'exclude' => __( 'Exclude from translation', 'woocommerce-multilingual' ),
					'all_trnsl' => __( 'All %s are translated.', 'woocommerce-multilingual' ),
					'not_to_trnsl' => __( 'Right now, there are no taxonomy terms needing translation.', 'woocommerce-multilingual' )
				),
				'nonces' => array(
					'ignore_tax' => wp_create_nonce( 'wcml_ingore_taxonomy_translation_nonce' )
				)
			),
			'troubl_url' => admin_url( 'admin.php?page=wpml-wcml&tab=troubleshooting' ),
			'strings' => array(
				'troubl' => __( 'Troubleshooting page', 'woocommerce-multilingual' )
			)
		);

		return $model;
	}

	public function get_flag_url( $language ){

		return $this->sitepress->get_flag_url( $language );

	}

	public function get_missed_product_slug_translations_languages(){

		$slug = $this->woocommerce_wpml->strings->product_permalink_slug();

		if ( has_filter( 'wpml_slug_translation_available') ) {

			if( version_compare( WPML_ST_VERSION, '2.2.6', '>' ) ){
				$slug_translation_languages = apply_filters( 'wpml_get_slug_translation_languages', array(), 'product' );
			} else {
				$slug_translation_languages = apply_filters( 'wpml_get_slug_translation_languages', array(), $slug );
			}

		} else {
			$string_id = icl_get_string_id( $slug, $this->woocommerce_wpml->url_translation->url_strings_context(), $this->woocommerce_wpml->url_translation->url_string_name('product') );
			$slug_translations = icl_get_string_translations_by_id( $string_id );
		}

		$miss_slug_lang = array();

		$string_language = $this->woocommerce_wpml->strings->get_string_language( $slug, $this->woocommerce_wpml->url_translation->url_strings_context(), $this->woocommerce_wpml->url_translation->url_string_name('product') );

		foreach( $this->sitepress->get_active_languages() as $lang_info ){
			if(
				(
					( isset( $slug_translations ) && !array_key_exists( $lang_info['code'], $slug_translations ) ) ||
					( isset( $slug_translation_languages ) && !in_array( $lang_info['code'], $slug_translation_languages ) )
				) && $lang_info['code'] != $string_language
			){
				$miss_slug_lang[] = $lang_info;
			}
		}

		return $miss_slug_lang;
	}

	public function get_taxonomies_data(){
		$taxonomies = $this->woocommerce_wpml->terms->get_wc_taxonomies();
		$taxonomies_data = array();

		foreach ( $taxonomies as $key => $taxonomy ) {
			$taxonomies_data[$key]['tax'] = $taxonomy;
			$taxonomies_data[$key]['untranslated'] = $this->woocommerce_wpml->terms->get_untranslated_terms_number($taxonomy);
			$taxonomies_data[$key]['fully_trans'] = $this->woocommerce_wpml->terms->is_fully_translated($taxonomy);
			$taxonomies_data[$key]['name'] = get_taxonomy($taxonomy)->labels->name;
			$taxonomies_data[$key]['url'] = admin_url( 'admin.php?page=wpml-wcml&tab=product-attributes&taxonomy=' . $taxonomy );
		}

		return $taxonomies_data;
	}

	public function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/status/',
		);
	}

	public function get_template() {
		return 'status.twig';
	}
}