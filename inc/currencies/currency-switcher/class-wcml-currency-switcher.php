<?php

/**
 * Class WCML_Currency_Switcher
 *
 * Main class
 */
class WCML_Currency_Switcher{

	private $woocommerce_wpml;
	private $sitepress;
	private $is_touch_screen;

	public function __construct( &$woocommerce_wpml, &$sitepress ) {

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress = $sitepress;

		add_action( 'init', array($this, 'init'), 5 );
	}

	public function init() {

		add_action( 'wp_ajax_wcml_currencies_order', array($this, 'wcml_currencies_order') );
		add_action( 'wp_ajax_wcml_currencies_switcher_preview', array($this, 'wcml_currencies_switcher_preview') );
		add_action( 'wp_ajax_wcml_currencies_switcher_save_settings', array($this, 'wcml_currencies_switcher_save_settings') );
		add_action( 'wp_ajax_wcml_delete_currency_switcher', array($this, 'wcml_delete_currency_switcher') );

		add_action( 'wcml_currency_switcher', array($this, 'wcml_currency_switcher') );
		//@deprecated 3.9
		add_action( 'currency_switcher', array($this, 'currency_switcher') );

		add_shortcode( 'currency_switcher', array($this, 'currency_switcher_shortcode') );

		// Built in currency switcher
		add_action( 'woocommerce_product_meta_start', array($this, 'show_currency_switcher') );


	}

	/**
	 * @return array
	 */
	public static function parameters() {
		return array(
			'css_prefix'     => 'wcml-cs-',
			'core_templates' => array(
				'dropdown'          => 'wpml-legacy-dropdown',
				'dropdown-click'    => 'wpml-legacy-dropdown-click',
				'list-vertical'     => 'wpml-legacy-vertical-list',
				'list-horizontal'   => 'wpml-legacy-horizontal-list'
			),
		);
	}

	public function wcml_currencies_order() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( !$nonce || !wp_verify_nonce( $nonce, 'set_currencies_order_nonce' ) ) {
			die('Invalid nonce');
		}
		global $woocommerce_wpml;

		$woocommerce_wpml->settings['currencies_order'] = explode( ';', $_POST['order'] );
		$woocommerce_wpml->update_settings();
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
		$switcher_settings[ 'switcher_style' ]   = sanitize_text_field( $_POST[ 'switcher_style' ] );
		$switcher_settings[ 'template' ]        = $currency_switcher_format;

		foreach( $_POST[ 'color_scheme' ] as $color_id => $color ){
			$switcher_settings[ 'color_scheme' ][ sanitize_text_field( $color_id ) ] = sanitize_hex_color( $color );
		}

		$wcml_settings[ 'currency_switchers' ][ $switcher_id ] = $switcher_settings;

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
		$this->wcml_currency_switcher(
			array(
				'switcher_id'	 => $_POST['switcher_id'],
				'format'         => $_POST['template'] ? stripslashes_deep( $_POST['template'] ) : '%name% (%symbol%) - %code%',
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

	public function currency_switcher_shortcode( $atts ) {
		extract( shortcode_atts( array(), $atts ) );

		ob_start();
		$this->wcml_currency_switcher( $atts );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public function wcml_currency_switcher( $args = array() ) {
		global $sitepress;

		if ( is_page( wc_get_page_id( 'myaccount' ) ) ) {
			return '';
		}

		if( $args === '' ){
			$args = array(
				'switcher_id' => 'product'
			);
		}

		$wcml_settings = $this->woocommerce_wpml->get_settings();
		$multi_currency_object =& $this->woocommerce_wpml->multi_currency;

		if( isset( $args[ 'switcher_id' ] ) && isset( $wcml_settings[ 'currency_switchers' ][ $args[ 'switcher_id' ] ] ) ){

			$currency_switcher_settings = $wcml_settings[ 'currency_switchers' ][ $args[ 'switcher_id' ] ];

			if ( !isset( $args[ 'switcher_style' ] ) ) {
				$args[ 'switcher_style' ] = isset( $currency_switcher_settings[ 'switcher_style' ] ) ? $currency_switcher_settings[ 'switcher_style' ] : 'wcml-dropdown';
			}

			if ( !isset( $args[ 'format' ] ) ) {
				$args[ 'format' ] = isset( $currency_switcher_settings[ 'template' ] ) && $currency_switcher_settings[ 'template' ] != '' ?
					$currency_switcher_settings[ 'template' ] : '%name% (%symbol%) - %code%';
			}

			if ( !isset( $args[ 'color_scheme' ] ) ) {
				$args[ 'color_scheme' ] = isset($currency_switcher_settings['color_scheme']) ? $currency_switcher_settings['color_scheme'] : array();
			}

		}elseif( !isset( $args[ 'switcher_style' ] ) ){
			$args[ 'switcher_style' ] = 'wcml-dropdown';
		}

		$preview = '';
		$show_currency_switcher = true;

		$display_custom_prices = isset( $wcml_settings[ 'display_custom_prices' ] ) && $wcml_settings[ 'display_custom_prices' ];

		$is_cart_or_checkout = is_page( wc_get_page_id( 'cart' ) ) || is_page( wc_get_page_id( 'checkout' ) );

		if ( $display_custom_prices ) {
			if( $is_cart_or_checkout ){
				$show_currency_switcher = false;
			}elseif( is_product() ){
				$current_product_id = get_post()->ID;
				$original_product_language = $this->woocommerce_wpml->products->get_original_product_language( $current_product_id );

				$use_custom_prices  = get_post_meta(
					apply_filters( 'translate_object_id', $current_product_id, get_post_type( $current_product_id ), true, $original_product_language ),
					'_wcml_custom_prices_status',
					true
				);

				if ( !$use_custom_prices )  $show_currency_switcher = false;
			}
		}

		if ( $show_currency_switcher ) {

			$currencies = isset($wcml_settings['currencies_order']) ?
				$wcml_settings['currencies_order'] :
				$multi_currency_object->get_currency_codes();

			if ( count($currencies) > 1) {

				if ( !is_admin() ) {
					foreach ( $currencies as $k => $currency ) {
						if ( $wcml_settings['currency_options'][$currency]['languages'][$sitepress->get_current_language()] != 1 ) {
							unset( $currencies[$k] );
						}
					}
				}

				$template = $this->woocommerce_wpml->cs_templates->get_template( $args[ 'switcher_style' ] );
				$template->set_model( $this->get_model( $args, $currencies ) );
				$preview = $template->get_view();

			} else{

				if( is_admin() ){

					$preview = '<i>' . __("You haven't added any secondary currencies.", 'woocommerce-multilingual') . '</i>';

				}else{

					$preview = '';

				}

			}

		}

		if ( !isset($args['echo']) || $args['echo'] ) {
			echo $preview;
		} else {
			return $preview;
		}

	}

	public function get_model( $args, $currencies ){

		$css_classes = $this->get_css_classes( array( $args[ 'switcher_style' ], $args[ 'switcher_id' ] ) );

		$model = array(
			'css_classes' 	=> $css_classes,
			'format'        => isset( $args['format'] ) ? $args['format'] : '%code%, %symbol%, %name%',
			'currencies'    => $currencies,
			'selected_currency' => $this->woocommerce_wpml->multi_currency->get_client_currency()
		);

		return $model;
	}

	public function get_css_classes( $classes = array() ){

		if ( $this->sitepress->is_rtl( $this->sitepress->get_current_language() ) ) {
			$classes[] = 'wcml-cs-rtl';
		}

		$classes = $this->add_user_agent_touch_device_classes( $classes );

		$classes = apply_filters( 'wcml_cs_template_css_classes', $classes );

		return implode( ' ', $classes );
	}

	public function add_user_agent_touch_device_classes( $classes ) {

		if ( is_null( $this->is_touch_screen ) ) {
			require_once ICL_PLUGIN_PATH . '/lib/mobile-detect.php';
			$mobile_detect   = new WPML_Mobile_Detect();
			$is_touch_screen = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		}

		if ( $this->is_touch_screen ) {
			$classes[] = 'wcml-cs-touch-device';
		}

		return $classes;
	}

	public function show_currency_switcher() {
		$settings = $this->woocommerce_wpml->get_settings();

		if ( is_product() && isset($settings['currency_switcher_product_visibility']) && $settings['currency_switcher_product_visibility'] === 1 ) {
			echo(do_shortcode( '[currency_switcher]' ));
			echo '<br />';
		}

	}

	/**
	 * @deprecated 3.9
	 */
	public function currency_switcher( $args = array() ){
		$this->wcml_currency_switcher( $args );
	}


	/**
	 * @return array
	 */
	public function get_registered_sidebars() {
		global $wp_registered_sidebars;

		return is_array( $wp_registered_sidebars ) ? $wp_registered_sidebars : array();
	}

}
