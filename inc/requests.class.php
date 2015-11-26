<?php
class WCML_Requests{
    
    function __construct(){
        
        add_action('init', array($this, 'run'));

        
    }
    
    function run(){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        //@Todo move to multi-currency.class.php
        if(isset($_POST['wcml_update_languages_currencies']) && isset($_POST['currency_for']) && wp_verify_nonce($nonce, 'wcml_update_languages_currencies')){
            global $wpdb;
            $currencies = $_POST['currency_for'];
            foreach($currencies as $key=>$language_currency){
                $exist_currency = $wpdb->get_var($wpdb->prepare("SELECT currency_id FROM " . $wpdb->prefix . "icl_languages_currencies WHERE language_code = %s",$key));
                if($language_currency != get_woocommerce_currency()){
                    if(!$exist_currency){
                        $wpdb->insert($wpdb->prefix .'icl_languages_currencies', array(
                                'currency_id' => $language_currency,
                                'language_code' => $key
                            )
                        );
                    } else {
                        $wpdb->update(
                            $wpdb->prefix .'icl_languages_currencies',
                            array(
                                'currency_id' => $language_currency
                            ),
                            array( 'language_code' => $key )
                        );

                        wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
                    }
                }elseif($exist_currency){
                    $wpdb->delete($wpdb->prefix .'icl_languages_currencies', array('language_code' => $key) );
                }
            }
        }


        if(isset($_POST['wcml_file_path_options_table']) && wp_verify_nonce($nonce, 'wcml_file_path_options_table')){

            }
      
        if(isset($_POST['wcml_save_settings']) && wp_verify_nonce($nonce, 'wcml_save_settings_nonce')){
            global $sitepress,$sitepress_settings;

            $woocommerce_wpml->settings['trnsl_interface'] = filter_input( INPUT_POST, 'trnsl_interface', FILTER_SANITIZE_NUMBER_INT );

            $woocommerce_wpml->settings['products_sync_date'] = empty($_POST['products_sync_date']) ? 0 : 1;
            $woocommerce_wpml->settings['products_sync_order'] = empty($_POST['products_sync_order']) ? 0 : 1;

            $wcml_file_path_sync = filter_input( INPUT_POST, 'wcml_file_path_sync', FILTER_SANITIZE_NUMBER_INT );

            $woocommerce_wpml->settings['file_path_sync'] = $wcml_file_path_sync;
            $woocommerce_wpml->update_settings();

            $new_value =$wcml_file_path_sync == 0?2:$wcml_file_path_sync;
            $sitepress_settings['translation-management']['custom_fields_translation']['_downloadable_files'] = $new_value;
            $sitepress_settings['translation-management']['custom_fields_translation']['_file_paths'] = $new_value;
            $sitepress->save_settings($sitepress_settings);
        }

        if(isset($_GET['wcml_action']) && $_GET['wcml_action'] = 'dismiss'){
            $woocommerce_wpml->settings['dismiss_doc_main'] = 'yes';
            $woocommerce_wpml->update_settings();
        }


        add_action('wp_ajax_wcml_ignore_warning', array( $this, 'update_settings_from_warning') );
    }

    function update_settings_from_warning(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_ignore_warning')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $woocommerce_wpml->settings[$_POST['setting']] = 1;
        $woocommerce_wpml->update_settings();

    }

    function update_first_setup_warning( $check_tax_only = false ){
        global $woocommerce_wpml;

        if( ( $check_tax_only && !$woocommerce_wpml->terms->has_wc_taxonomies_to_translate() )  || ( !$woocommerce_wpml->terms->has_wc_taxonomies_to_translate() && !$woocommerce_wpml->store->get_missing_store_pages() ) ){
            $woocommerce_wpml->settings['first_setup_warning'] = 1;
            $woocommerce_wpml->update_settings();
        }
    }

}