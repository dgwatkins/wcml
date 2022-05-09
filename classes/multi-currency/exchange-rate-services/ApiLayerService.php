<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

use WPML\FP\Obj;
use WPML\FP\Relation;

abstract class ApiLayerService extends Service {

	/**
	 * @return string
	 */
	abstract protected function getApiLayerUrl();

	/**
	 * @param string $from The base currency code.
	 * @param array  $tos  The target currency codes.
	 *
	 * @return array|\WP_Error
	 */
	protected function getRawData( $from, $tos ) {
		$data = $this->getRawDataFromApiLayerEndpoint( $from, $tos );

		if ( $this->isWrongAuthenticationWithApiLayer( $data ) ) {
			$data = $this->getRawDataFromLegacyEndpoint( $from, $tos );
		}

		return $data;
	}

	/**
	 * @param object $data
	 *
	 * @return bool
	 */
	private function isWrongAuthenticationWithApiLayer( $data ) {
		return Obj::path( [ 'response', 'code' ], $data ) === 401;
	}

	/**
	 * @param string $from The base currency code.
	 * @param array  $tos  The target currency codes.
	 *
	 * @return array|\WP_Error
	 */
	private function getRawDataFromApiLayerEndpoint( $from, $tos ) {
		return wp_safe_remote_get(
			sprintf( $this->getApiLayerUrl(), $from, implode( ',', $tos ) ),
			[ 'headers' => [ 'apikey' => $this->getApiKey() ] ]
		);
	}

	/**
	 * @param string $from The base currency code.
	 * @param array  $tos  The target currency codes.
	 *
	 * @return array|\WP_Error
	 */
	private function getRawDataFromLegacyEndpoint( $from, $tos ) {
		return parent::getRawData( $from, $tos );
	}
}
