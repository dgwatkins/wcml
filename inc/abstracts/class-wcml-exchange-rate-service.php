<?php

abstract class WCML_Exchange_Rate_Service{

    private $id;
    private $name;
    private $description;
    private $url;
    private $api_url;

    private $settings = array();

    const REQUIRES_KEY = false;

    public function __construct( $id, $name, $description, $api_url, $url = '' ) {

        $this->id           = $id;
        $this->name         = $name;
        $this->description  = $description;
        $this->api_url      = $api_url;
        $this->url          = $url;

        $this->settings = get_option('wcml_exchange_rate_service_' . $this->id, array() );

        if( self::REQUIRES_KEY ){
            $this->api_key = $this->get_setting( 'api-key' );
        }

    }

    public function get_name(){
        return $this->name;
    }

    public function get_description(){
        return $this->description;
    }

    public function get_url(){
        return $this->url;
    }

    /**
     * @param $from
     * @param $to
     * @return mixed
     */
    public abstract function get_rates( $from, $to );

    /**
     * @return array
     */
    public function get_settings(){
        return $this->settings;
    }

    /**
     *
     */
    public function save_settings( $settings = null ){
        if( is_null( $settings )){
            $settings =& $this->settings;
        }
        update_option('wcml_exchange_rate_service_' . $this->id, $settings );
    }

    /**
     * @param $key string
     * @return mixed|null
     */
    public function get_setting( $key ){
        return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
    }

    /**
     * @param $key string
     * @param $value mixed
     */
    public function save_setting( $key, $value ){
        $this->settings[$key] = $value;
        $this->save_settings();
    }

    /**
     * @return bool
     */
    public function is_key_required(){
        return static::REQUIRES_KEY;
    }


}