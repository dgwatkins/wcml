<?php

class WCML_Multi_Currency_Shipping{

    /**
     * @var WCML_Multi_Currency
     */
    private $multi_currency;

    public function __construct( &$multi_currency ) {
        global $wpdb;

        $this->multi_currency =& $multi_currency;

        add_filter('woocommerce_cart_shipping_method_full_label', array( $this, 'convert_shipping_cost'), 10, 2 );

        // shipping method cost settings
        $rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id IN('flat_rate', 'local_pickup', 'free_shipping')" );
        foreach( $rates as $method ){
            $option_name = sprintf('woocommerce_%s_%d_settings', $method->method_id, $method->instance_id );
            add_filter('option_' . $option_name, array($this, 'convert_shipping_method_cost_settings'));
        }

        // Used for table rate shipping compatibility class
        add_filter( 'wcml_shipping_price_amount', array( $this, 'shipping_price_filter' ) ); // WCML filters
        add_filter( 'wcml_shipping_free_min_amount', array( $this, 'shipping_free_min_amount') ); // WCML filters

        add_filter( 'woocommerce_evaluate_shipping_cost_args', array( $this, 'woocommerce_evaluate_shipping_cost_args') );

        add_action( 'woocommerce_calculate_totals', array( $this, 'convert_shipping_totals' ) );

        add_filter( 'woocommerce_shipping_packages', array( $this, 'convert_shipping_taxes'), 10 );


        // Before WooCommerce 2.6
        add_filter('option_woocommerce_free_shipping_settings', array( $this, 'adjust_min_amount_required' ) );


    }

    /**
     * @param $label string
     * @param $method object
     * @return string
     *
     */
    public function convert_shipping_cost( $label, $method ){

        if( empty( $method->_costs_converted ) ){

            $client_currency = $this->multi_currency->get_client_currency();
            $label = $method->get_label();

            if ( $method->cost > 0 ) {
                $method->cost = $this->multi_currency->prices->raw_price_filter( $method->cost, $client_currency);
                if ( WC()->cart->tax_display_cart == 'excl' ) {
                    $label .= ': ' . wc_price( $method->cost );
                    if ( $method->get_shipping_tax() > 0 && WC()->cart->prices_include_tax ) {
                        $label .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
                    }
                } else {
                    $label .= ': ' . wc_price( $method->cost + $method->get_shipping_tax() );
                    if ( $method->get_shipping_tax() > 0 && ! WC()->cart->prices_include_tax ) {
                        $label .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
                    }
                }
            }

            $method->_costs_converted = true;

        }

        return $label;
    }

    public function convert_shipping_method_cost_settings( $settings ){

        $has_free_shipping_coupon = false;
        if ( $coupons = WC()->cart->get_coupons() ) {
            foreach ( $coupons as $code => $coupon ) {
                if ( $coupon->is_valid() && $coupon->enable_free_shipping() ) {
                    $has_free_shipping_coupon = true;
                }
            }
        }

        if( !empty( $settings['requires'] ) ){

            if(
                $settings['requires'] == 'min_amount' ||
                $settings['requires'] == 'either' ||
                $settings['requires'] == 'both' && $has_free_shipping_coupon
            ){
                $settings['min_amount'] = apply_filters( 'wcml_shipping_free_min_amount', $settings['min_amount'] );
            }
        }

        return $settings;
    }

    /**
     * @param $args
     * @param $sum
     * @param $method
     * @return array
     *
     * When using [cost] in the shipping class costs, we need to use the not-converted cart total
     * It will be converted as part of the total cost
     *
     */
    public function woocommerce_evaluate_shipping_cost_args( $args ){

        $args['cost'] = $this->multi_currency->prices->unconvert_price_amount( $args['cost'] );

        return $args;
    }

    /**
     * @param $cart WC_Cart (reference)
     *
     * converts the cart total and the cart tax total in the WC_Cart object on the checkout page
     */
    public function convert_shipping_totals( $cart ){
        $cart->shipping_total     = $this->multi_currency->prices->raw_price_filter( $cart->shipping_total );
    }

    public function convert_shipping_taxes( $packages ){

        foreach( $packages as $package_id => $package ){
            foreach( $package['rates'] as $rate_id => $rate  ){
                foreach( $rate->taxes as $tax_id => $tax){

                    $packages[$package_id]['rates'][$rate_id]->taxes[$tax_id] =
                        $this->multi_currency->prices->raw_price_filter( $tax );

                }
            }
        }

        return $packages;
    }

    public function shipping_price_filter($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    public function shipping_free_min_amount($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    // Before WooCommerce 2.6
    public function adjust_min_amount_required($options){

        if( !empty( $options['min_amount'] ) ){
            $options['min_amount'] = apply_filters( 'wcml_shipping_free_min_amount', $options['min_amount'] );
        }

        return $options;
    }

}