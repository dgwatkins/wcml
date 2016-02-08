<?php

class WCML_Menus_Wrap extends WPML_Templates_Factory {

    private $woocommerce_wpml;

    function __construct( &$woocommerce_wpml ){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;
    }

    public function get_model(){

        $current_tab = $this->get_current_tab();

        $model = array(

            'strings' => array(
                'title'             => __('WooCommerce Multilingual', 'woocommerce-multilingual'),
                'untranslated_terms'=> __('You have untranslated terms!', 'woocommerce-multilingual')
            ),
            'menu' => array(
                'products' => array(
                    'title'     => __('Products', 'woocommerce-multilingual'),
                    'url'       => admin_url('admin.php?page=wpml-wcml'),
                    'active'    => $current_tab == 'products' ? 'nav-tab-active' : ''
                ),
                'taxonomies' => array(
                    'product_cat' => array(
                        'name'      => __('Product Categories', 'woocommerce-multilingual'),
                        'title'     => !WCML_Terms::is_fully_translated( 'product_cat' ) ? __('You have untranslated terms!', 'woocommerce-multilingual') : '',
                        'active'    => $current_tab == 'product_cat' ? 'nav-tab-active':'',
                        'url'       => admin_url('admin.php?page=wpml-wcml&tab=product_cat'),
                        'translated'=> WCML_Terms::is_fully_translated( 'product_cat' )
                    ),
                    'product_tag' => array(
                        'name'      => __('Product Tags', 'woocommerce-multilingual'),
                        'title'     => !WCML_Terms::is_fully_translated( 'product_tag' ) ? __('You have untranslated terms!', 'woocommerce-multilingual') : '',
                        'active'    => $current_tab == 'product_tag' ? 'nav-tab-active':'',
                        'url'       => admin_url('admin.php?page=wpml-wcml&tab=product_tag'),
                        'translated'=> WCML_Terms::is_fully_translated( 'product_tag' )
                    )
                ),
                'attributes' => array(
                    'name'      => __('Product Attributes', 'woocommerce-multilingual'),
                    'active'    => $current_tab == 'product-attributes' ? 'nav-tab-active':'',
                    'url'       => admin_url('admin.php?page=wpml-wcml&tab=product-attributes'),
                ),
                'shipping_classes' => array(
                    'name'      => __('Shipping Classes', 'woocommerce-multilingual'),
                    'title'     => !WCML_Terms::is_fully_translated( 'product_shipping_class' ) ? __('You have untranslated terms!', 'woocommerce-multilingual') : '',
                    'active'    => $current_tab == 'product_shipping_class' ? 'nav-tab-active':'',
                    'url'       => admin_url('admin.php?page=wpml-wcml&tab=product_shipping_class'),
                    'translated'=> WCML_Terms::is_fully_translated( 'product_shipping_class' )
                ),
                'settings' => array(
                    'name'      => __( 'Settings', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'settings' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=settings' )
                ),
                'multi_currency' => array(
                    'name'      => __( 'Multi-currency', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'multi-currency' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' )
                ),
                'slugs' => array(
                    'name'      => __( 'Store URLs', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'slugs' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=slugs' )
                ),
                'status' => array(
                    'name'      => __( 'Status', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'status' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=status' )
                ),
            ),
            'can_manage_options' => current_user_can('wpml_manage_woocommerce_multilingual'),
            'rate' => array(
                'on'        => !isset( $this->woocommerce_wpml->settings['rate-block'] ),
                'message'   => sprintf(__('Thank you for using %sWooCommerce Multilingual%s! You can express your love and
                                    support by %s rating our plugin and saying that %sit works%s for you.', 'woocommerce_wpml'),
                    '<strong>',
                    '</strong>',
                    '<a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-multilingual?filter=5#postform" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
                    '<a href="https://wordpress.org/plugins/woocommerce-multilingual/?compatibility[version]='.$this->woocommerce_wpml->get_supported_wp_version().'&compatibility[topic_version]='.WCML_VERSION.'&compatibility[compatible]=1#compatibility" target="_blank">',
                    '</a>'

                ),
                'hide_text' => __('Hide','woocommerce-multilingual'),
                'nonce'     =>  wp_nonce_field('wcml_settings', 'wcml_settings_nonce', true, false)
            ),
            'content' => $this->get_current_menu_content( $current_tab )
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/',
        );
    }

    public function get_template() {
        return 'menus-wrap.twig';
    }

    protected function get_current_tab(){

        if(isset($_GET['tab'])){
            $current_tab = $_GET['tab'];
            if( !current_user_can('wpml_manage_woocommerce_multilingual') ){
                $current_tab = 'products';
            }
        }else{
            $current_tab = 'products';
        }

        return $current_tab;

    }

    protected function get_current_menu_content( $current_tab ){
        global $sitepress;

        $woocommerce_wpml = $this->woocommerce_wpml;

        $content = '';

        switch ( $current_tab ) {

            case 'products':
                if( current_user_can('wpml_manage_woocommerce_multilingual') ) {
                    global $wpdb;

                    $current_language = $sitepress->get_current_language();
                    $active_languages = $sitepress->get_active_languages();

                    $wcml_products_ui = new WCML_Products_UI();
                    $content = $wcml_products_ui->get_view();
                }
                break;

            case 'multi-currency':

                $wcml_mc_ui = new WCML_Multi_Currency_UI( $woocommerce_wpml, $sitepress );
                $content = $wcml_mc_ui->get_view();
                break;

            // TBD
            case 'product-attributes':
                if( current_user_can('wpml_operate_woocommerce_multilingual') ) {
                    ob_start();
                    include WCML_PLUGIN_PATH . '/menu/sub/product-attributes.php';
                    $content = ob_get_contents();
                    ob_end_clean();
                }
                break;

            // TBD
            case 'slugs':
                if( current_user_can('wpml_operate_woocommerce_multilingual') ) {
                    $current_language = $sitepress->get_current_language();
                    $active_languages = $sitepress->get_active_languages();
                    ob_start();
                    include WCML_PLUGIN_PATH . '/menu/sub/slugs.php';
                    $content = ob_get_contents();
                    ob_end_clean();
                }
                break;

            // TBD
            case 'status':
                ob_start();
                include WCML_PLUGIN_PATH . '/menu/sub/status.php';
                $content = ob_get_contents();
                ob_end_clean();
                break;

            // TBD
            case 'settings':
                if( current_user_can('wpml_operate_woocommerce_multilingual') ) {
                    ob_start();
                    include WCML_PLUGIN_PATH . '/menu/sub/settings.php';
                    $content = ob_get_contents();
                    ob_end_clean();
                }
                break;

            // TBD
            default:
                if( current_user_can('wpml_operate_woocommerce_multilingual') && in_array( $current_tab, array( 'product_cat', 'product_tag', 'product_shipping_class' ) ) ){
                    ob_start();
                    include WCML_PLUGIN_PATH . '/menu/sub/product-taxonomy.php';
                    $content = ob_get_contents();
                    ob_end_clean();
                    break;
                }

        }

        return $content;

    }


}