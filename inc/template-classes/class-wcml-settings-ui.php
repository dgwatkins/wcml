<?php

class WCML_Settings_UI extends WPML_Templates_Factory {

    private $woocommerce_wpml;

    function __construct( &$woocommerce_wpml ){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;
    }

    public function get_model(){

        $model = array(
            'form' => array(
                'action' => $_SERVER['REQUEST_URI'],

                'translation_interface' => array(
                    'heading'   => __('Product Translation Interface','woocommerce-multilingual'),
                    'tip'       => __( 'The recommended way to translate products is using the products translation table
                                    in the WooCommerce Multilingual admin. Choose to go to the native WooCommerce interface,
                                    if your products include custom sections that require direct access.',
                                    'woocommerce-multilingual' ),
                    'controls'  => __('Choose what to do when clicking on the translation controls for products:', 'woocommerce-multilingual'),
                    'wcml'      => array(
                        'label' => __('Open the WooCommerce Multilingual product translation interface', 'woocommerce-multilingual'),

                    ),
                    'native'      => array(
                        'label' => __('Go to the native WooCommerce product editing screen', 'woocommerce-multilingual'),

                    ),
                    'controls_value' => $this->woocommerce_wpml->settings['trnsl_interface'],
                ),

                'synchronization' => array(
                    'heading'   => __('Products synchronization', 'woocommerce-multilingual'),
                    'tip'       => __( 'Configure specific product properties that should be synced to translations.', 'woocommerce-multilingual' ),
                    'sync_date' => array(
                        'value' => $this->woocommerce_wpml->settings['products_sync_date'],
                        'label' => __('Sync publishing date for translated products.', 'woocommerce-multilingual')
                    ),
                    'sync_order'=> array(
                        'value' => $this->woocommerce_wpml->settings['products_sync_order'],
                        'label' => __('Sync products and product taxonomies order.', 'woocommerce-multilingual')
                    ),
                ),

                'file_sync' => array(
                    'heading'   => __('File Paths Synchronization ', 'woocommerce-multilingual'),
                    'tip'       => __( 'If you are using downloadable products, you can choose to have their paths
                                            synchronized, or seperate for each language.', 'woocommerce-multilingual' ),
                    'value'         => $this->woocommerce_wpml->settings['file_path_sync'],
                    'label_same'    => __('Use the same file paths in all languages', 'woocommerce-multilingual'),
                    'label_diff'    => __('Different file paths for each language', 'woocommerce-multilingual'),
                ),


                'nonce'             => wp_nonce_field('wcml_save_settings_nonce', 'wcml_nonce', true, false),
                'save_label'        => __( 'Save changes', 'woocommerce-multilingual' ),

            ),

            'troubleshooting' => array(
                'url'   => admin_url( 'admin.php?page=' . basename( WCML_PLUGIN_PATH ) . '/menu/sub/troubleshooting.php' ),
                'label' => __( 'Troubleshooting page', 'woocommerce-multilingual' )
            )
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/',
        );
    }

    public function get_template() {
        return 'settings-ui.twig';
    }


}