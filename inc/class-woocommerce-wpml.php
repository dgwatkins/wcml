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
            $this->load_managment_css();
            return false;
        }

        $this->load_css_and_js();

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

        add_action('init', array($this,'load_locale'));

        register_deactivation_hook(__FILE__, array($this, 'deactivation_actions'));

        if(is_admin()){
            add_action('admin_footer', array($this, 'documentation_links'));
        }

        add_filter('woocommerce_get_checkout_payment_url', array($this, 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_cancel_order_url', array($this, 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_return_url', array($this, 'filter_woocommerce_redirect_location'));
        //add_filter('woocommerce_redirect', array($this, 'filter_woocommerce_redirect_location'));

        add_filter('woocommerce_paypal_args', array($this, 'filter_paypal_args'));

        if( ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product' && !$this->products->is_original_product($_GET['post'])) ||
            ($pagenow == 'post-new.php' && isset($_GET['source_lang']) && isset($_GET['post_type']) && $_GET['post_type'] == 'product')
            && !$this->settings['trnsl_interface']){
            add_action('init', array($this, 'load_lock_fields_js'));
            add_action( 'admin_footer', array($this,'hidden_label'));
        }

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

    function deactivation_actions(){
        delete_option('wpml_dismiss_doc_main');
    }

    function load_css_and_js() {
        global $pagenow;

        if( isset( $_GET[ 'page' ] ) ){

            $this->load_managment_css();

            if( in_array( $_GET[ 'page' ], array( 'wpml-wcml' ) ) ) {

	            wp_register_script( 'wcml-tm-scripts', WCML_PLUGIN_URL . '/res/js/scripts.js', array(
		            'jquery',
		            'jquery-ui-core',
		            'jquery-ui-resizable'
	            ), WCML_VERSION );
	            wp_register_script( 'jquery-cookie', WCML_PLUGIN_URL . '/res/js/jquery.cookie.js', array( 'jquery' ), WCML_VERSION );
                wp_register_script( 'wcml-editor', WCML_PLUGIN_URL . '/res/js/wcml-translation-editor.js', array( 'jquery', 'jquery-ui-core' ), WCML_VERSION );
                wp_register_script( 'wcml-dialogs', WCML_PLUGIN_URL . '/res/js/dialogs.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog'), WCML_VERSION );
                wp_register_script( 'wcml-troubleshooting', WCML_PLUGIN_URL . '/res/js/troubleshooting.js', array( 'jquery' ), WCML_VERSION );
                wp_register_style( 'wpml-dialogs', ICL_PLUGIN_URL . '/res/css/dialogs.css', null, ICL_SITEPRESS_VERSION );

                wp_enqueue_script( 'wcml-dialogs' );
                wp_enqueue_script( 'wcml-editor' );
                wp_enqueue_script( 'wcml-tm-scripts' );
                wp_enqueue_script( 'jquery-cookie' );
                wp_enqueue_script( 'wcml-troubleshooting' );
                wp_enqueue_style( 'wcml-dialogs' );

                wp_localize_script( 'wcml-tm-scripts', 'wcml_settings',
                    array(
                        'nonce'             => wp_create_nonce( 'woocommerce_multilingual' )
                    )
                );

                $this->load_tooltip_resources();
            }

            if( $_GET[ 'page' ] == WPML_TM_FOLDER.'/menu/main.php' ){
	            wp_register_script( 'wpml_tm', WCML_PLUGIN_URL . '/res/js/wpml_tm.js', array( 'jquery' ), WCML_VERSION );
                wp_enqueue_script( 'wpml_tm' );
            }

            if( $_GET[ 'page' ] == 'wpml-wcml' || ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'products' ) ) {
                wp_register_style( 'wcml-dialogs', WCML_PLUGIN_URL . '/res/css/dialogs.css', null, WCML_VERSION );
                wp_enqueue_style( 'wcml-dialogs' );
            }

            if( $_GET[ 'page' ] == 'wpml-wcml' && isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'multi-currency' ){
                wp_register_script( 'multi-currency', WCML_PLUGIN_URL . '/res/js/multi-currency.js', array( 'jquery', 'jquery-ui-sortable' ), WCML_VERSION, true );
                wp_enqueue_script( 'multi-currency' );
            }

        }

        if( $pagenow == 'options-permalink.php' ){
            wp_register_style( 'wcml_op', WCML_PLUGIN_URL . '/res/css/options-permalink.css', null, WCML_VERSION );
            wp_enqueue_style( 'wcml_op' );
        }

        if( !is_admin() ){
            wp_register_script( 'cart-widget', WCML_PLUGIN_URL . '/res/js/cart_widget.js', array( 'jquery' ), WCML_VERSION );
            wp_enqueue_script( 'cart-widget' );
        }else{

            wp_register_script( 'wcml-messages', WCML_PLUGIN_URL . '/res/js/wcml-messages.js', array( 'jquery' ), WCML_VERSION );
            wp_enqueue_script( 'wcml-messages' );

        }

    }

    function load_managment_css(){

        if( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wpml-wcml' ) {
            wp_register_style( 'wpml-wcml', WCML_PLUGIN_URL . '/res/css/management.css', array(), WCML_VERSION );
            wp_enqueue_style( 'wpml-wcml' );
        }

    }

    //load Tooltip js and styles from WC
    function load_tooltip_resources(){
        if( class_exists( 'woocommerce' ) ){
            wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );
	        wp_register_script( 'wcml-tooltip-init', WCML_PLUGIN_URL . '/res/js/tooltip_init.js', array( 'jquery' ), WCML_VERSION );
            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'wcml-tooltip-init' );
            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
        }
    }

    function load_lock_fields_js(){
	    wp_register_script( 'wcml-lock-script', WCML_PLUGIN_URL . '/res/js/lock_fields.js', array( 'jquery' ), WCML_VERSION );
        wp_enqueue_script('wcml-lock-script');

        wp_localize_script( 'wcml-lock-script', 'unlock_fields', array( 'menu_order' => $this->settings['products_sync_order'], 'file_paths' => $this->settings['file_path_sync'] ) );
        wp_localize_script( 'wcml-lock-script', 'non_standard_fields', array(
            'ids' => apply_filters( 'wcml_js_lock_fields_ids', array() ),
            'classes' => apply_filters( 'wcml_js_lock_fields_classes', array() ),
            'input_names' => apply_filters( 'wcml_js_lock_fields_input_names', array() )
        ) );
    }

    function hidden_label(){
	    echo '<img src="' . WCML_PLUGIN_URL . '/res/images/locked.png" class="wcml_lock_img" alt="' . __( 'This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual' ) . '" title="' . __( 'This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual' ) . '" style="display: none;position:relative;left:2px;top:2px;">';

        if( isset($_GET['post']) ){
            $original_language = $this->products->get_original_product_language($_GET['post']);
            $original_id = apply_filters( 'translate_object_id',$_GET['post'],'product',true,$original_language);
        }elseif( isset($_GET['trid']) ){
            global $sitepress;
            $original_id = $sitepress->get_original_element_id_by_trid( $_GET['trid'] );
        }

        if( isset($_GET['lang'])){
            $language =  $_GET['lang'];
        }else{
            return;
        }


        echo '<h3 class="wcml_prod_hidden_notice">'.sprintf(__("This is a translation of %s. Some of the fields are not editable. It's recommended to use the %s for translating products.",'woocommerce-multilingual'),'<a href="'.get_edit_post_link($original_id).'" >'.get_the_title($original_id).'</a>','<a data-action="product-translation-dialog" class="js-wcml-dialog-trigger" data-id="'.$original_id.'" data-job_id="" data-language="'. $language .'">'.__('WooCommerce Multilingual products translator','woocommerce-multilingual').'</a>').'</h3>';
    }

    function generate_tracking_link($link,$term=false,$content = false, $id = false){
        $params = '?utm_source=wcml-admin&utm_medium=plugin&utm_term=';
        $params .= $term?$term:'WPML';
        $params .= '&utm_content=';
        $params .= $content?$content:'required-plugins';
        $params .= '&utm_campaign=WCML';

        if($id){
            $params .= $id;
        }
        return $link.$params;
    }

    function documentation_links(){
        global $post, $pagenow;

        if( is_null( $post ) )
            return;

        $get_post_type = get_post_type($post->ID);

        if($get_post_type == 'product' && $pagenow == 'edit.php'){
            $prot_link = '<span class="button"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('https://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#4') .'" target="_blank">' .
                    __('How to translate products', 'sitepress') . '<\/a>' . '<\/span>';
            $quick_edit_notice = '<div id="quick_edit_notice" style="display:none;"><p>'. sprintf(__("Quick edit is disabled for product translations. It\'s recommended to use the %s for editing products translations. %s", 'woocommerce-multilingual'), '<a href="'.admin_url('admin.php?page=wpml-wcml&tab=products').'" >'.__('WooCommerce Multilingual products editor', 'woocommerce-multilingual').'</a>','<a href="" class="quick_product_trnsl_link" >'.__('Edit this product translation', 'woocommerce-multilingual').'</a>').'</p></div>';
            $quick_edit_notice_prod_link = '<input type="hidden" id="wcml_product_trnsl_link" value="'.admin_url('admin.php?page=wpml-wcml&tab=products&prid=').'">';
        ?>
                <script type="text/javascript">
                    jQuery(".subsubsub").append('<?php echo $prot_link ?>');
                    jQuery(".subsubsub").append('<?php echo $quick_edit_notice ?>');
                    jQuery(".subsubsub").append('<?php echo $quick_edit_notice_prod_link ?>');
                    jQuery(".quick_hide a").on('click',function(){
                        jQuery(".quick_product_trnsl_link").attr('href',jQuery("#wcml_product_trnsl_link").val()+jQuery(this).closest('tr').attr('id').replace(/post-/,''));
                    });

                    //lock feautured for translations
                    jQuery(document).on('click', '.featured a', function(){

                        if( jQuery(this).closest('tr').find('.quick_hide').size() > 0 ){

                            return false;

                        }

                    });

                </script>
        <?php
        }

        if(isset($_GET['taxonomy'])){
            $pos = strpos($_GET['taxonomy'], 'pa_');

            if($pos !== false && $pagenow == 'edit-tags.php'){
                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('https://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate attributes', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>';
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
            }
        }

        if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat'){

                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('https://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate product categories', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>';
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
        }
    }

    function filter_woocommerce_redirect_location($link){
        global $sitepress;
        return html_entity_decode($sitepress->convert_url($link));
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
