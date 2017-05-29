<?php

class WCML_Admin_Menu_Documentation_Links {
	/** @var WP_Post|null */
	private $post;
	/** @var string */
	private $pagenow;
	/** @var WCML_Tracking_Link */
	private $tracking_link;

	/**
	 * @param null|WP_Post $page
	 * @param string $pagenow
	 * @param WCML_Tracking_Link $tracking_link
	 */
	public function __construct( $post, $pagenow, WCML_Tracking_Link $tracking_link ) {
		$this->post          = $post;
		$this->pagenow       = $pagenow;
		$this->tracking_link = $tracking_link;
	}


	public function init_hooks() {
		add_action( 'admin_footer', array( $this, 'render' ) );
	}

	public function render() {
		if ( null === $this->post ) {
			return;
		}

		$get_post_type = get_post_type( $this->post->ID );

		if ( 'product' === $get_post_type && 'edit.php' === $this->pagenow ) {
			$this->render_product_edit_notice();
		}

		if ( isset( $_GET['taxonomy'] ) ) {
			$pos = strpos( $_GET['taxonomy'], 'pa_' );

			if ( false !== $pos  && 'edit-tags.php' === $this->pagenow ) {
				$this->render_product_taxonomy_notice( __( 'How to translate attributes', 'woocommerce-multilingual' ) );
			}

			if ( 'product_cat' === $_GET['taxonomy'] ) {
				$this->render_product_taxonomy_notice( __( 'How to translate product categories', 'woocommerce-multilingual' ) );
			}
		}
	}

	private function render_product_edit_notice() {
		$link1 = '<a href="' . admin_url( 'admin.php?page=wpml-wcml&tab=products' ) . '" >' .
		         esc_html__( 'WooCommerce Multilingual products editor', 'woocommerce-multilingual' ) . '</a>';

		$link2 = '<a href="" class="quick_product_trnsl_link" >' . esc_html__( 'Edit this product translation', 'woocommerce-multilingual' ) . '</a>';

		$content = sprintf(
			__(
				"Quick edit is disabled for product translations. It\'s recommended to use the %s for editing products translations. %s",
				'woocommerce-multilingual'
			),
			$link1,
			$link2
		);

		$quick_edit_notice = '<div id="quick_edit_notice" style="display:none;"><p>' . $content . '</p></div>';
		$quick_edit_notice_prod_link = '<input type="hidden" id="wcml_product_trnsl_link" value="' . admin_url( 'admin.php?page=wpml-wcml&tab=products&prid=' ) . '">';
		?>
		<script type="text/javascript">
            jQuery(".subsubsub").append('<?php echo esc_js( $quick_edit_notice ) ?>').append('<?php echo esc_js( $quick_edit_notice_prod_link ) ?>');
			jQuery(".quick_hide a").on('click', function () {
				jQuery(".quick_product_trnsl_link").attr('href', jQuery("#wcml_product_trnsl_link").val() + jQuery(this).closest('tr').attr('id').replace(/post-/, ''));
			});

			//lock feature for translations
			jQuery(document).on('click', '.featured a', function () {
				if (jQuery(this).closest('tr').find('.quick_hide').size() > 0) {
					return false;
				}

			});
		</script>
		<?php
	}

	private function render_product_taxonomy_notice( $msg ) {
		$href      = $this->get_documentation_link();

		$prot_link = '<span class="button" style="padding:4px;margin-top:0; float: left;">
				<img align="baseline" src="' . ICL_PLUGIN_URL . '/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> 
				<a href="' . $href . '" target="_blank" style="text-decoration: none;">' .
					 esc_html( $msg ) .
				 '<\/a>' . '<\/span><br \/><br \/>';
		?>
		<script type="text/javascript">
			jQuery("table.widefat").before('<?php echo $prot_link ?>');
		</script>
		<?php
	}

	/**
	 * @return string
	 */
	private function get_documentation_link() {
		return $this->tracking_link->generate(
			'https://wpml.org/documentation/related-projects/woocommerce-multilingual/',
			'woocommerce-multilingual',
			'documentation',
			'#3'
		);
	}
}
