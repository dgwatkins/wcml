<?php
class woocommerce_wpml {

    public $settings;

    public $troubleshooting;
    private $compatibility;
    public $endpoints;
    public $products;
    public $store;
    public $emails;
    public $terms;
    public $attributes;
    public $orders;
    public $currencies;
    public $multi_currency_support;
    public $multi_currency;
    private $xdomain_data;
    public $languages_upgrader;
    public $url_translation;
    private $reports;
    private $requests;


    function __construct(){

        $this->settings = $this->get_settings();

        $this->currencies = new WCML_Currencies( $this );

        add_action('init', array($this, 'init'),2);
    }

    function init(){
        global $sitepress,$pagenow;

        new WCML_Upgrade;

        $this->dependencies = new WCML_Dependencies;
        $this->check_dependencies = $this->dependencies->check();
        $this->check_design_update = $this->dependencies->check_design_update();

        if( !$this->check_dependencies ){
            wp_enqueue_style( 'onthegosystems-icon', WCML_PLUGIN_URL . '/res/css/otgs-ico.css' );
            WCML_Resources::load_managment_css();
            return false;
        }

        $actions_that_need_mc = array( 'save-mc-options', 'wcml_new_currency', 'wcml_save_currency', 'wcml_delete_currency',
                'wcml_update_currency_lang', 'wcml_update_default_currency', 'wcml_price_preview');
        if($this->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT
            || ( isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab']) && $_GET['tab'] == 'multi-currency' )
            || ( isset( $_POST[ 'action' ] ) && in_array( $_POST[ 'action' ], $actions_that_need_mc ) )
        ){
            $this->multi_currency_support = new WCML_Multi_Currency_Support;
            $this->multi_currency = new WCML_Multi_Currency;
        }else{
            add_shortcode('currency_switcher', '__return_empty_string');
        }

        WCML_Admin_Menus::set_up_menus( $this, $sitepress );

        $this->troubleshooting      = new WCML_Troubleshooting();
        $this->compatibility        = new WCML_Compatibility();
        $this->endpoints            = new WCML_Endpoints;
        $this->products             = new WCML_Products;
        $this->store                = new WCML_Store_Pages;
        $this->emails               = new WCML_Emails;
        $this->terms                = new WCML_Terms;
        $this->attributes           = new WCML_Attributes;
        $this->orders               = new WCML_Orders;
        $this->strings              = new WCML_WC_Strings;
        $this->currencies           = new WCML_Currencies( $this );
        $this->currency_switcher    = new WCML_Currency_Switcher;
        $this->xdomain_data         = new WCML_xDomain_Data;
        $this->languages_upgrader   = new WCML_Languages_Upgrader;
        $this->url_translation      = new WCML_Url_Translation;
        $this->requests             = new WCML_Requests;

        if(isset($_GET['page']) && $_GET['page'] == 'wc-reports'){
            $this->reports          = new WCML_Reports;
        }

        include WCML_PLUGIN_PATH . '/inc/woocommerce-2.0-backward-compatibility.php';

        new WCML_Ajax_Setup;
        new WCML_WooCommerce_Rest_API_Support;

        WCML_Install::initialize( $this, $sitepress );

        WCML_Resources::set_up_resources( $this );

        add_action('init', array($this,'load_locale'));

        add_filter('woocommerce_get_checkout_payment_url', array('WCML_Links', 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_cancel_order_url', array('WCML_Links', 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_return_url', array('WCML_Links', 'filter_woocommerce_redirect_location'));

        add_filter('woocommerce_paypal_args', array($this, 'filter_paypal_args'));

        add_action('wp_ajax_wcml_update_setting_ajx', array($this, 'update_setting_ajx'));

        //load WC translations
        add_action( 'icl_update_active_languages', array( $this, 'download_woocommerce_translations_for_active_languages' ) );
        add_action( 'wp_ajax_hide_wcml_translations_message', array($this, 'hide_wcml_translations_message') );

        add_filter( 'wpml_tm_dashboard_translatable_types', array( $this, 'hide_variation_type_on_tm_dashboard') );
    }

    function get_settings(){

        $defaults = array(
            'file_path_sync'               => 1,
            'is_term_order_synced'         => 0,
            'enable_multi_currency'        => WCML_MULTI_CURRENCIES_DISABLED,
            'dismiss_doc_main'             => 0,
            'trnsl_interface'              => 1,
            'currency_options'             => array(),
            'currency_switcher_product_visibility'             => 1
        );

        if(empty($this->settings)){
            $this->settings = get_option('_wcml_settings');
        }

        foreach($defaults as $key => $value){
            if(!isset($this->settings[$key])){
                $this->settings[$key] = $value;
            }
        }

        return $this->settings;
    }

    function update_settings($settings = null){
        if(!is_null($settings)){
            $this->settings = $settings;
        }
        update_option('_wcml_settings', $this->settings);
    }

    function update_setting_ajx(){
        $nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_settings')){
            die('Invalid nonce');
        }

        $data = $_POST;
        $error = '';
        $html = '';

        $this->settings[$data['setting']] = $data['value'];
        $this->update_settings();

        echo json_encode(array('html' => $html, 'error'=> $error));
        exit;
    }

    function load_locale(){
        load_plugin_textdomain('woocommerce-multilingual', false, WCML_LOCALE_PATH);
    }

    function filter_paypal_args($args) {
        global $sitepress;
        $args['lc'] = $sitepress->get_current_language();

        //filter URL when default permalinks uses
        $wpml_settings = $sitepress->get_settings();
        if( $wpml_settings[ 'language_negotiation_type' ] == 3 ){
            $args[ 'notify_url' ] = str_replace( '%2F&', '&', $args[ 'notify_url' ] );
        }

        return $args;
    }

    function hide_variation_type_on_tm_dashboard( $types ){
        unset( $types['product_variation'] );

        return $types;
    }

    //get latest stable version from WC readme.txt
    function get_stable_wc_version(){
        global $woocommerce;

        $file = $woocommerce->plugin_path(). '/readme.txt';
        $values = file($file);
        $wc_info = explode( ':', $values[5] );
        if( $wc_info[0] == 'Stable tag' ){
            $version =  trim( $wc_info[1] );
        }else{
            foreach( $values as $value ){
                $wc_info = explode( ':', $value );

                if( $wc_info[0] == 'Stable tag' ){
                    $version = trim( $wc_info[1] );
                }
            }
        }

        return $version;
    }

    function get_supported_wp_version(){
        $file = WCML_PLUGIN_PATH. '/readme.txt';

        $values = file($file);

        $version = explode( ':', $values[6] );

        if( $version[0] == 'Tested up to' ){
            return $version[1];
        }

        foreach( $values as $value ){
            $version = explode( ':', $value );

            if( $version[0] == 'Tested up to' ){
                return $version[1];
            }
        }

    }

}
