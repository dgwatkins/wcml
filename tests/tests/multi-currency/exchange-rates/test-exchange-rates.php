<?php


class Test_WCML_Exchange_Rates extends WCML_UnitTestCase {

    private $multi_currency;
    private $exchange_rate_services;

    function setUp(){

        parent::setUp();

        set_current_screen( 'dashboard' );

        $this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
        $this->multi_currency_helper->enable_multi_currency();
        $this->multi_currency_helper->setup_3_currencies();

        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency =& $this->woocommerce_wpml->multi_currency;

        $this->exchange_rate_services =& $this->multi_currency->exchange_rate_services;
        $this->exchange_rate_services->init();
    }

    /**
     * @test
     */
    public function test_initialize_settings(){

        // 1) WCML_Exchange_Rates::initialize_settings will set defaults when settings don't exist
        $defaults = array(
            'automatic'      => 0,
            'service'        => 'yahoo',
            'schedule'       => 'manual',
            'week_day'       => 1,
            'month_day'      => 1,
            'lifting_charge' => 0
        );
        unset( $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] ); //reset
        // test private method by calling the constructor again
        // will write to the the wcml global settings
        new WCML_Exchange_Rates( $this->woocommerce_wpml ); // calls WCML_Exchange_Rates::initialize_settings

        $this->assertEquals( $defaults, $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] );


        // 2) WCML_Exchange_Rates::initialize_settings will not set defaults when settings exist
        $custom = array(
            'automatic'      => 1,
            'service'        => 'currencylayer',
            'schedule'       => 'daily',
            'week_day'       => 5,
            'month_day'      => 15,
            'lifting_charge' => 0
        );
        $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] = $custom;
        new WCML_Exchange_Rates( $this->woocommerce_wpml ); // calls WCML_Exchange_Rates::initialize_settings

        $this->assertEquals( $custom, $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] );


    }


    /**
     * @test
     */
    public function test_get_services(){

        $expected_services = array('yahoo', 'fixierio', 'currencylayer');
        $actual_services   = array_keys( $this->exchange_rate_services->get_services() );

        $this->assertEquals( $expected_services, $actual_services );

    }

    /**
     * @test
     */
    public function test_get_settings(){

        $custom = array(
            'automatic'      => 1,
            'service'        => 'currencylayer',
            'schedule'       => 'daily',
            'week_day'       => 5,
            'month_day'      => 15,
            'lifting_charge' => 0
        );
        $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] = $custom;

        $this->assertEquals( $custom, $this->exchange_rate_services->get_settings() );

    }

    /**
     * @test
     */
    public function test_get_setting(){

        $custom = array(
            'automatic'      => 1,
            'service'        => 'currencylayer',
            'schedule'       => 'daily',
            'week_day'       => 5,
            'month_day'      => 15,
            'lifting_charge' => 0
        );
        $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] = $custom;

        $this->assertEquals( 1,         $this->exchange_rate_services->get_setting( 'automatic' ) );
        $this->assertEquals( 'daily',   $this->exchange_rate_services->get_setting( 'schedule' ) );

    }

    /**
     * @test
     */
    public function test_save_settings(){

        $random = rand(1, 100000);
        $custom = array(
            'automatic'      => 1,
            'service'        => 'currencylayer',
            'schedule'       => 'daily',
            'week_day'       => 5,
            'month_day'      => 15,
            'lifting_charge' => 0,
            '_random'        => $random
        );
        $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] = $custom;

        $this->exchange_rate_services->save_settings();

        $this->assertEquals( $custom,   $this->woocommerce_wpml->settings['multi_currency']['exchange_rates'] );
        $this->assertEquals( $random,   $this->exchange_rate_services->get_setting( '_random' ) );

    }

    /**
     * @test
     */
    public function test_save_setting(){

        $random = random_string();
        $this->exchange_rate_services->save_setting( '_random_key', $random );

        $this->assertEquals( $random, $this->woocommerce_wpml->settings['multi_currency']['exchange_rates']['_random_key'] );

    }

    /**
     * @test
     */
    public function test_update_exchange_rates_ajax(){

        /*

        // Invalid Nonce
        $_POST['wcml_nonce'] = '_invalid_';

        $this->exchange_rate_services->update_exchange_rates_ajax();
        $response = json_decode( $this->_json_response );

        $this->assertEquals( 0 ,                $response['success'] );
        $this->assertEquals( 'Invalid nonce' ,  $response['error'] );

        // Valid Nonce
        $_POST['wcml_nonce'] = wp_create_nonce( 'update-exchange-rates' );
        $this->exchange_rate_services->update_exchange_rates_ajax();
        $response = json_decode( $this->_json_response );

        print_r( $response );

        */
    }

    /**
     * @test
     */
    public function test_update_exchange_rates(){

        $exchange_rate_services = new WCML_Exchange_Rates( $this->woocommerce_wpml );

        $mocked_exchange_rate_service = $this->getMockBuilder( 'WCML_Exchange_Rates_YahooFinance' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_rates' ) )
            ->getMock();

        $exchange_rate_services->add_service( 'dummy_service', $mocked_exchange_rate_service );
        $exchange_rate_services->save_setting( 'service',  'dummy_service' );

        // Set random rates for the mocked get_rates method
        $currencies = $this->woocommerce_wpml->multi_currency->get_currency_codes();
        $default_currency = get_option( 'woocommerce_currency' );
        $secondary_currencies = array_diff( $currencies, array( $default_currency ) );
        foreach( $secondary_currencies as $currency ){
            $rates[ $currency ] = round( rand( 1, 1000 ) / 100 , 2);
        }

        $mocked_exchange_rate_service->method( 'get_rates' )->willReturn( $rates );

        // Update
        $exchange_rate_services->update_exchange_rates();

        foreach( $secondary_currencies as $currency ){
            $this->assertEquals( $rates[ $currency ],  $exchange_rate_services->get_currency_rate( $currency) );
        }

        // restore exchange rate service
        $this->exchange_rate_services->save_setting( 'service',  'yahoo' );
    }

    /**
     * @test
     */
    /*
    public function test_save_exchange_rate(){

        $currency = 'USD';
        $rate     = round( rand( 1, 1000 ) / 100 , 2);

        $this->exchange_rate_services->save_exchage_rate( 'USD', $rate );

        $this->assertEquals( $this->woocommerce_wpml->settings['currency_options'][$currency]['rate'],  $rate );

    }
    */

    /**
     * @test
     */
    public function test_get_currency_rate(){

        $currency = 'USD';

        $this->assertEquals( $this->woocommerce_wpml->settings['currency_options'][$currency]['rate'],
            $this->exchange_rate_services->get_currency_rate( $currency ) );

    }

    /**
     * @test
     */
    public function test_update_exchange_rate_options(){

        $this->exchange_rate_services->save_setting( 'service', 'yahoo' );
        $backup_settings = $this->exchange_rate_services->get_settings();

        $custom = array(
            'exchange-rates-automatic' => rand(1, 10000),
            'exchange-rates-service'   => 'currencylayer',
            'update-schedule'          => 'manual',
            'update-weekly-day'        => rand(1, 10000),
            'update-monthly-day'       => rand(1, 10000),
            'lifting_charge'           => rand(1, 10000),
            'services'                 => array(
                'currencylayer' => array(
                    'api-key' => random_string()
                )
            )
        );

        // keys_map
        $post_settings_map = array(
            'exchange-rates-automatic' => 'automatic',
            'exchange-rates-service'   => 'service',
            'update-schedule'          => 'schedule',
            'update-weekly-day'        => 'week_day',
            'update-monthly-day'       => 'month_day',
            'lifting_charge'           => 'lifting_charge'
        );

        $this->exchange_rate_services->update_exchange_rate_options( $custom );

        foreach( $custom as $key=> $value ){
            if( is_scalar( $value ) ){
                $this->assertEquals( $custom[$key],  $this->exchange_rate_services->get_setting( $post_settings_map[$key] ) );
            }
        }

        $services = $this->exchange_rate_services->get_services();
        $this->assertEquals( $custom['services']['currencylayer']['api-key'],
            $services['currencylayer']->get_setting('api-key') );


        // when the schedule changes from manual to daily, a cron is set
        $custom[ 'update-schedule' ] = 'daily';
        $this->exchange_rate_services->update_exchange_rate_options( $custom );

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertEquals( 'daily',  $schedule );


        // when the schedule changes from daily to weekly, a cron is set and the previous one deleted
        $custom[ 'update-schedule' ] = 'weekly';
        $custom[ 'update-weekly-day' ] = 4;

        $this->exchange_rate_services->update_exchange_rate_options( $custom );

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertEquals( 'wcml_weekly_on_4',  $schedule );


        // when the schedule changes from weekly to manual, the previous cron is deleted
        $custom[ 'update-schedule' ] = 'manual';

        $this->exchange_rate_services->update_exchange_rate_options( $custom );

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertFalse( $schedule );

        // restore settings
        foreach ( $backup_settings as $key => $val ) {
            $this->exchange_rate_services->save_setting( $key, $value );
        }

    }

    /**
     * @test
     */
    public function test_enable_update_cronjob(){

        // daily
        $this->exchange_rate_services->save_setting( 'schedule', 'daily' );
        $this->exchange_rate_services->enable_update_cronjob();

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertEquals( 'daily',  $schedule );

        // weekly
        $this->exchange_rate_services->save_setting( 'schedule', 'weekly' );
        $this->exchange_rate_services->save_setting( 'week_day', '2' );
        $this->exchange_rate_services->enable_update_cronjob();

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertEquals( 'wcml_weekly_on_2',  $schedule );

        // monthly
        $this->exchange_rate_services->save_setting( 'schedule', 'monthly' );
        $this->exchange_rate_services->save_setting( 'month_day', '12' );
        $this->exchange_rate_services->enable_update_cronjob();

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertEquals( 'wcml_monthly_on_12',  $schedule );

        // manual
        $this->exchange_rate_services->save_setting( 'schedule', 'manual' );
        $this->exchange_rate_services->enable_update_cronjob();

        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertFalse( $schedule );

    }

    /**
     * @test
     */
    public function test_delete_update_cronjob(){

        // enable daily
        $this->exchange_rate_services->save_setting( 'schedule', 'daily' );
        $this->exchange_rate_services->enable_update_cronjob();

        $this->exchange_rate_services->delete_update_cronjob();
        $schedule = wp_get_schedule( WCML_Exchange_Rates::cronjob_event );
        $this->assertFalse( $schedule );


    }

    /**
     * @test
     */
    public function test_cron_schedules(){

        // monthly
        $this->exchange_rate_services->save_setting( 'schedule', 'monthly' );
        $this->exchange_rate_services->save_setting( 'month_day', '12' );
        $this->exchange_rate_services->enable_update_cronjob();

        $schedules = wp_get_schedules();
        $this->assertArrayHasKey( 'wcml_monthly_on_12', $schedules );

        // weekly
        $this->exchange_rate_services->save_setting( 'schedule', 'weekly' );
        $this->exchange_rate_services->save_setting( 'week_day', '3' );
        $this->exchange_rate_services->enable_update_cronjob();

        $schedules = wp_get_schedules();
        $this->assertArrayHasKey( 'wcml_weekly_on_3', $schedules );


    }

    public function test_apply_lifting_charge(){


        $lifting_charge = 2.5;
        $rates = array(
            'RON' => 4.25
        );

        $expected_rates = array();
        foreach( $rates as $currency => $rate ){
            $expected_rates[ $currency ] = round( $rate * ($lifting_charge/100 + 1), 4);
        }

        $this->exchange_rate_services->save_setting( 'lifting_charge', $lifting_charge );

        $this->exchange_rate_services->apply_lifting_charge( $rates );

        foreach( $rates as $currency => $rate ){
            $this->assertEquals( $expected_rates[ $currency ], $rate );
        }



    }


}