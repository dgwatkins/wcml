<?php

class WCML_Exchange_Rates_Currencylayer extends WCML_Exchange_Rate_Service{

    private $id             = 'currencylayer';
    private $name           = 'currencylayer';
    private $description    = '';
    private $url            = 'https://currencylayer.com/';
    private $api_url        = 'http://apilayer.net/api/live?access_key=%s&source=%s&currencies=%s&amount=1';
    private $api_key        = '';

    const REQUIRES_KEY      = true;

    function __construct() {
        $this->description = __( 'Currencylayer is a product maintained by apilayer, a technology company based in Austria, with offices in Vienna, and London, UK.', 'woocommerce-multilingual' );
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

        $url = sprintf( $this->api_url, $this->api_key, $from, join(',', $tos) );

        $http = new WP_Http();
        $data = $http->request( $url );

        if( is_wp_error( $data ) ){

            throw new Exception( join("\n", $data->get_error_messages() ));

        } else {

            $json = json_decode( $data['body'] );

            if( empty( $json->success ) ){
                if( !empty( $json->error->info ) ){
                    throw new Exception( $json->error->info );
                } else{
                    throw new Exception( __( 'Cannot get exchange rates. Connection failed.', 'woocommerce-multilingual' ) );
                }
            } else{

                if( isset( $json->quotes ) ){
                    foreach( $tos as $to ){
                        if( isset( $json->quotes->{$from.$to} ) ){
                            $rates[$to] = round( $json->quotes->{$from.$to}, 4 );
                        }
                    }
                }

            }
        }

        return $rates;

    }

}