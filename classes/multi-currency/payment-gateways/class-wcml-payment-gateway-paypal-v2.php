<?php

use WPML\FP\Obj;
use WPML\FP\Fns;
use function WCML\functions\getClientCurrency;

/**
 * @see https://wordpress.org/plugins/woocommerce-paypal-payments/
 */
class WCML_Payment_Gateway_PayPal_V2 extends WCML_Payment_Gateway_PayPal {

	const ID = 'ppcp-gateway';

	const FIELDS = [
		'merchant_email',
		'merchant_id',
		'client_id',
		'client_secret',
		'currency',
	];

	const BEARER_TOKEN_TRANSIENT = 'ppcp-paypal-bearerppcp-bearer';

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => true,
			'settings'    => $this->get_currencies_details(),
			'tooltip'     => '',
			'strings'     => [
				'labelCurrency'       => __( 'Currency', 'woocommerce-multilingual' ),
				'labelPayPalEmail'    => __( 'PayPal Email', 'woocommerce-multilingual' ),
				'labelMerchantId'     => __( 'Merchant ID', 'woocommerce-multilingual' ),
				'labelClientId'       => __( 'Client ID', 'woocommerce-multilingual' ),
				'labelSecretKey'      => __( 'Secret Key', 'woocommerce-multilingual' ),
				// translators: %s is currency code.
				'tooltipNotSupported' => __( 'This gateway does not support %s. To show this gateway please select another currency.', 'woocommerce-multilingual' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function get_currencies_details() {
		$currencies_details     = [];
		$default_currency       = wcml_get_woocommerce_currency_option();
		$woocommerce_currencies = get_woocommerce_currencies();

		foreach ( $woocommerce_currencies as $code => $currency ) {
			if ( $default_currency === $code ) {
				$getSetting = Obj::propOr( '', Fns::__, $this->get_gateway()->settings );
			} else {
				$getSetting = Obj::propOr( '', Fns::__, $this->get_setting( $code ) );
			}

			foreach ( self::FIELDS as $key ) {
				$currencies_details[ $code ][ $key ] = $getSetting( $key );
			}

			$currencies_details[ $code ]['isValid'] = $this->is_valid_for_use( $code );
		}

		return $currencies_details;
	}

	public function add_hooks() {
		add_filter( 'ppcp_create_order_request_body_data', [ $this, 'filter_request_body_data' ] );
		add_filter( 'ppcp_smart_button_currency', function( $currency ) {
			$gateway_setting = $this->get_setting( $currency );

			if ( $gateway_setting ) {
				return $gateway_setting['currency'];
			}

			return $currency;
		} );
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function filter_ppcp_args( $settings ) {
		if ( is_admin() ) {
			return $settings;
		}

		$gateway = Obj::prop( getClientCurrency(), get_option( self::OPTION_KEY . self::ID, [] ) );

		if ( $gateway ) {
			$getSetting = Obj::prop( Fns::__, $gateway );

			foreach ( self::FIELDS as $key ) {
				$settings[ $key ] = $getSetting( $key ) ?: $settings[ $key ];
			}
		}

		return $settings;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function filter_request_body_data( $data ) {
		$client_currency = Obj::path( [ 'purchase_units', 0, 'amount', 'currency_code' ], $data );
		$gateway_setting = $this->get_setting( $client_currency );

		if ( $gateway_setting ) {
			$gatewayCurrency = $gateway_setting['currency'];

			if ( $client_currency !== $gatewayCurrency ) {
				$convertAmount = function( $amount ) use ( $gatewayCurrency ) {
					$value    = $amount['value'];
					$currency = $amount['currency_code'];

					$priceInDefaultCurrency = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $value, $currency );
					$priceInGatewayCurrency = $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $priceInDefaultCurrency, $gatewayCurrency );

					return [
						'value'         => $priceInGatewayCurrency,
						'currency_code' => $gatewayCurrency,
					];
				};

				foreach ( $data['purchase_units'] as &$purchaseUnit ) {
					$purchaseUnit['amount'] = array_merge( $purchaseUnit['amount'], $convertAmount( $purchaseUnit['amount'] ) );

					$purchaseUnit['amount']['breakdown']['item_total'] = $convertAmount( $purchaseUnit['amount']['breakdown']['item_total'] );
					$purchaseUnit['amount']['breakdown']['shipping']   = $convertAmount( $purchaseUnit['amount']['breakdown']['shipping'] );
					$purchaseUnit['amount']['breakdown']['tax_total']  = $convertAmount( $purchaseUnit['amount']['breakdown']['tax_total'] );

					foreach ( $purchaseUnit['items'] as &$item ) {
						$item['unit_amount'] = $convertAmount( $item['unit_amount'] );
					}
				}
			}
		}

		return $data;
	}
}
