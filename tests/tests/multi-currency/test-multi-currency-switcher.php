<?php

class Test_WCML_Multi_Currency_Switcher extends WCML_UnitTestCase {

    private $multi_currency;
    private $multi_currency_helper;

    private $currencies = array();
    private $products = array();

    private $switcher_args = array();

    public function setUp() {

        add_filter('wcml_load_multi_currency_in_ajax', '__return_true');
        set_current_screen( 'front' );

        parent::setUp();

        $this->multi_currency_helper = new WCML_Helper_Multi_Currency( $this->woocommerce_wpml );
        $this->multi_currency_helper->enable_multi_currency();

        //
        // THE CONTEXT
        //
        $this->products = array(
            '0' => array(
                'title' => 'Test Shipping Costs',
                'price' => 50
            )
        );
        $this->currencies = array(
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
                )
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

        foreach( $this->currencies as $code => $currency ){
            $this->multi_currency_helper->add_currency( $code, $currency['rate'], $currency['options'] );
        }


        // Multi currency objects
        $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        $this->multi_currency =& $this->woocommerce_wpml->multi_currency;

        $this->multi_currency->currency_swicther = new WCML_Currency_Switcher( $this->woocommerce_wpml, $this->sitepress );

        $this->multi_currency->prices->prices_init();


        $this->switcher_args['switcher_style']  = array(
            'dropdown' => array(
                'expected_string_match' => '#</?select /?([^>]*)>#',
            ),
            'list' => array(
                'expected_string_match' => '#</?ul /?([^>]*)>#',
            )
        );
        $this->switcher_args['format']          = array(

            '%name%/%symbol%' => array(
                'expected' => array(
                    'USD' => 'United States dollar/&#36;',
                    'JPY' => 'Japanese yen/&yen;',
                    'BTC' => 'Bitcoin/&#3647;',
                    'GBP' => 'Pound sterling/&pound;'
                )
            ),

            '%code%/#%subtotal%' => array(
                'expected' => array(
                    'USD' => 'USD/#<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>0.00</span>',
                    'JPY' => 'JPY/#<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&yen;</span>0</span>',
                    'BTC' => 'BTC/#<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#3647;</span>0.0000</span>',
                    'GBP' => 'GBP/#<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>0.00</span>'
                )
            )
        );
        $this->switcher_args['orientation']     = array(
            'horizontal' => array(
                'expected_string_match' => '#curr_list_horizontal#',

            ),
            'vertical' => array(
                'expected_string_match' => '#curr_list_vertical#',
            )
        );


    }

//    public function test_currency_switcher(){
//
//        $wcml_settings = $this->woocommerce_wpml->get_settings();
//
//        $currencies = isset($wcml_settings['currencies_order']) ?
//            $wcml_settings['currencies_order'] :
//            $this->multi_currency->get_currency_codes();
//
//        foreach( $this->switcher_args['switcher_style'] as $style => $expected_style ){
//
//            foreach( $this->switcher_args['orientation'] as $orientation => $expected_orientation ){
//
//                foreach( $this->switcher_args['format'] as $format => $expected ){
//                    $args = array(
//                        'switcher_style' => $style,
//                        'orientation'    => $orientation,
//                        'format'         => $format
//
//                    );
//
//                    $switcher_ui = new WCML_Currency_Switcher_Template($args, $this->woocommerce_wpml , $currencies);
//                    $switcher_html = $switcher_ui->get_view();
//
//                }
//
//                $this->assertRegExp( $expected_style['expected_string_match'], $switcher_html );
//            }
//
//            if( $style == 'list') {
//                $this->assertRegExp( $expected_orientation['expected_string_match'], $switcher_html );
//            }
//
//        }
//
//    }

    public function test_get_formatted_price(){

	    // reset WooCommerce translations
	    global $l10n;
	    unset( $l10n[ 'woocommerce' ] );

        $currencies = isset($wcml_settings['currencies_order']) ?
            $wcml_settings['currencies_order'] :
            $this->multi_currency->get_currency_codes();

        foreach( $this->switcher_args['format'] as $format => $expected ) {
            foreach ( $currencies as $currency ) {

                $args = array( 'format' => $format );
                $switcher = new WCML_Currency_Switcher_Template( $this->woocommerce_wpml, $args );
                $formatted = $switcher->get_formatted_price( $currency, $format );

                $this->assertEquals( $this->switcher_args['format'][$format]['expected'][$currency], $formatted );

            }
        }
    }



}