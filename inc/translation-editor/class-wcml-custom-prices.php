<?php

class WCML_Custom_Prices{

    private $woocommerce_wpml;
    private $sitepress;
    private $wpdb;

    public function __construct( &$woocommerce_wpml, &$sitepress, &$wpdb ) {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;
        $this->wpdb = $wpdb;
    }

    public function save_custom_prices( $post_id ){
        $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if( isset( $_POST[ '_wcml_custom_prices' ] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices' ) ){
            if( isset( $_POST[ '_wcml_custom_prices' ][ $post_id ] ) ) {
                $wcml_custom_prices_option = $_POST[ '_wcml_custom_prices' ][ $post_id ];
            }else{
                $current_option = get_post_meta( $post_id, '_wcml_custom_prices_status', true );
                $wcml_custom_prices_option = $current_option ? $current_option : 0;
            }
            update_post_meta( $post_id, '_wcml_custom_prices_status', $wcml_custom_prices_option );

            if( $wcml_custom_prices_option == 1){
                $currencies = $this->woocommerce_wpml->multi_currency->get_currencies();
                foreach( $currencies as $code => $currency ){
                    $sale_price = wc_format_decimal( $_POST[ '_custom_sale_price' ][ $code ] );
                    $regular_price = wc_format_decimal( $_POST[ '_custom_regular_price' ][ $code ] );
                    $date_from = isset( $_POST[ '_custom_sale_price_dates_from' ][ $code ] ) ? strtotime( $_POST[ '_custom_sale_price_dates_from' ][ $code ] ) : '';
                    $date_to = isset( $_POST[ '_custom_sale_price_dates_to' ][ $code ] ) ? strtotime( $_POST[ '_custom_sale_price_dates_to' ][ $code ] ) : '';
                    $schedule = $_POST[ '_wcml_schedule' ][ $code ];
                    $custom_prices = apply_filters( 'wcml_update_custom_prices_values',
                        array( '_regular_price' => $regular_price,
                            '_sale_price' => $sale_price,
                            '_wcml_schedule_' => $schedule,
                            '_sale_price_dates_from' => $date_from,
                            '_sale_price_dates_to' => $date_to ),
                        $code
                    );
                    $this->update_custom_prices( $post_id, $custom_prices , $code );
                }
            }
        }
    }

    public function update_custom_prices( $post_id, $custom_prices, $code ){
        $price = '';
        foreach( $custom_prices as $custom_price_key => $custom_price_value ){
            update_post_meta( $post_id, $custom_price_key.'_'.$code, $custom_price_value );
        }
        if ( $custom_prices[ '_sale_price_dates_to' ]  && ! $custom_prices[ '_sale_price_dates_from' ] ) {
            update_post_meta($post_id, '_sale_price_dates_from_' . $code, strtotime( 'NOW', current_time( 'timestamp' ) ) );
        }
        // Update price if on sale
        if ( $custom_prices[ '_sale_price' ] != '' && $custom_prices[ '_sale_price_dates_to' ] == '' && $custom_prices[ '_sale_price_dates_from' ] == '' ){
            $price = stripslashes( $custom_prices[ '_sale_price' ] );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_sale_price' ] ) );
        }else{
            $price = stripslashes( $custom_prices[ '_regular_price' ] );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_regular_price' ] ) );
        }

        if ( $custom_prices[ '_sale_price' ] != '' && $custom_prices[ '_sale_price_dates_from' ] < strtotime( 'NOW', current_time( 'timestamp' ) ) ){
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_sale_price' ] ) );
            $price = stripslashes( $custom_prices[ '_sale_price' ] );
        }

        if ( $custom_prices[ '_sale_price_dates_to' ] && $custom_prices[ '_sale_price_dates_to' ] < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $custom_prices[ '_regular_price' ] ) );
            $price = stripslashes( $custom_prices[ '_regular_price' ] );
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, '' );
            update_post_meta( $post_id, '_sale_price_dates_to_'.$code, '' );
        }

        return $price;
    }

    public function sync_product_variations_custom_prices( $product_id ){
        $is_variable_product = $this->woocommerce_wpml->products->is_variable_product( $product_id );
        if( $is_variable_product ){
            $get_all_post_variations = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->posts}
                                                WHERE post_status IN ('publish','private')
                                                  AND post_type = 'product_variation'
                                                  AND post_parent = %d
                                                ORDER BY ID"
                    ,$product_id )
            );
            $duplicated_post_variation_ids = array();
            $min_max_prices = array();

            foreach( $get_all_post_variations as $k => $post_data ){
                $duplicated_post_variation_ids[] = $post_data->ID;
                //save files option
                $this->woocommerce_wpml->downloadable->save_files_option( $post_data->ID );

                if( !isset( $_POST[ '_wcml_custom_prices' ][ $post_data->ID ] ) ){
                    continue; // save changes for individual variation
                }
                //save custom prices for variation
                $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_variation_' . $post_data->ID . '_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                if( isset( $_POST['_wcml_custom_prices'][$post_data->ID]) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices_variation_' . $post_data->ID ) ){
                    update_post_meta( $post_data->ID, '_wcml_custom_prices_status', $_POST[ '_wcml_custom_prices' ][ $post_data->ID ] );
                    $currencies = $this->woocommerce_wpml->multi_currency->get_currencies();

                    if( $_POST[ '_wcml_custom_prices' ][ $post_data->ID ] == 1 ){
                        foreach( $currencies as $code => $currency ){
                            $sale_price = $_POST[ '_custom_variation_sale_price' ][ $code ][ $post_data->ID ];
                            $regular_price = $_POST[ '_custom_variation_regular_price' ][ $code ][ $post_data->ID ];
                            $date_from = strtotime( $_POST[ '_custom_sale_price_dates_from' ][ $code ][ $post_data->ID ] );
                            $date_to = strtotime( $_POST[ '_custom_sale_price_dates_to' ][ $code ][ $post_data->ID ] );
                            $schedule = $_POST[ '_wcml_schedule' ][ $code ][ $post_data->ID ];
                            $custom_prices = apply_filters( 'wcml_update_custom_prices_values',
                                array( '_regular_price' => $regular_price,
                                    '_sale_price' => $sale_price,
                                    '_wcml_schedule_' => $schedule,
                                    '_sale_price_dates_from' => $date_from,
                                    '_sale_price_dates_to' => $date_to ),
                                $code,
                                $post_data->ID
                            );
                            $price = $this->update_custom_prices( $post_data->ID, $custom_prices, $code );

                            if( !isset( $min_max_prices[ '_min_variation_price_'.$code ] ) || ( $price && $price < $min_max_prices[ '_min_variation_price_'.$code ] ) ){
                                $min_max_prices[ '_min_variation_price_'.$code ] = $price;
                                $min_max_prices[ '_min_price_variation_id_'.$code ] = $post_data->ID;
                            }

                            if( !isset( $min_max_prices[ '_max_variation_price_'.$code ] ) || ( $price && $price > $min_max_prices[ '_max_variation_price_'.$code ] ) ){
                                $min_max_prices[ '_max_variation_price_'.$code ] = $price;
                                $min_max_prices[ '_max_price_variation_id_'.$code ] = $post_data->ID;
                            }

                            if( !isset( $min_max_prices[ '_min_variation_regular_price_'.$code ] ) || ( $regular_price && $regular_price < $min_max_prices[ '_min_variation_regular_price_'.$code ] ) ){
                                $min_max_prices[ '_min_variation_regular_price_'.$code ] = $regular_price;
                            }

                            if( !isset( $min_max_prices[ '_max_variation_regular_price_'.$code ] ) || ( $regular_price && $regular_price > $min_max_prices[ '_max_variation_regular_price_'.$code ] ) ){
                                $min_max_prices[ '_max_variation_regular_price_'.$code ] = $regular_price;
                            }

                            if( !isset( $min_max_prices[ '_min_variation_sale_price_'.$code ] ) || ( $sale_price && $sale_price < $min_max_prices[ '_min_variation_sale_price_'.$code ] ) ){
                                $min_max_prices[ '_min_variation_sale_price_'.$code ] = $sale_price;
                            }

                            if( !isset( $min_max_prices[ '_max_variation_sale_price_'.$code ] ) || ( $sale_price && $sale_price > $min_max_prices[ '_max_variation_sale_price_'.$code ] ) ){
                                $min_max_prices[ '_max_variation_sale_price_'.$code ] = $sale_price;
                            }
                        }
                    }
                }
            }
        }
    }

}