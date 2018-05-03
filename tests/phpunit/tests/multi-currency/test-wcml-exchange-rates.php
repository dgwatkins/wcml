<?php

/**
 * Class Test_WCML_Exchange_Rates
 */
class Test_WCML_Exchange_Rates extends OTGS_TestCase {

	private function get_subject( $woocommerce_wpml = null, $wp_locale = null ) {

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		if ( null === $wp_locale ) {
			$wp_locale = $this->get_wp_locale_mock();
		}

		return new WCML_Exchange_Rates( $woocommerce_wpml, $wp_locale );
	}

	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_wp_locale_mock() {
		return $this->getMockBuilder( 'WP_Locale' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_weekday' ] )
		            ->getMock();
	}

	private function get_wcml_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_currencies', 'get_currency_codes' ] )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function add_actions_admin() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', array(
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::expectActionAdded( 'wcml_saved_mc_options', array( $subject, 'update_exchange_rate_options' ) );
		\WP_Mock::expectActionAdded( 'init', array( $subject, 'init' ) );

		$subject->add_actions();
	}

	/**
	 * @test
	 */
	public function add_actions_NOT_admin() {
		$subject = $this->get_subject();

		\WP_Mock::wpFunction( 'is_admin', array(
			'times'  => 1,
			'return' => false
		) );

		$this->expectActionAdded( 'wcml_saved_mc_options', array(
			$subject,
			'update_exchange_rate_options'
		), 10, 1, 0 );
		\WP_Mock::expectActionAdded( 'init', array( $subject, 'init' ) );

		$subject->add_actions();
	}

	/**
	 * @test
	 */
	public function init_without_currencies() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->once() )
		                                 ->method( 'get_currencies' )
		                                 ->willReturn( [] );

		$this->expectActionAdded( 'wp_ajax_wcml_update_exchange_rates', array(
			$subject,
			'update_exchange_rates_ajax'
		), 10, 1, 0 );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function init_with_currencies_not_admin() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->once() )
		                                 ->method( 'get_currencies' )
		                                 ->willReturn( [ 'EUR' ] );

		\WP_Mock::wpFunction( 'is_admin', array(
			'times'  => 1,
			'return' => false
		) );

		$this->expectActionAdded( 'wp_ajax_wcml_update_exchange_rates', array(
			$subject,
			'update_exchange_rates_ajax'
		), 10, 1, 0 );
		\WP_Mock::expectFilterAdded( 'cron_schedules', array( $subject, 'cron_schedules' ) );
		\WP_Mock::expectActionAdded( WCML_Exchange_Rates::CRONJOB_EVENT, array( $subject, 'update_exchange_rates' ) );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function init_with_currencies_on_admin() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->once() )
		                                 ->method( 'get_currencies' )
		                                 ->willReturn( [ 'EUR' ] );

		\WP_Mock::wpFunction( 'is_admin', array(
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::expectActionAdded( 'wp_ajax_wcml_update_exchange_rates', array(
			$subject,
			'update_exchange_rates_ajax'
		) );
		\WP_Mock::expectFilterAdded( 'cron_schedules', array( $subject, 'cron_schedules' ) );
		\WP_Mock::expectActionAdded( WCML_Exchange_Rates::CRONJOB_EVENT, array( $subject, 'update_exchange_rates' ) );

		$subject->init();
	}

	/**
	 * @test
	 */
	public function initialize_settings_empty_exchange_rates() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$expected_settings = array(
			'automatic'      => 0,
			'service'        => 'fixerio',
			'lifting_charge' => 0,
			'schedule'       => 'manual',
			'week_day'       => 1,
			'month_day'      => 1
		);

		$subject->initialize_settings();

		$actual_settings = $subject->get_settings();

		$this->assertSame( $expected_settings, $actual_settings );
	}

	/**
	 * @test
	 */
	public function initialize_settings_existing_exchange_rates() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$test_key = rand_str();
		$test_val = rand_str();
		$subject->save_setting( $test_key, $test_val );

		$actual_settings = $subject->get_settings();

		$subject->initialize_settings();

		$this->assertSame( [ $test_key => $test_val ], $actual_settings );
	}

	/**
	 * @test
	 */
	public function add_services() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$service_id = rand_str();
		$service    = $this->getMockBuilder( 'WCML_Exhange_Rate_Service' )
		                   ->disableOriginalConstructor()
		                   ->getMock();
		$subject->add_service( $service_id, $service );

		$this->assertSame( [ $service_id => $service ], $subject->get_services() );
	}

	/**
	 * @test
	 */
	public function get_settings() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$test_key = rand_str();
		$test_val = rand_str();
		$subject->save_setting( $test_key, $test_val );

		$actual_settings = $subject->get_settings();

		$this->assertSame( [ $test_key => $test_val ], $actual_settings );
	}

	/**
	 * @test
	 */
	public function get_setting() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$test_key = rand_str();
		$test_val = rand_str();
		$subject->save_setting( $test_key, $test_val );

		$this->assertSame( $test_val, $subject->get_setting( $test_key ) );
	}

	/**
	 * @test
	 */
	public function save_setting() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$test_key = rand_str();
		$test_val = rand_str();
		$subject->save_setting( $test_key, $test_val );

		$actual_settings = $subject->get_settings();

		$this->assertSame( [ $test_key => $test_val ], $actual_settings );
	}

	/**
	 * @test
	 */
	public function update_exchange_rates_ajax_invalid_nonce() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$_POST['wcml_nonce'] = rand_str();

		\WP_Mock::wpFunction( 'wp_create_nonce', array(
			'times'  => 1,
			'args'   => [ 'update-exchange-rates' ],
			'return' => $_POST['wcml_nonce'] . rand_str()
		) );

		\WP_Mock::wpFunction( 'wp_send_json', array(
			'times' => 1,
			'args'  => [ [ 'success' => 0, 'error' => 'Invalid nonce' ] ]
		) );

		$subject->update_exchange_rates_ajax();

	}

	/**
	 * @test
	 */
	public function update_exchange_rates_ajax_valid_nonce() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );


		$_POST['wcml_nonce'] = rand_str();

		$cur_1 = rand_str();
		$cur_2 = rand_str();

		\WP_Mock::wpFunction( 'current_time', array(
			'times'  => 1,
			'return' => time()
		) );

		$last_updated = time() - random_int( 10000, 90000 );
		$subject->save_setting( 'last_updated', $last_updated );
		\WP_Mock::wpFunction( 'date_i18n', array(
			'times'  => 1,
			'return' => date( 'F j, Y g:i a', $last_updated )
		) );

		\WP_Mock::wpFunction( 'wp_create_nonce', array(
			'times'  => 1,
			'args'   => [ 'update-exchange-rates' ],
			'return' => $_POST['wcml_nonce']
		) );

		$rates      = [
			$cur_1 => random_int( 1, 1000 ),
			$cur_2 => random_int( 1, 1000 )
		];
		$service_id = 'fixerio';
		$service    = $this->getMockBuilder( 'WCML_Exchange_Rates_Fixerio' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( [ 'get_rates' ] )
		                   ->getMock();
		$service->method( 'get_rates' )
		        ->willReturn( $rates );

		$subject->add_service( $service_id, $service );
		$subject->save_setting( 'service', $service_id );

		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->once() )
		                                 ->method( 'get_currency_codes' )
		                                 ->willReturn( [ $cur_1, $cur_2 ] );

		$subject->save_setting( 'lifting_charge', 0 );

		\WP_Mock::wpFunction( 'get_option', array(
			'times'  => 1,
			'args'   => [ 'woocommerce_currency' ],
			'return' => $cur_1
		) );

		$woocommerce_wpml->settings['currency_options'][ $cur_1 ]['rate'] = random_int( 1, 100 );
		$woocommerce_wpml->settings['currency_options'][ $cur_2 ]['rate'] = random_int( 1, 100 );

		\WP_Mock::wpFunction( 'wp_send_json', array(
			'times' => 1,
			'args'  => [
				[
					'success'      => 1,
					'last_updated' => date( 'F j, Y g:i a', $last_updated ),
					'rates'        => $rates
				]
			]
		) );

		$subject->update_exchange_rates_ajax();

	}

	/**
	 * @test
	 * @expectedException
	 */
	public function update_exchange_rates_ajax_valid_nonce_service_error() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$_POST['wcml_nonce'] = rand_str();
		\WP_Mock::wpFunction( 'wp_create_nonce', array(
			'times'  => 1,
			'args'   => [ 'update-exchange-rates' ],
			'return' => $_POST['wcml_nonce']
		) );

		$service_id = 'fixerio';
		$service    = $this->getMockBuilder( 'WCML_Exchange_Rates_Fixerio' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( [ 'get_rates' ] )
		                   ->getMock();
		$service->method( 'get_rates' )
		        ->will( $this->throwException( new Exception ) );

		$subject->add_service( $service_id, $service );
		$subject->save_setting( 'service', $service_id );

		$woocommerce_wpml->multi_currency = $this->get_wcml_multi_currency_mock();
		$woocommerce_wpml->multi_currency->expects( $this->once() )
		                                 ->method( 'get_currency_codes' )
		                                 ->willReturn( [] );


		$subject->update_exchange_rates_ajax();

	}

	/**
	 * @test
	 */
	public function apply_lifting_charge() {
		$subject = $this->get_subject();

		$lifting_charge = random_int( 1, 1000 );
		$subject->save_setting( 'lifting_charge', $lifting_charge );

		$cur_1 = rand_str();
		$cur_2 = rand_str();

		$rates = [
			$cur_1 => random_int( 1, 1000 ),
			$cur_2 => random_int( 1, 1000 ),
		];

		$expected_rates = [
			$cur_1 => round( $rates[ $cur_1 ] * ( 1 + $lifting_charge / 100 ), 4 ),
			$cur_2 => round( $rates[ $cur_2 ] * ( 1 + $lifting_charge / 100 ), 4 ),
		];

		$subject->apply_lifting_charge( $rates );

		$this->assertSame( $expected_rates, $rates );

	}

	/**
	 * @test
	 */
	public function get_currency_rate() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$currency = rand_str();
		$rate     = rand_str();

		$woocommerce_wpml->settings['currency_options'][ $currency ]['rate'] = $rate;

		$this->assertSame( $rate, $subject->get_currency_rate( $currency ) );
	}

	/**
	 * @test
	 */
	public function update_exchange_rate_options_it_deletes_cron() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$post_data = [
			'exchange-rates-automatic' => 0
		];

		\WP_Mock::wpFunction( 'wp_clear_scheduled_hook', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		$subject->save_setting( 'automatic', 1 );
		$subject->update_exchange_rate_options( $post_data );
		$settings = $subject->get_settings();

		$this->assertSame( 0, $settings['automatic'] );
	}

	/**
	 * @test
	 */
	public function update_exchange_rate_options_it_sets_manual_update() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$post_data = [
			'update-schedule' => 'manual'
		];

		\WP_Mock::wpFunction( 'wp_clear_scheduled_hook', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		$subject->save_setting( 'automatic', 1 );
		$subject->update_exchange_rate_options( $post_data );
		$settings = $subject->get_settings();

		$this->assertSame( 0, $settings['automatic'] );
	}

	/**
	 * @test
	 */
	public function update_exchange_rate_options_it_enables_monthly_cron() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$preexisting_service = rand_str();
		$service             = $this->getMockBuilder( 'WCML_Exchange_Rates_Fixerio' )
		                            ->disableOriginalConstructor()
		                            ->getMock();
		$subject->add_service( $preexisting_service, $service );
		$subject->save_setting( 'service', $preexisting_service );

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );

		$post_data = [
			'exchange-rates-automatic' => 1,
			'update-schedule'          => 'monthly',
			'exchange-rates-service'   => rand_str(),
			'lifting_charge'           => rand_str(),
			'update-time'              => rand_str(),
			'update-weekly-day'        => random_int(1,7),
			'update-monthly-day'       => random_int(1,30)
		];

		\WP_Mock::wpFunction( 'wp_get_schedule', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		\WP_Mock::wpFunction( 'wp_next_scheduled', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		\WP_Mock::wpFunction( 'wp_schedule_event', [
			'times' => 1,
			'args'  => [
				\Mockery::type( 'int' ),
				'wcml_monthly_on_' . $post_data['update-monthly-day'],
				$subject::CRONJOB_EVENT
			]
		] );

		$subject->update_exchange_rate_options( $post_data );

		$settings = $subject->get_settings();

		$this->assertSame( 1, $settings['automatic'] );
	}

	/**
	 * @test
	 */
	public function update_exchange_rate_options_it_enables_weekly_cron() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$preexisting_service = rand_str();
		$service             = $this->getMockBuilder( 'WCML_Exchange_Rates_Fixerio' )
		                            ->disableOriginalConstructor()
		                            ->getMock();
		$subject->add_service( $preexisting_service, $service );
		$subject->save_setting( 'service', $preexisting_service );

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );

		$post_data = [
			'exchange-rates-automatic' => 1,
			'update-schedule'          => 'weekly',
			'exchange-rates-service'   => rand_str(),
			'lifting_charge'           => rand_str(),
			'update-time'              => rand_str(),
			'update-weekly-day'        => random_int(1,7),
			'update-monthly-day'       => random_int(1,30)
		];

		\WP_Mock::wpFunction( 'wp_get_schedule', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		\WP_Mock::wpFunction( 'wp_next_scheduled', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		\WP_Mock::wpFunction( 'wp_schedule_event', [
			'times' => 1,
			'args'  => [
				\Mockery::type( 'int' ),
				'wcml_weekly_on_' . $post_data['update-weekly-day'],
				$subject::CRONJOB_EVENT
			]
		] );

		$subject->update_exchange_rate_options( $post_data );

		$settings = $subject->get_settings();

		$this->assertSame( 1, $settings['automatic'] );
	}

	/**
	 * @test
	 */
	public function update_exchange_rate_options_it_enables_daily_cron() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		$preexisting_service = rand_str();
		$service             = $this->getMockBuilder( 'WCML_Exchange_Rates_Fixerio' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'save_setting' ] )
		                            ->getMock();

		$subject->add_service( $preexisting_service, $service );
		$subject->save_setting( 'service', $preexisting_service );

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );

		$new_service_id = rand_str();
		$api_key        = rand_str();
		$subject->add_service( $new_service_id, $service );
		$post_data = [
			'exchange-rates-automatic' => 1,
			'update-schedule'          => 'daily',
			'exchange-rates-service'   => $new_service_id,
			'services'                 => [
				$new_service_id => [ 'api-key' => $api_key ]
			],
			'lifting_charge'           => random_int( 1, 1000 ),
			'update-time'              => rand_str(),
			'update-weekly-day'        => random_int(1,7),
			'update-monthly-day'       => random_int(1,30)
		];

		\WP_Mock::wpFunction( 'wp_get_schedule', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		\WP_Mock::wpFunction( 'wp_next_scheduled', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		\WP_Mock::wpFunction( 'wp_schedule_event', [
			'times' => 1,
			'args'  => [
				\Mockery::type( 'int' ),
				'daily',
				$subject::CRONJOB_EVENT
			]
		] );

		$service->expects( $this->exactly( 2 ) )
		        ->method( 'save_setting' )
		        ->withConsecutive(
			        [ 'last_error', false ],
			        [ 'api-key', $api_key ]
		        );

		$subject->update_exchange_rate_options( $post_data );

		$settings = $subject->get_settings();

		$this->assertSame( 1, $settings['automatic'] );

		$this->assertSame( $new_service_id, $subject->get_setting( 'service' ) );
		$this->assertSame( $post_data['update-schedule'], $subject->get_setting( 'schedule' ) );
		$this->assertSame( $post_data['lifting_charge'], $subject->get_setting( 'lifting_charge' ) );
		$this->assertSame( $post_data['update-time'], $subject->get_setting( 'time' ) );
		$this->assertSame( $post_data['update-weekly-day'], $subject->get_setting( 'week_day' ) );
		$this->assertSame( $post_data['update-monthly-day'], $subject->get_setting( 'month_day' ) );
	}

	/**
	 * @test
	 */
	public function delete_update_cronjob() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::wpFunction( 'wp_clear_scheduled_hook', [
			'times' => 1,
			'args'  => $subject::CRONJOB_EVENT
		] );

		$subject->delete_update_cronjob();
	}

	/**
	 * @test
	 */
	public function cron_schedules_filter_it_adds_monthly() {
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::wpPassthruFunction( '__' );

		$schedules = [];

		$subject->save_setting( 'schedule', 'monthly' );
		$month_days = [ 1, 2, 3, random_int( 4, 31 ) ];
		foreach ( $month_days as $month_day ) {
			$subject->save_setting( 'month_day', $month_day );
			$schedules = $subject->cron_schedules( $schedules );
			$this->assertNotNull( $schedules[ 'wcml_monthly_on_' . $month_day ] );

			$this->assertRegExp(
				"/Monthly on the {$month_day}[dhnrts]{2}/",
				$schedules[ 'wcml_monthly_on_' . $month_day ]['display']
			);

			$this->assertGreaterThan( 0, $schedules[ 'wcml_monthly_on_' . $month_day ]['interval'] );
			$this->assertLessThanOrEqual( DAY_IN_SECONDS * 31, $schedules[ 'wcml_monthly_on_' . $month_day ]['interval'] );

		}

	}

	/**
	 * @test
	 */
	public function cron_schedules_filter_it_adds_weekly() {
		$wp_locale        = $this->get_wp_locale_mock();
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject          = $this->get_subject( $woocommerce_wpml, $wp_locale );

		\WP_Mock::wpPassthruFunction( '__' );

		$schedules = [];

		$subject->save_setting( 'schedule', 'weekly' );

		$week_day = random_int( 0, 6 );

		$subject->save_setting( 'week_day', $week_day );

		$day_name = rand_str();
		$wp_locale->expects( $this->once() )
		          ->method( 'get_weekday' )
		          ->willReturn( $day_name );


		$schedules = $subject->cron_schedules( $schedules );
		$this->assertNotNull( $schedules[ 'wcml_weekly_on_' . $week_day ] );

		$this->assertSame( "Weekly on {$day_name}", $schedules[ 'wcml_weekly_on_' . $week_day ]['display'] );
		$this->assertSame( WEEK_IN_SECONDS, $schedules[ 'wcml_weekly_on_' . $week_day ]['interval'] );
	}

}