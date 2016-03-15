<?php

class WCML_Locale{

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress ){
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        $this->load_locale();

        add_filter( 'locale',array( $this, 'update_product_action_locale_check' ) );
        add_action( 'woocommerce_email', array( $this, 'woocommerce_email_refresh_text_domain' ) );
        add_action( 'wp_ajax_woocommerce_update_shipping_method', array( $this, 'wcml_refresh_text_domain' ), 9 );
        add_action( 'wp_ajax_nopriv_woocommerce_update_shipping_method', array( $this, 'wcml_refresh_text_domain' ), 9 );

    }

    function load_locale(){
        load_plugin_textdomain( 'woocommerce-multilingual', false, WCML_LOCALE_PATH );
    }

    /* Change locale to saving language - needs for sanitize_title exception wcml-390 */
    public function update_product_action_locale_check( $locale ){
        if( isset($_POST['action']) && $_POST['action'] == 'wpml_translation_dialog_save_job' ){
            return $this->sitepress->get_locale( $_POST[ 'job_details' ][ 'target' ] );
        }
        return $locale;
    }

    public function woocommerce_email_refresh_text_domain(){
        if( !isset( $_GET[ 'page' ] ) || ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] != 'wc-settings' ) ){
            $this->wcml_refresh_text_domain();
        }
    }

    public function wcml_refresh_text_domain(){
        global $woocommerce;
        $domain = 'woocommerce';
        unload_textdomain( $domain );
        $woocommerce->load_plugin_textdomain();
    }

}