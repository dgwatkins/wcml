<?php

class Test_WCML_Currencies extends WCML_UnitTestCase {

	private $settings_backup;
	private $settings;

	function setUp(){
		parent::setUp();

		set_current_screen( 'dashboard' );

		// settings
		$settings = $this->woocommerce_wpml->settings;
		$this->settings_backup = $settings;
		$settings['enable_multi_currency'] = 2;
		$settings['default_currencies'] = array( 'en' => 'USD', 'de' => 'RON'  );
		$settings['currency_options']['USD'] = array(
			'rate' 				=> 1.55,
			'position'			=> 'left',
			'thousand_sep'		=> '#',
			'decimal_sep'		=> '@',
			'num_decimals'		=> 4,
			'rounding'			=> 'down',
			'rounding_increment'=> 10,
			'auto_subtract'		=> 3
		);
		$settings['currency_options']['RON'] = array(
			'rate' 				=> 1.64,
			'position'			=> 'right',
			'thousand_sep'		=> '.',
			'decimal_sep'		=> ',',
			'num_decimals'		=> 0,
			'rounding'			=> 'up',
			'rounding_increment'=> 100,
			'auto_subtract'		=> 1

		);

		$this->settings = $settings;

		$this->woocommerce_wpml->update_settings( $settings );
	}

	/*
	 *  When the default WooCommerce currency is updated, update list of currencies configure for the multi-currency
	 */
	public function test_woocommerce_currency_update(){


		// change currency from GBP (default) to RON
		// It means that only USD will be a secondary currency
		$_POST['woocommerce_currency'] = 'RON';

		do_action('woocommerce_settings_save_general');

		$this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
		$this->woocommerce_wpml->multi_currency->init();

		$secondary_currencies = array_keys( $this->woocommerce_wpml->multi_currency->get_currencies( false ) );

		// RON is no longer a secondary currency sice it was configured to be the default
		$this->assertEquals( array( 'USD' ), $secondary_currencies );


	}

	function tearDown() {
		parent::tearDown();

		$this->woocommerce_wpml->settings = $this->settings_backup;
		$this->woocommerce_wpml->update_settings();

	}

}
