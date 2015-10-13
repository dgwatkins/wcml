<?php

class WCML_gravityforms{

    function __construct(){
        add_filter('gform_formatted_money',array($this,'wcml_convert_price'),10,2);
        add_filter('wcml_multi_currency_is_ajax',array($this,'add_ajax_action'));

        add_filter('wcml_exception_duplicate_products_in_cart', array($this, 'addons_duplicate_exception'),10,2);
    }
    
    function wcml_convert_price($formatted, $unformatted){
        if ( ! is_admin() ) {
        	$currency = apply_filters('wcml_price_currency', get_woocommerce_currency());
        	$formatted = strip_tags(wc_price(apply_filters('wcml_raw_price_amount', $unformatted), array('currency'=>$currency)));
        }
        return $formatted;    
	}

	
	function add_ajax_action($actions){
		$actions[] = 'get_updated_price';
		return $actions;
	}

    function addons_duplicate_exception( $exclude, $cart_item ) {
        if ( isset( $cart_item[ '_gravity_form_data' ] ) ) {
            $exclude = true;
        }
        return $exclude;
    }
   
}
