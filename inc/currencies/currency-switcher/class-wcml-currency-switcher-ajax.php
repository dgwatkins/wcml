<?php

/**
 * Class WCML_Currency_Switcher_Ajax
 *
 */
class WCML_Currency_Switcher_Ajax{

	private $woocommerce_wpml;

	public function __construct( &$woocommerce_wpml ) {

		$this->woocommerce_wpml = $woocommerce_wpml;

		add_action( 'init', array($this, 'init'), 5 );
	}

	public function init() {

		add_action( 'wp_ajax_wcml_currencies_order', array($this, 'wcml_currencies_order') );
		add_action( 'wp_ajax_wcml_currencies_switcher_preview', array($this, 'wcml_currencies_switcher_preview') );
		add_action( 'wp_ajax_wcml_currencies_switcher_save_settings', array($this, 'wcml_currencies_switcher_save_settings') );
		add_action( 'wp_ajax_wcml_delete_currency_switcher', array($this, 'wcml_delete_currency_switcher') );

	}

	public function wcml_currencies_order() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( !$nonce || !wp_verify_nonce( $nonce, 'set_currencies_order_nonce' ) ) {
			die('Invalid nonce');
		}

		$this->woocommerce_wpml->settings['currencies_order'] = explode( ';', $_POST['order'] );
		$this->woocommerce_wpml->update_settings();
		echo json_encode( array('message' => __( 'Currencies order updated', 'woocommerce-multilingual' )) );
		die;
	}

	public function wcml_currencies_switcher_save_settings() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( !$nonce || !wp_verify_nonce( $nonce, 'wcml_currencies_switcher_save_settings' ) ) {
			die('Invalid nonce');
		}
		$wcml_settings =& $this->woocommerce_wpml->settings;
		$switcher_settings = array();

		// Allow some HTML in the currency switcher
		$currency_switcher_format = strip_tags( stripslashes_deep( $_POST[ 'template' ] ), '<img><span><u><strong><em>');
		$currency_switcher_format = htmlentities( $currency_switcher_format );
		$currency_switcher_format = sanitize_text_field( $currency_switcher_format );
		$currency_switcher_format = html_entity_decode( $currency_switcher_format );

		$switcher_id = sanitize_text_field( $_POST[ 'switcher_id' ] );
		if( $switcher_id == 'new_widget' ){
			$switcher_id = sanitize_text_field( $_POST[ 'widget_id' ] );
		}

		$switcher_settings[ 'widget_title' ]   = sanitize_text_field( $_POST[ 'widget_title' ] );
		$switcher_settings[ 'switcher_style' ] = sanitize_text_field( $_POST[ 'switcher_style' ] );
		$switcher_settings[ 'template' ]       = $currency_switcher_format;

		foreach( $_POST[ 'color_scheme' ] as $color_id => $color ){
			$switcher_settings[ 'color_scheme' ][ sanitize_text_field( $color_id ) ] = sanitize_hex_color( $color );
		}

		$wcml_settings[ 'currency_switchers' ][ $switcher_id ] = $switcher_settings;

		//update widget settings
		if( $switcher_id != 'product' ){
			$widget_settings = get_option('widget_currency_sel_widget');
			$setting_match = false;
			foreach( $widget_settings as $key => $widget_setting ){
				if( $switcher_id == $widget_setting['id'] ){
					$setting_match = true;
					$widget_settings[ $key ][ 'settings' ] = $switcher_settings;
				}
			}

			if( !$setting_match ){
				$widget_settings[] = array(
					'id' => $switcher_id,
					'settings' => $switcher_settings
				);
			}

			update_option( 'widget_currency_sel_widget', $widget_settings );
		}

		$this->woocommerce_wpml->update_settings( $wcml_settings );

		die();
	}

	public function wcml_delete_currency_switcher(){
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( !$nonce || !wp_verify_nonce( $nonce, 'delete_currency_switcher' ) ) {
			die('Invalid nonce');
		}

		$switcher_id = sanitize_text_field( $_POST[ 'switcher_id' ] );

		$wcml_settings =& $this->woocommerce_wpml->settings;

		unset( $wcml_settings[ 'currency_switchers' ][ $switcher_id ] );

		$this->woocommerce_wpml->update_settings( $wcml_settings );

		die();
	}

	public function wcml_currencies_switcher_preview() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( !$nonce || !wp_verify_nonce( $nonce, 'wcml_currencies_switcher_preview' ) ) {
			die('Invalid nonce');
		}
		$return= array();

		$inline_css = $this->woocommerce_wpml->cs_templates->get_color_picket_css( $_POST['switcher_id'], array( 'switcher_style' => $_POST['switcher_style'], 'color_scheme' => $_POST['color_scheme'] ) );
		$return['inline_css'] = $inline_css;

		ob_start();
		$this->woocommerce_wpml->multi_currency->currency_switcher->wcml_currency_switcher(
			array(
				'switcher_id'	 => $_POST['switcher_id'],
				'format'         => isset( $_POST['template'] ) ? stripslashes_deep( $_POST['template'] ) : '%name% (%symbol%) - %code%',
				'switcher_style' => $_POST['switcher_style'],
				'color_scheme'   => $_POST['color_scheme']
			)
		);
		$switcher_preview = ob_get_contents();
		ob_end_clean();

		$return['preview'] = $switcher_preview;

		echo json_encode( $return );

		die();
	}

}
