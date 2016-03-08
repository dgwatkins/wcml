<?php

class WCML_Currencies{

    private $woocommerce_wpml;

    public function __construct( &$woocommerce_wpml ) {
        $this->woocommerce_wpml =& $woocommerce_wpml;

        add_action( 'init', array( $this, 'init' ), 15 );
    }

    public function init(){
        if( is_admin() ){
            add_action( 'woocommerce_settings_save_general', array( $this, 'currency_options_update_default_currency'));
        }
    }

    /**
     * When the default WooCommerce currency is updated, if it existed as a secondary currency, remove it
     *
     */
    public function currency_options_update_default_currency(){

        $current_currency = get_option('woocommerce_currency');

        if( isset( $this->woocommerce_wpml->settings['currency_options'][ $current_currency ] )){
            unset( $this->woocommerce_wpml->settings['currency_options'][ $current_currency ] );
            $this->woocommerce_wpml->update_settings();
        }

    }


}