<?php

namespace WCML\PaymentGateways;

use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;
use WCML\MultiCurrency\Geolocation;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;


class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {

	const OPTION_KEY = 'wcml_payment_gateways';
	/* took this priority from wcgcl but we could not recall the reason of this number.*/
	const PRIORITY = 1000;

	public function add_hooks() {

		if ( is_admin() ) {
			if ( $this->isWCGatewaysSettingsScreen() ) {
				add_action( 'woocommerce_update_options_checkout', [ $this, 'updateSettingsOnSave' ], self::PRIORITY );
			}
			add_action( 'admin_notices', [ $this, 'maybeAddNotice'] );
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
			} else {
				$settings[ $gatewaySettings['ID'] ]['mode'] = 'all';
			}

			if ( isset( $gatewaySettings['countries'] ) ) {
				$settings[ $gatewaySettings['ID'] ]['countries'] = array_map( 'esc_attr', array_filter( explode( ',', $gatewaySettings['countries'] ) ) );
			} else {
				$settings[ $gatewaySettings['ID'] ]['countries'] = [];
			}

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

	public function maybeAddNotice(){
		if( class_exists( 'WooCommerce_Gateways_Country_Limiter' ) ) {
			echo $this->getNoticeText();
		}
	}

	/**
	 * @return string
	 */
	private function getNoticeText(){

		$text = '<div id="message" class="updated error">';
		$text .= '<p>';
		$text .= __( 'We noticed that you\'re using WooCommerce Gateways Country Limiter plugin which was integrated into WooCommerce Multilingual, please remove it!', 'woocommerce-multilingual' );
		$text .= '</p>';
		$text .= '</div>';

		return $text;
	}

	/**
	 * @param string $gatewayId
	 *
	 * @return array
	 */
	private function getSettings( $gatewayId ) {
		return Maybe::fromNullable( get_option( self::OPTION_KEY, false ) )
		            ->map( Obj::prop( $gatewayId ) )
		            ->getOrElse( [ 'mode' => 'all', 'countries' => [] ] );
	}

	/**
	 * @param array $settings
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
		return Obj::prop( 'section', $_GET ) && Relation::equals( 'wc-settings', Obj::prop( 'page', $_GET ) );
	}

}