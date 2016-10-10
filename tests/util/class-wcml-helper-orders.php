<?php

class WCML_Helper_Orders {

    /**
     * Create a dummy order.
     *
     * @return WC_Order
     */
    public static function create_order( $args = array() ) {

    	$default_args = array(
    		'_qty'              => 1,
            '_line_subtotal'    => 10,
		    'wpml_language'     => 'en'
	    );

    	$args = array_merge( $default_args, $args );

        if( !isset( $args[ 'product_id' ] ) ){
            $args[ 'product_id' ] = wpml_test_insert_post( $args[ 'wpml_language' ], 'product', false, rand_str(), 0 );
        }

        $order_data = array(
            'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
            'customer_id'   => get_current_user_id(),
            'customer_note' => '',
            'cart_hash'     => md5( json_encode( 'test order' ) ),
            'created_via'   => 'admin'
        );

        $order = wc_create_order( $order_data );
        $order_id = self::get_order_id( $order );

        $item_id = wc_add_order_item( $order_id, array(
            'order_item_name' 		=> 'product 1',
            'order_item_type' 		=> 'line_item'
        ) );

        wc_add_order_item_meta( $item_id, '_qty', $args[ '_qty' ] );
        wc_add_order_item_meta( $item_id, '_product_id', $args[ 'product_id' ] );
        wc_add_order_item_meta( $item_id, '_line_subtotal', $args[ '_line_subtotal' ] );
        wc_add_order_item_meta( $item_id, 'wpml_language', $args[ 'wpml_language' ] );

        return $order;
    }

    public static function get_order_id( $order ){
        return method_exists( 'WC_Order', 'get_id' ) ? $order->get_id() : $order->id;
    }

}