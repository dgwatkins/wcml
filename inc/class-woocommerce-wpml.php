<?php
class woocommerce_wpml {

    /** @var woocommerce_wpml */
    protected static $_instance = null;

    public $settings;
    /** @var  WCML_Troubleshooting */
    public $troubleshooting;
    /** @var  WCML_Endpoints */
    public $endpoints;
    /** @var WCML_Products */
    public $products;
    /** @var  WCML_Synchronize_Product_Data */
    public $sync_product_data;
    /** @var  WCML_Synchronize_Variations_Data */
    public $sync_variations_data;
    /** @var WCML_Store_Pages */
    public $store;
    /** @var WCML_Emails */
    public $emails;
    /** @var WCML_Terms */
    public $terms;
    /** @var WCML_Attributes */
    public $attributes;
    /** @var WCML_Orders */
    public $orders;
    /** @var WCML_Currencies */
    public $currencies;
    /** @var WCML_Multi_Currency */
    public $multi_currency;
    /** @var WCML_Languages_Upgrader */
    public $languages_upgrader;
    /** @var WCML_Url_Translation */
    public $url_translation;
    /** @var WCML_Coupons */
    public $coupons;
    /** @var WCML_Locale */
    public $locale;
    /** @var WCML_Media */
    public $media;
    /** @var WCML_Downloadable_Products */
    public $downloadable;
    /** @var WCML_WC_Strings */
    public $strings;
    /** @var WCML_WC_Shipping */
    public $shipping;
    /** @var  WCML_WC_Gateways */
    public $gateways;

    /** @var  WCML_Reports */
    private $reports;
    /** @var  WCML_Requests */
    public $requests;
    /** @var  WCML_Compatibility */
    // NOTE: revert back to private after wcml-1218
    public $compatibility;
    /** @var  WCML_xDomain_Data */
    private $xdomain_data;

    /** @var  WCML_WooCommerce_Rest_API_Support */
    private $wc_rest_api;


    public function __construct(){

        $this->settings = $this->get_settings();

        $this->currencies = new WCML_Currencies( $this );

        add_action('init', array($this, 'init'),2);
    }

    /**
     * Main instance.
     *
     * @since 3.8
     * @static
     * @return woocommerce_wpml
     */
    public static function instance() {
        global $woocommerce_wpml;

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
            $woocommerce_wpml = self::$_instance;
        }

        return self::$_instance;
    }
    public function init(){
        global $sitepress, $wpdb;

        new WCML_Upgrade;

        $this->dependencies = new WCML_Dependencies;
        $this->check_dependencies = $this->dependencies->check();
        $this->check_design_update = $this->dependencies->check_design_update();

        WCML_Admin_Menus::set_up_menus( $this, $sitepress, $this->check_dependencies );

        if( !$this->check_dependencies ){

            wp_register_style( 'otgs-ico', WCML_PLUGIN_URL . '/res/css/otgs-ico.css', null, WCML_VERSION );
            wp_enqueue_style( 'otgs-ico');

            WCML_Resources::load_management_css();
            WCML_Resources::load_tooltip_resources();
            return false;
        }

        $this->compatibility        = new WCML_Compatibility();

        $actions_that_need_mc = array(
                'save-mc-options',
                'wcml_new_currency',
                'wcml_save_currency',
                'wcml_delete_currency',
                'wcml_update_currency_lang',
                'wcml_update_default_currency',
                'wcml_price_preview'
        );
        if($this->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT
            || ( isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab']) && $_GET['tab'] == 'multi-currency' )
            || ( isset( $_POST[ 'action' ] ) && in_array( $_POST[ 'action' ], $actions_that_need_mc ) )
        ){
            $this->multi_currency = new WCML_Multi_Currency;
        }else{
            add_shortcode('currency_switcher', '__return_empty_string');
        }

        $this->troubleshooting      = new WCML_Troubleshooting();
        $this->endpoints            = new WCML_Endpoints;
        $this->products             = new WCML_Products( $this, $sitepress, $wpdb );
        $this->sync_product_data    = new WCML_Synchronize_Product_Data( $this, $sitepress, $wpdb );
        $this->sync_variations_data = new WCML_Synchronize_Variations_Data( $this, $sitepress, $wpdb );
        $this->store                = new WCML_Store_Pages;
        $this->emails               = new WCML_Emails;
        $this->terms                = new WCML_Terms( $this, $sitepress, $wpdb );
        $this->attributes           = new WCML_Attributes( $this, $sitepress, $wpdb );
        $this->orders               = new WCML_Orders;
        $this->strings              = new WCML_WC_Strings;
        $this->shipping             = new WCML_WC_Shipping( $sitepress );
        $this->gateways             = new WCML_WC_Gateways( $sitepress );
        $this->currencies           = new WCML_Currencies( $this );
        $this->xdomain_data         = new WCML_xDomain_Data;
        $this->languages_upgrader   = new WCML_Languages_Upgrader;
        $this->url_translation      = new WCML_Url_Translation ( $this, $sitepress );
        $this->requests             = new WCML_Requests;
        $this->translation_editor   = new WCML_Translation_Editor( $this, $sitepress, $wpdb );
        $this->cart                 = new WCML_Cart( $this, $sitepress );
        $this->links                = new WCML_Links( $this, $sitepress );
        $this->coupons              = new WCML_Coupons( $this, $sitepress );
        $this->locale               = new WCML_Locale( $this, $sitepress );
        $this->media                = new WCML_Media( $this, $sitepress, $wpdb );
        $this->downloadable         = new WCML_Downloadable_Products( $this, $sitepress );
        $this->reports              = new WCML_Reports;

        new WCML_Ajax_Setup;

        if ( 'yes' == get_option( 'woocommerce_api_enabled' ) ){
            $this->wc_rest_api = new WCML_WooCommerce_Rest_API_Support( $this, $sitepress );
        }

        WCML_Install::initialize( $this, $sitepress );

        WCML_Resources::set_up_resources( $this );

        add_filter('woocommerce_get_checkout_payment_url', array('WCML_Links', 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_cancel_order_url', array('WCML_Links', 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_return_url', array('WCML_Links', 'filter_woocommerce_redirect_location'));

        add_action('wp_ajax_wcml_update_setting_ajx', array($this, 'update_setting_ajx'));


        add_filter( 'default_hidden_columns', array( $this, 'filter_screen_options' ), 10, 2 );
        add_filter( 'admin_init', array( $this, 'save_translation_controls' ), 10, 1 );
        add_action( 'admin_notices', array( $this, 'product_page_admin_notices' ), 10 );
    }

    /**
     * Display admin notice for translation management column.
     */
    public function product_page_admin_notices() {
        global $sitepress;
        $current_screen = get_current_screen();
        if ( 'edit-product' === $current_screen->id ) {
            $translate_url = admin_url( 'admin.php?page=wpml-wcml' );
            $nonce = wp_create_nonce('enable_translation_controls');
            if ( $sitepress->show_management_column_content( 'product' ) ) {
                ?>
                <div class="notice notice-info">
                    <p>
                        <?php _e( 'You have translation controls enabled.', 'woocommerce-multilingual' ); ?>
                        <br>
                        <?php
                        echo sprintf( __( 'Disabling the translation controls will make this page load faster. The best place to translate products is in %sWPML-&gt;WooCommerce Multilingual%s.', 'woocommerce-multilingual' ), '<a href="' . $translate_url . '">', '</a>' );
                        ?>
                    </p>
                    <p>
                        <a id="translations_control" class="translations_control button-secondary" href="<?php echo admin_url( 'edit.php?post_type=product&translation_controls=0&nonce=' . $nonce ); ?>">
                            <?php _e( 'Disable translation controls',  'woocommerce-multilingual' ); ?>
                        </a>
                    </p>
                    <p>
                        <small><?php _e( 'P.S. You can also do that using Screen Options',  'woocommerce-multilingual' ); ?></small>
                    </p>
                </div>
                <?php
            } else {
                ?>
                <div class="notice notice-info">
                    <p>
                        <?php _e( 'We disabled translation controls here.', 'woocommerce-multilingual' ); ?>
                        <br>
                        <?php
                        echo sprintf( __( "Enabling the translation controls in this page can increase the load time for this admin screen.\n The best place to translate products is in %sWPML-&gt;WooCommerce Multilingual%s.", 'woocommerce-multilingual' ), '<a href="' . $translate_url . '">', '</a>' );
                        ?>
                    </p>
                    <p>
                        <a id="translations_control" class="translations_control button-secondary" href="<?php echo admin_url( 'edit.php?post_type=product&translation_controls=1&nonce=' . $nonce ); ?>">
                            <?php _e( 'Enable translation controls anyway',  'woocommerce-multilingual' ); ?>
                        </a>
                    </p>
                    <p>
                        <small><?php _e( 'P.S. You can also do that using Screen Options',  'woocommerce-multilingual' ); ?></small>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Set default option for translations management column.
     *
     * @param $hidden
     * @param $screen
     *
     * @return array
     */
    public function filter_screen_options( $hidden, $screen ) {
        if ( 'edit-product' === $screen->id ) {
            $hidden[] = 'icl_translations';
        }
        return $hidden;
    }

    /**
     * Save user options for management column.
     */
    public function save_translation_controls() {
        if ( isset( $_GET['translation_controls'] )
             && isset( $_GET['nonce'] )
             && wp_verify_nonce( $_GET['nonce'], 'enable_translation_controls' )
        ) {
            $user = get_current_user_id();
            $hidden_columns = get_user_meta( $user, 'manageedit-productcolumnshidden', true );
            if ( ! is_array( $hidden_columns ) ) {
                $hidden_columns = array();
            }
            if ( 0 === (int) $_GET['translation_controls'] ) {
                $hidden_columns[] = 'icl_translations';
            } else {
                $tr_control_index = array_search( 'icl_translations', $hidden_columns );
                if ( false !== $tr_control_index ) {
                    unset( $hidden_columns[ $tr_control_index ] );
                }
            }

            update_user_meta( $user, 'manageedit-productcolumnshidden', $hidden_columns );
            wp_safe_redirect( admin_url( 'edit.php?post_type=product' ) );
        }
    }

    public function get_settings(){

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

    public function update_settings($settings = null){
        if(!is_null($settings)){
            $this->settings = $settings;
        }
        update_option('_wcml_settings', $this->settings);
    }

    public function update_setting_ajx(){
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

    //get latest stable version from WC readme.txt
    public function get_stable_wc_version(){
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

    public function get_supported_wp_version(){
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
