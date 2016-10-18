<?php

class WCML_Exchange_Rates_YahooFinance extends WCML_Exchange_Rate_Service{

    private $id             = 'yahoo';
    private $name           = 'Yahoo! Finance';
    private $description    = '';
    private $url            = 'https://finance.yahoo.com/currency-converter';
    private $api_url        = 'http://finance.yahoo.com/d/quotes.csv?e=.csv&f=c4l1&s=%s'; // EURUSD=X,GBPUSD=X
    private $api_key        = '';

    const REQUIRES_KEY      = false;

    function __construct() {
        $this->description = __( "Yahoo! Finance is a media property that is part of Yahoo!'s network. It provides financial news, data and commentary including stock quotes, press releases, financial reports, and original programming.", 'woocommerce-multilingual' );
        parent::__construct( $this->id, $this->name, $this->description, $this->api_url, $this->url );
    }

    /**
     * @param $from string
     * @param $to array
     * @return array
     * @throws Exception
     */
    public function get_rates( $from, $tos ){

        $rates = array();

        $pairs = array();
        foreach( $tos as $to ){
            $pairs[] = $from . $to . '=X';
        }

        $url = sprintf( $this->api_url, join(',', $pairs) );

        $http = new WP_Http();
        $data = $http->request( $url );

        if( is_wp_error( $data ) ){

            throw new Exception( join("\n", $data->get_error_messages() ));

        } else {
            // str_getcsv not working as expected

            $lines = explode("\n", trim( $data['body'] ) );
            foreach( $lines as $line ){
                $to     = substr( $line, 1, 3);
                $rate   = trim( substr( $line, 6 ) );

                if( !is_numeric( $rate ) ){
                    throw new Exception( sprintf( __("Error reading the exchange rate for %s. Please try again. If the error persist, try selecting a different exchange rate service.", 'woocommerce-multilingual' ), $to ) );
                }

                $rates[$to] = $rate;
            }

        }

        return $rates;

    }

}