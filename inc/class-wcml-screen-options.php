<?php

/**
 * Class WCML_Screen_Options
 */
class WCML_Screen_Options extends WPML_Templates_Factory {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WCML_Screen_Options constructor.
	 *
	 * @param $sitepress
	 */
	public function __construct( &$sitepress ) {
		parent::__construct();
		$this->sitepress = $sitepress;
	}

	/**
	 * Setup hooks.
	 */
	public function init() {
		add_filter( 'default_hidden_columns', array( $this, 'filter_screen_options' ), 10, 2 );
		add_filter( 'admin_init', array( $this, 'save_translation_controls' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'product_page_admin_notices' ), 10 );
	}

	/**
	 * Set default option for translations management column.
	 *
	 * @param $hidden
	 * @param $screen
	 *
	 * @return array
	 */
	public function filter_screen_options( $hidden, $screen ) {
		if ( 'edit-product' === $screen->id ) {
			$hidden[] = 'icl_translations';
		}
		return $hidden;
	}

	/**
	 * Save user options for management column.
	 */
	public function save_translation_controls() {
		if ( isset( $_GET['translation_controls'] )
		     && isset( $_GET['nonce'] )
		     && wp_verify_nonce( $_GET['nonce'], 'enable_translation_controls' )
		) {
			$user = get_current_user_id();
			$hidden_columns = get_user_meta( $user, 'manageedit-productcolumnshidden', true );
			if ( ! is_array( $hidden_columns ) ) {
				$hidden_columns = array();
			}
			if ( 0 === (int) $_GET['translation_controls'] ) {
				$hidden_columns[] = 'icl_translations';
			} else {
				$tr_control_index = array_search( 'icl_translations', $hidden_columns );
				if ( false !== $tr_control_index ) {
					unset( $hidden_columns[ $tr_control_index ] );
				}
			}

			update_user_meta( $user, 'manageedit-productcolumnshidden', $hidden_columns );
			wp_safe_redirect( admin_url( 'edit.php?post_type=product' ) );
		}
	}

	/**
	 * Display admin notice for translation management column.
	 */
	public function product_page_admin_notices() {
		$current_screen = get_current_screen();
		if ( 'edit-product' === $current_screen->id ) {
			$this->show();
		}
	}

	public function get_model() {
		$translate_url = esc_url_raw( admin_url( 'admin.php?page=wpml-wcml' ) );
		$nonce         = wp_create_nonce( 'enable_translation_controls' );
		$button_url    = esc_url_raw( admin_url( 'edit.php?post_type=product&translation_controls=0&nonce=' . $nonce ) );
		$button_text   = __( 'Disable translation controls',  'woocommerce-multilingual' );
		$first_line    = __( 'You have translation controls enabled.', 'woocommerce-multilingual' );
		$second_line   = sprintf( __( "Disabling the translation controls will make this page load faster.\nThe best place to translate products is in %sWPML-&gt;WooCommerce Multilingual%s.", 'woocommerce-multilingual' ), '<a href="' . $translate_url . '">', '</a>' );
		if ( false === $this->sitepress->show_management_column_content( 'product' ) ) {
			$button_url = admin_url( 'edit.php?post_type=product&translation_controls=1&nonce=' . $nonce );
			$button_text = __( 'Enable translation controls anyway',  'woocommerce-multilingual' );
			$first_line    = __( 'We disabled translation controls here.', 'woocommerce-multilingual' );
			$second_line   = sprintf( __( "Enabling the translation controls in this page can increase the load time for this admin screen.\n The best place to translate products is in %sWPML-&gt;WooCommerce Multilingual%s.", 'woocommerce-multilingual' ), '<a href="' . $translate_url . '">', '</a>' );
		}
		$model = array(
			'first_line'   => $first_line,
			'second_line'  => $second_line,
			'button_url'   => $button_url,
			'button_text'  => $button_text,
			'ps_message'   => __( 'P.S. You can also do that using Screen Options',  'woocommerce-multilingual' ),
		);

		return $model;
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/products-list/',
		);
	}

	public function get_template() {
		return 'admin-notice.twig';
	}
}
