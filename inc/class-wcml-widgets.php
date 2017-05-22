<?php

class WCML_Widgets {
	private $settings;

	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function init_hooks() {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	public function register_widgets() {

		if ( WCML_MULTI_CURRENCIES_INDEPENDENT === (int) $this->get_setting( 'enable_multi_currency' ) ) {
			register_widget( 'WCML_Currency_Switcher_Widget' );
		}

		if ( WCML_CART_CLEAR === $this->get_sub_setting( 'cart_sync', 'currency_switch' ) || WCML_CART_CLEAR === $this->get_sub_setting( 'cart_sync', 'lang_switch' ) ) {
			register_widget( 'WCML_Cart_Removed_Items_Widget' );
		}
	}

	private function get_setting( $key, $default = null ) {
		if ( $this->settings && array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}

		return $default;
	}

	private function get_sub_setting( $key, $subkey, $default = null ) {
		$setting = $this->get_setting( $key );

		if ( is_array( $setting ) && array_key_exists( $subkey, $setting ) ) {
			return $setting[ $subkey ];
		}

		return $default;
	}
}
