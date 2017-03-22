<?php

class WCML_Klarna_Gateway{

    function __construct(){

        add_filter( 'wcml_multi_currency_ajax_actions', array( $this, 'add_ajax_action' ) );
    }

    function add_ajax_action( $actions ){

        $actions[] = 'klarna_checkout_cart_callback_update';

        return $actions;
    }

}
