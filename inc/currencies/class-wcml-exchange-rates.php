<?php

class WCML_Exchange_Rates{

    /**
     * @var woocommerce_wpml
     */
    private $woocommerce_wpml;

    /**
     * @var array
     */
    private $services = array();

    /**
     * @var array
     */
    private $settings;

    const cronjob_event = 'wcml_exchange_rates_update';

    function __construct( $woocommerce_wpml ) {

        $this->woocommerce_wpml =& $woocommerce_wpml;

        $this->initialize_settings();

        // Load built in services
        $this->services['currencylayer'] = new WCML_Exchange_Rates_Currencylayer();
        $this->services['fixierio']      = new WCML_Exchange_Rates_Fixierio();
        $this->services['yahoo']         = new WCML_Exchange_Rates_YahooFinance();

        if( is_admin() ){
            add_action( 'wp_ajax_wcml_update_exchange_rates', array( $this, 'update_exchange_rates_ajax') );

            add_action( 'wcml_saved_mc_options', array($this, 'update_exchange_rate_options' ) );
        }

        add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
        add_action( self::cronjob_event, array( $this, 'update_exchange_rates' ) );

    }

    private function initialize_settings(){

        if( !isset( $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] ) ){

            $this->settings = array(
                'mode'           => 'manual',
                'service'        => 'currencylayer',
                'schedule'       => 'manual',
                'week_day'       => 1,
                'month_day'      => 1
            );

            $this->update_settings();

        } else {
            $this->settings =& $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'];
        }

    }

    public function get_services(){
        return $this->services;
    }

    public function get_settings(){
        return $this->settings;
    }

    public function get_setting( $key ){
        return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
    }

    public function update_settings( $settings = null ){
        if( is_null( $settings ) ){
            $settings =& $this->settings;
        }
        $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] = $settings;
        $this->woocommerce_wpml->update_settings();
    }

    public function update_exchange_rates_ajax(){

        if( wp_create_nonce( 'update-exchange-rates' ) == $_POST['wcml_nonce'] ) {
            try {
                $this->update_exchange_rates();
            } catch ( Exception $e ) {
                wp_send_json( array('success' => 0, 'error' => $e->getMessage()) );
            }

            wp_send_json( array(
                'success' => 1,
                'last_updated' => date_i18n( 'F j, Y g:i a', $this->settings['last_updated'] )
                )
            );
        } else {
            wp_send_json( array('success' => 0, 'error' => 'Invalid nonce') );
        }
    }

    public function update_exchange_rates(){

        if( isset( $this->services[ $this->settings['service'] ]) ){
            $service =&  $this->services[ $this->settings['service'] ];

            $currencies = $this->woocommerce_wpml->multi_currency->get_currency_codes();
            $default_currency = get_option( 'woocommerce_currency' );
            $secondary_currencies = array_diff( $currencies, array( $default_currency ) );

            $rates = $service->get_rates( $default_currency,  $secondary_currencies );

            foreach( $rates as $to => $rate ){
                if( $rate && is_numeric( $rate ) ){
                    $this->save_exchage_rate( $to, $rate );
                }
            }
        }

        $this->settings['last_updated'] = current_time( 'timestamp' );
        $this->update_settings();

    }

    private function save_exchage_rate( $currency, $rate ){

        $this->woocommerce_wpml->settings['currency_options'][$currency]['previous_rate'] =
            $this->woocommerce_wpml->settings['currency_options'][$currency]['rate'];
        $this->woocommerce_wpml->settings['currency_options'][$currency]['rate'] = $rate;
        $this->woocommerce_wpml->update_settings();

    }

    public function update_exchange_rate_options( $post_data ){

        if( isset( $post_data['exchange-rates-mode'] ) ){
            $this->settings['mode'] = sanitize_text_field( $post_data['exchange-rates-mode'] );
        }

        if( isset( $post_data['exchange-rates-service'] ) ){
           $this->settings['service'] = sanitize_text_field( $post_data['exchange-rates-service'] );
        }

        if( isset( $post_data['services'] ) ){
            foreach( $post_data['services'] as $service_id => $service_data ){
                if( isset( $this->services[ $service_id ] ) ) {
                    $this->services[ $service_id ]->save_settings( $service_data );
                }
            }

        }

        if( isset( $post_data['update-schedule'] ) ){
            $this->settings['schedule'] = sanitize_text_field( $post_data['update-schedule'] );
        }

        if( isset( $post_data['update-time'] ) ){
            $this->settings['time'] = sanitize_text_field( $post_data['update-time'] );
        }

        if( isset( $post_data['update-weekly-day'] ) ){
            $this->settings['week_day'] = sanitize_text_field( $post_data['update-weekly-day'] );
        }

        if( isset( $post_data['update-monthly-day'] ) ){
            $this->settings['month_day'] = sanitize_text_field( $post_data['update-monthly-day'] );
        }

        if( $this->settings['schedule'] === 'manual' || $this->settings['mode'] === 'manual' ){
            $this->delete_update_cronjob();
        }else{
            $this->enable_update_cronjob();
        }

        $this->update_settings();


    }

    private function enable_update_cronjob(){

        $schedule = wp_get_schedule( self::cronjob_event );

        if( $schedule != $this->settings['schedule'] ){
            $this->delete_update_cronjob();
        }


        if( $this->settings['schedule'] == 'monthly' ){
            $current_day = date('j');
            if( $this->settings['month_day'] >= $current_day ){
                $days = $this->settings['month_day'] - $current_day;
            }else{
                $days = cal_days_in_month( CAL_GREGORIAN, date('n'), date('Y') ) - $current_day + $this->settings['month_day'];
            }

            $time_offset = time() + $days * 86400;
            $schedule = 'wcml_' . $this->settings['schedule'] . '_on_' . $this->settings['month_day'];


        }elseif( $this->settings['schedule'] == 'weekly' ){
            $current_day = date('w');
            if( $this->settings['week_day'] >= $current_day ){
                $days = $this->settings['week_day'] - $current_day;
            }else{
                $days = 7 - $current_day + $this->settings['month_day'];
            }

            $time_offset = time() + $days * 86400;
            $schedule = 'wcml_' . $this->settings['schedule'] . '_on_' . $this->settings['week_day'];

        }else{
            $time_offset = time();
            $schedule = $this->settings['schedule'];
        }


        if( !wp_next_scheduled ( self::cronjob_event ) ){
            wp_schedule_event( $time_offset, $schedule, self::cronjob_event );
        }

    }

    private function delete_update_cronjob(){

        wp_clear_scheduled_hook( self::cronjob_event );

    }

    public function cron_schedules( $schedules ) {

        if( $this->settings['schedule'] == 'monthly' ){

            $month_day = $this->settings['month_day'];
            switch( $month_day ){
                case 1:  $month_day .= 'st'; break;
                case 2:  $month_day .= 'nd'; break;
                case 3:  $month_day .= 'rd'; break;
                default: $month_day .= 'th'; break;
            }
            $schedules['wcml_monthly_on_' . $this->settings['month_day']] = array(
                'interval' => 2635200,
                'display'  => sprintf( __( 'Monthly on the %s', 'woocommerce-multilingual' ), $month_day ),
            );

        } elseif( $this->settings['schedule'] == 'weekly' ){

            global $wp_locale;
            $week_day = $wp_locale->get_weekday( $this->settings['week_day'] );
            $schedules['wcml_weekly_on_' . $this->settings['week_day']] = array(
                'interval' => 604800,
                'display'  => sprintf( __( 'Weekly on %s', 'woocommerce-multilingual' ), $week_day ),
            );

        }

        return $schedules;
    }

}