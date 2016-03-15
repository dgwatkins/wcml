<?php

class WCML_Resources {

    private static $page;
    private static $tab;
    private static $is_wpml_wcml_page;
    private static $pagenow;

    private static $woocommerce_wcml;

    public static function set_up_resources( &$woocommerce_wcml ) {
        global $pagenow;

        self::$woocommerce_wcml =& $woocommerce_wcml;

        self::$page = isset($_GET['page']) ? $_GET['page'] : null;
        self::$tab = isset($_GET['tab']) ? $_GET['tab'] : null;
        self::$is_wpml_wcml_page = self::$page == 'wpml-wcml';
        self::$pagenow = $pagenow;

        self::load_css();
        self::load_js();
        self::load_tooltip_resources();

        $is_edit_product = self::$pagenow == 'post.php' && isset($_GET['post']) && get_post_type( $_GET['post'] ) == 'product';
        $is_original_product = isset( $_GET['post'] ) && self::$woocommerce_wcml->products->is_original_product( $_GET['post'] );
        $is_new_product = self::$pagenow == 'post-new.php' && isset($_GET['source_lang']) && isset($_GET['post_type']) && $_GET['post_type'] == 'product';

        if ( ($is_edit_product && !$is_original_product) || $is_new_product && !self::$woocommerce_wcml->settings['trnsl_interface'] ) {
            add_action( 'init', array(__CLASS__, 'load_lock_fields_js') );
            add_action( 'admin_footer', array(__CLASS__, 'hidden_label') );
        }
    }

    private static function load_css() {

        if ( self::$is_wpml_wcml_page ) {

            self::load_management_css();

            wp_register_style( 'wpml-dialogs', ICL_PLUGIN_URL . '/res/css/dialogs.css', null, ICL_SITEPRESS_VERSION );
            wp_enqueue_style( 'wcml-dialogs' );

        }


    }

    public static function load_management_css() {
        wp_register_style( 'wpml-wcml', WCML_PLUGIN_URL . '/res/css/management.css', array(), WCML_VERSION );
        wp_enqueue_style( 'wpml-wcml' );
    }

    private static function load_js() {

        if ( self::$is_wpml_wcml_page ) {

            wp_register_script( 'wcml-tm-scripts', WCML_PLUGIN_URL . '/res/js/scripts.js', array(
                'jquery',
                'jquery-ui-core',
                'jquery-ui-resizable'
            ), WCML_VERSION );
            wp_register_script( 'jquery-cookie', WCML_PLUGIN_URL . '/res/js/jquery.cookie.js', array('jquery'), WCML_VERSION );
            wp_register_script( 'wcml-editor', WCML_PLUGIN_URL . '/res/js/wcml-translation-editor.js', array('jquery', 'jquery-ui-core'), WCML_VERSION );
            wp_register_script( 'wcml-dialogs', WCML_PLUGIN_URL . '/res/js/dialogs.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog'), WCML_VERSION );
            wp_register_script( 'wcml-troubleshooting', WCML_PLUGIN_URL . '/res/js/troubleshooting.js', array('jquery'), WCML_VERSION );

            wp_enqueue_script( 'wcml-dialogs' );
            wp_enqueue_script( 'wcml-editor' );
            wp_enqueue_script( 'wcml-tm-scripts' );
            wp_enqueue_script( 'jquery-cookie' );
            wp_enqueue_script( 'wcml-troubleshooting' );


            wp_localize_script( 'wcml-tm-scripts', 'wcml_settings',
                array(
                    'nonce' => wp_create_nonce( 'woocommerce_multilingual' )
                )
            );

            self::load_tooltip_resources();
        }

        if ( self::$page == WPML_TM_FOLDER . '/menu/main.php' ) {
            wp_register_script( 'wpml_tm', WCML_PLUGIN_URL . '/res/js/wpml_tm.js', array('jquery'), WCML_VERSION );
            wp_enqueue_script( 'wpml_tm' );
        }

        if ( self::$page == 'wpml-wcml' && in_array( self::$tab, array('multi-currency', 'slugs') ) ) {
            wp_register_style( 'wcml-dialogs', WCML_PLUGIN_URL . '/res/css/dialogs.css', null, WCML_VERSION );
            wp_enqueue_style( 'wcml-dialogs' );
        }

        if ( self::$page == 'wpml-wcml' && self::$tab == 'multi-currency' ) {
            wp_register_script( 'multi-currency', WCML_PLUGIN_URL . '/res/js/multi-currency.js', array('jquery', 'jquery-ui-sortable'), WCML_VERSION, true );
            wp_enqueue_script( 'multi-currency' );
        }

        if ( self::$pagenow == 'options-permalink.php' ) {
            wp_register_style( 'wcml_op', WCML_PLUGIN_URL . '/res/css/options-permalink.css', null, WCML_VERSION );
            wp_enqueue_style( 'wcml_op' );
        }

        if ( !is_admin() ) {
            wp_register_script( 'cart-widget', WCML_PLUGIN_URL . '/res/js/cart_widget.js', array('jquery'), WCML_VERSION );
            wp_enqueue_script( 'cart-widget' );
        } else {
            wp_register_script( 'wcml-messages', WCML_PLUGIN_URL . '/res/js/wcml-messages.js', array('jquery'), WCML_VERSION );
            wp_enqueue_script( 'wcml-messages' );
        }

    }

    private static function load_tooltip_resources() {

        if ( class_exists( 'woocommerce' ) ) {
            wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array('jquery'), WC_VERSION, true );
            wp_register_script( 'wcml-tooltip-init', WCML_PLUGIN_URL . '/res/js/tooltip_init.js', array('jquery'), WCML_VERSION );
            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'wcml-tooltip-init' );
            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
        }

    }

    public static function load_lock_fields_js() {

        wp_register_script( 'wcml-lock-script', WCML_PLUGIN_URL . '/res/js/lock_fields.js', array('jquery'), WCML_VERSION );
        wp_enqueue_script( 'wcml-lock-script' );

        wp_localize_script( 'wcml-lock-script', 'unlock_fields', array(
                'menu_order' => self::$woocommerce_wcml->settings['products_sync_order'],
                'file_paths' => self::$woocommerce_wcml->settings['file_path_sync'])
        );
        wp_localize_script( 'wcml-lock-script', 'non_standard_fields', array(
            'ids' => apply_filters( 'wcml_js_lock_fields_ids', array() ),
            'classes' => apply_filters( 'wcml_js_lock_fields_classes', array() ),
            'input_names' => apply_filters( 'wcml_js_lock_fields_input_names', array() )
        ) );


    }

    public static function hidden_label() {
        global $sitepress;

        echo '<img src="' . WCML_PLUGIN_URL . '/res/images/locked.png" class="wcml_lock_img" alt="' .
            __( 'This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual' ) .
            '" title="' . __( 'This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual' ) .
            '" style="display: none;position:relative;left:2px;top:2px;">';

        if ( isset($_GET['post']) ) {
            $original_language = self::$woocommerce_wcml->products->get_original_product_language( $_GET['post'] );
            $original_id = apply_filters( 'translate_object_id', $_GET['post'], 'product', true, $original_language );
        } elseif ( isset($_GET['trid']) ) {
            $original_id = $sitepress->get_original_element_id_by_trid( $_GET['trid'] );
        }

        if ( isset($_GET['lang']) ) {
            $language = $_GET['lang'];
        } else {
            return;
        }

        echo '<h3 class="wcml_prod_hidden_notice">' .
            sprintf( __( "This is a translation of %s. Some of the fields are not editable. It's recommended to use the %s for translating products.",
                'woocommerce-multilingual' ),
                '<a href="' . get_edit_post_link( $original_id ) . '" >' . get_the_title( $original_id ) . '</a>',
                '<a data-action="product-translation-dialog" class="js-wcml-dialog-trigger" data-id="' . $original_id . '" data-job_id="" data-language="' . $language . '">' .
                __( 'WooCommerce Multilingual products translator', 'woocommerce-multilingual' ) . '</a>' ) . '</h3>';
    }
}