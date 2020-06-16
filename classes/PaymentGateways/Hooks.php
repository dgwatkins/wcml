<?php

namespace WCML\PaymentGateways;

use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;
use WCML\MultiCurrency\Geolocation;


class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {

	const OPTION_KEY = 'wcml_payment_gateways';
	const PRIORITY = 100;

	public function add_hooks() {

		if ( is_admin() ) {
			if ( $this->isWCGatewaysSettingsScreen() ) {
				add_action( 'woocommerce_update_options_checkout', [ $this, 'updateSettingsOnSave' ], self::PRIORITY );
			}
		} else {
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filterByCountry' ], self::PRIORITY );
		}
	}

	public function updateSettingsOnSave() {

		if ( isset( $_POST[ self::OPTION_KEY ] ) ) {

			$gatewaySettings = $_POST[ self::OPTION_KEY ];

			$settings = [];

			if ( in_array( $gatewaySettings['mode'], [
				'all',
				'exclude',
				'include'
			], true ) ) {
				$settings[ $gatewaySettings['ID'] ]['mode'] = $gatewaySettings['mode'];
			}

			$settings[ $gatewaySettings['ID'] ]['countries'] = array_map( 'esc_attr', array_filter( explode( ',', $gatewaySettings['countries'] ) ) );

			$this->updateSettings( $settings );
		}
	}

	/**
	 * @param array $payment_gateways
	 *
	 * @return array
	 */
	public function filterByCountry( $payment_gateways ) {

		$customer_country = Geolocation::getUserCountry();

		if ( $customer_country ) {

			$ifExceptCountries = function ( $gateway ) use ( $customer_country ) {
				$gatewaySettings = $this->getSettings( $gateway->id );

				return $gatewaySettings['mode'] == 'exclude' && in_array( $customer_country, $gatewaySettings['countries'] );
			};

			$ifNotIncluded = function ( $gateway ) use ( $customer_country ) {
				$gatewaySettings = $this->getSettings( $gateway->id );

				return $gatewaySettings['mode'] == 'include' && ! in_array( $customer_country, $gatewaySettings['countries'] );
			};

			return wpml_collect( $payment_gateways )
				->reject( $ifExceptCountries )
				->reject( $ifNotIncluded )
				->toArray();
		}

		return $payment_gateways;
	}

	/**
	 * @param string $gatewayId
	 *
	 * @return array
	 */
	private function getSettings( $gatewayId ) {

		$settings = get_option( self::OPTION_KEY, false );

		if ( $settings && isset( $settings[ $gatewayId ] ) ) {
			return $settings[ $gatewayId ];
		}

		return [ 'mode' => 'all', 'countries' => [] ];
	}

	/**
	 * @param $settings
	 *
	 * @return bool
	 */
	private function updateSettings( $settings ) {
		return update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * @return bool
	 */
	private function isWCGatewaysSettingsScreen() {
		return isset( $_GET['section'] ) && isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'];
	}

}
