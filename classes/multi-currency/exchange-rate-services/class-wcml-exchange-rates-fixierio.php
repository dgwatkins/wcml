<?php

/**
 * Class WCML_Exchange_Rates_Fixierio
 */
class WCML_Exchange_Rates_Fixierio extends WCML_Exchange_Rate_Service {

	private $id = 'fixierio';
	private $name = 'Fixer.io';
	private $url = 'http://fixer.io/';
	private $api_url = 'http://api.fixer.io/latest?base=%s&symbols=%s';

	protected $api_key = '';
	protected $requires_key = false;

	public function __construct() {
		parent::__construct( $this->id, $this->name, $this->api_url, $this->url );
	}

	/**
	 * @param string $from
	 * @param  array $tos
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_rates( $from, $tos ) {

		parent::clear_last_error();
		$rates = array();

		$url = sprintf( $this->api_url, $from, implode( ',', $tos ) );

		$data = wp_safe_remote_get( $url );

		if ( is_wp_error( $data ) ) {

			$http_error = implode( "\n", $data->get_error_messages() );
			parent::save_last_error( $http_error );
			throw new Exception( $http_error );

		}

		$json = json_decode( $data['body'] );

		if ( ! isset( $json->base, $json->rates ) ) {
			$error = isset( $json->error ) ? $json->error :
				__( 'Cannot get exchange rates. Connection failed.', 'woocommerce-multilingual' );
			parent::save_last_error( $error );
			throw new Exception( $error );
		}

		foreach ( $json->rates as $to => $rate ) {
			$rates[ $to ] = round( $rate, 4 );
		}

		return $rates;

	}

}