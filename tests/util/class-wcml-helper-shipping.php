<?php

class WCML_Helper_Shipping {

    /**
     * Create some mock shipping zones to test against
     */
    public static function create_mock_zones() {
        self::remove_mock_zones();

        // Local zone
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name( 'Local' );
        $zone->set_zone_order( 1 );
        $zone->add_location( 'GB', 'country' );
        $zone->add_location( 'CB*', 'postcode' );
        $zone->save();

        // Europe zone
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name( 'Europe' );
        $zone->set_zone_order( 2 );
        $zone->add_location( 'EU', 'continent' );
        $zone->save();

        // US california zone
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name( 'California' );
        $zone->set_zone_order( 3 );
        $zone->add_location( 'US:CA', 'state' );
        $zone->save();

        // US zone
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name( 'US' );
        $zone->set_zone_order( 4 );
        $zone->add_location( 'US', 'country' );
        $zone->save();
    }

    /**
     * Remove all zones
     */
    public static function remove_mock_zones() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods;" );
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_locations;" );
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zones;" );
        WC_Cache_Helper::invalidate_cache_group( 'shipping_zones' );
    }

    /**
     * Create a simple flat rate at the cost of 10.
     *
     */
    public static function create_simple_flat_rate( $args = array() ) {
        $flat_rate_settings = array(
            'enabled'      => 'yes',
            'title'        => 'Flat Rate',
            'availability' => 'all',
            'countries'    => '',
            'tax_status'   => 'taxable',
            'cost'         => isset( $args['cost'] ) ? $args['cost'] : 10
        );

        update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
        update_option( 'woocommerce_flat_rate', array() );
        WC_Cache_Helper::get_transient_version( 'shipping', true );

        WC()->shipping->unregister_shipping_methods();
    }

    /**
     * Delete the simple flat rate.
     *
     */
    public static function delete_simple_flat_rate() {
        delete_option( 'woocommerce_flat_rate_settings' );
        delete_option( 'woocommerce_flat_rate' );
        WC_Cache_Helper::get_transient_version( 'shipping', true );
        WC()->shipping->unregister_shipping_methods();
    }

    /**
     * Adds free shipping
     *
     */
    public static function add_free_shipping( $args = array() ) {

        $zone        = WC_Shipping_Zones::get_zone( 2 );
        $instance_id = $zone->add_shipping_method( 'free_shipping' );

        $free_shipping_settings['title'] = 'Free Shipping One';
        if( isset( $args['min_amount'] ) ){
            $free_shipping_settings['requires'] = 'min_amount';
            $free_shipping_settings['min_amount'] = $args['min_amount'];
        }

        update_option( 'woocommerce_free_shipping_' . $instance_id . '_settings', $free_shipping_settings );

        return $instance_id;

    }

    /**
     * Adds flat rate shipping
     *
     */
    public static function add_flat_rate_shipping( $args = array() ) {

        $zone        = WC_Shipping_Zones::get_zone( 2 );
        $instance_id = $zone->add_shipping_method( 'flat_rate' );

        $settings['title'] = 'Flat Rate One';
        $settings['cost'] = isset( $args['cost'] ) ? $args['cost'] : 10;

        update_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', $settings );

        return $instance_id;

    }

    /**
     * Adds local pickup
     *
     */
    public static function add_local_pickup_shipping( $args = array() ) {

        $zone        = WC_Shipping_Zones::get_zone( 2 );
        $instance_id = $zone->add_shipping_method( 'local_picku' );

        $settings['title'] = 'Local Pickup One';
        $settings['cost'] = isset( $args['cost'] ) ? $args['cost'] : 3;

        update_option( 'woocommerce_local_pickup_' . $instance_id . '_settings', $settings );

        return $instance_id;

    }


}