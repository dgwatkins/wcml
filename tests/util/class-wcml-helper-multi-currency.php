<?php

/**
 * Enable and setup multi-currency
 */

class WCML_Helper_Multi_Currency {

    private $woocommerce_wpml;

	public function __construct( $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
    }

    /**
     * Enable multi currency
     *
     */
    public function enable_multi_currency() {

        $this->woocommerce_wpml->settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;
        $this->woocommerce_wpml->update_settings();

    }

    public function add_currency( $code, $rate, $options = array() ) {

        $languages 		= array_map('trim', explode(',', WPML_TEST_LANGUAGE_CODES));

        $defaults = array(
            'position'              => 'left',
            'thousand_sep'          => ',',
            'decimal_sep'           => '.',
            'num_decimals'          => 2,
            'rounding'              => 'disabled',
            'rounding_increment'    => 1,
            'auto_subtract'         => 0

        );

        foreach( $defaults as $key => $value ){
            if( !isset( $options[$key]) ){
                $options[ $key ] = $defaults[ $key ];
            }
        }

        $this->woocommerce_wpml->settings['currency_options'][$code] = array(
            'rate' 				=> $rate,
            'position'			=> $options['position'],
            'thousand_sep'		=> $options['thousand_sep'],
            'decimal_sep'		=> $options['decimal_sep'],
            'num_decimals'		=> $options['num_decimals'],
            'rounding'			=> $options['rounding'],
            'rounding_increment'=> $options['rounding_increment'],
            'auto_subtract'		=> $options['auto_subtract']
        );

        // enabled on all languages
        foreach( $languages as $cur ){
            $this->settings['currency_options'][$code]['languages'][$cur] = 1;
        }


        $this->woocommerce_wpml->update_settings();
    }

    public function setup_3_currencies(){

	    $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
	    $this->woocommerce_wpml->multi_currency->prices = new WCML_Multi_Currency_Prices( $this->woocommerce_wpml->multi_currency, $this->woocommerce_wpml->settings['currency_options'] );
	    $this->woocommerce_wpml->multi_currency->prices->add_hooks();

	    $currencies = array(
		    'USD' => array(
			    'rate'      => 1.34,
			    'options'   => array(
				    'position' => 'left',
				    'thousand_sep' => ',',
				    'decimal_sep' => '.',
				    'num_decimals' => 2,
				    'rounding' => 'disabled',
				    'rounding_increment' => 0,
				    'auto_subtract' => 0
			    ),
		    ),
		    'JPY' => array(
			    'rate'      => 137,
			    'options'   => array(
				    'position' => 'left',
				    'thousand_sep' => ',',
				    'decimal_sep' => '.',
				    'num_decimals' => 0,
				    'rounding' => 'disabled',
				    'rounding_increment' => 0,
				    'auto_subtract' => 0
			    )
		    ),
		    'BTC' => array(
			    'rate'      => 0.0020,
			    'options'   => array(
				    'position' => 'left',
				    'thousand_sep' => ',',
				    'decimal_sep' => '.',
				    'num_decimals' => 4,
				    'rounding' => 'disabled',
				    'rounding_increment' => 0,
				    'auto_subtract' => 0
			    )
		    )

	    );

	    foreach( $currencies as $code => $currency ){
		    $this->add_currency( $code, $currency['rate'], $currency['options'] );
	    }

        $this->woocommerce_wpml->multi_currency->init_currencies();

    }

}