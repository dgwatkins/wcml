<?php

namespace WCML\CLI;

class Commands implements \IWPML_CLI_Action {


	public function add_hooks() {

		\WP_CLI::add_command( 'wcml-multi-currency', [ self::class, 'runMcCommand' ] );
	}

	/**
	 * Update Multi-currency options.
	 *
	 *<status>
	 * : Status of MC: enable, disable
	 *
	 * ## OPTIONS
	 *
	 * [--mode=<by_language,by_location>]
	 * : Show currencies based on values language or location.
	 *
	 * [--currency=<code>]
	 * : Currency code.
	 *
	 * [--delete_currency=<code>]
	 * : Currency code to delete.
	 *
	 * [--languages=<list>]
	 * : List of languages to be visible on.
	 *
	 * [--countries=<list>]
	 * : List of countries to be visible on.
	 *
	 * [--location_mode=<all,exclude,include>]
	 * : Location mode - all, exclude, include.
	 *
	 * [--rate=<float>]
	 * : Rate value.
	 *
	 * [--position=<left,right,left_space,right_space>]
	 * : Currency position - left, right, left space, right space.
	 *
	 * [--thousand_sep=<string>]
	 * : Currency thousand separator - '.' or ',' .
	 *
	 * [--decimal_sep=<string>]
	 * : Currency decimal separator - '.' or ',' .
	 *
	 * [--num_decimals=<number>]
	 * : Number of decimals.
	 *
	 * [--rounding=<disabled,up,down,nearest>]
	 * : Rounding - disabled, up, down, nearest.
	 *
	 * [--rounding_increment=<1,10,100,1000>]
	 * : Rounding increment - 1, 10, 100, 1000.
	 *
	 * [--auto_subtract=<number>]
	 * : Rounding auto subtract amount.
	 *
	 * @param Array $args Arguments in array format.
	 * @param Array $assoc_args Key value arguments stored in associated array format.
	 *
	 * ## EXAMPLES
	 * wp wcml-multi-currency enable --currency=EUR --mode=by_language --languages=en,uk --rate=1.32
	 */
	public function runMcCommand( $args, $assoc_args ) {
		global $woocommerce_wpml;

		$settings = $woocommerce_wpml->get_settings();

		list( $status ) = $args;

		if ( 'enable' === $status ) {

			$settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;

			if ( isset( $assoc_args['mode'] ) ) {
				$settings['currency_mode'] = $assoc_args['mode'];
			}

			if ( isset( $assoc_args['delete_currency'] ) ) {

				unset( $settings['currency_options'][ $assoc_args['delete_currency'] ] );
			} elseif ( isset( $assoc_args['currency'] ) ) {

				$currencyOptions = $settings['currency_options'][ $assoc_args['currency'] ];

				$currencyExists = isset( $settings['currency_options'][ $assoc_args['currency'] ] );

				if ( isset( $assoc_args['countries'] ) ) {
					$currencyOptions['countries'] = wc_string_to_array( $assoc_args['countries'], ',' );
				}

				if ( isset( $assoc_args['languages'] ) ) {
					$languages = array_map( 'strtolower', wc_string_to_array( $assoc_args['languages'], ',' ) );

					$activeLanguages = array_keys( apply_filters( 'wpml_active_languages', [] ) );

					foreach ( $activeLanguages as $code ) {
						$currencyOptions['languages'][ $code ] = in_array( $code, $languages ) ? 1 : 0;
					}
				}

				$defaults = array(
					'rate'               => 1,
					'location_mode'      => 'all',
					'position'           => 'left',
					'thousand_sep'       => ',',
					'decimal_sep'        => '.',
					'num_decimals'       => 2,
					'rounding'           => 'disabled',
					'rounding_increment' => 1,
					'auto_subtract'      => 0
				);

				foreach ( $defaults as $key => $value ) {

					if ( $assoc_args[ $key ] ) {
						$currencyOptions[ $key ] = $assoc_args[ $key ];
					} elseif ( ! $currencyExists ) {
						$currencyOptions[ $key ] = $value;
					}
				}

				$settings['currency_options'][ $assoc_args['currency'] ] = $currencyOptions;
			}
		} elseif ( 'disable' === $status ) {
			$settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_DISABLED;
		}

		$woocommerce_wpml->update_settings( $settings );

		\WP_CLI::success( 'WooCommerce Multilingual Multi-currency settings saved' );
	}
}
