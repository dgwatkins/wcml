<?php

namespace WCML\Multicurrency\UI;

use WCML\StandAlone\IStandAloneAction;

use WPML\FP\Relation;
use function WCML\functions\isStandAlone;
use function WPML\Container\make;

class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Deferred_Action_Loader, IStandAloneAction {

	public function get_load_action() {
		return 'init';
	}

	/**
	 * @return \IWPML_Action|null
	 */
	public function create() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		if ( self::isMultiCurrencySettings() ) {
			return make(
				Hooks::class,
				[
					':wcmlSettings' => $woocommerce_wpml->settings,
				]
			);
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public static function isMultiCurrencySettings() {
		$isWcmlPage = Relation::propEq( 'page', 'wpml-wcml', $_GET );

		if ( isStandAlone() ) {
			return $isWcmlPage;
		}

		return $isWcmlPage
			&& Relation::propEq( 'tab', 'multi-currency', $_GET );
	}
}