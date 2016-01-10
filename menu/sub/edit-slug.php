<div class="wcml-base-dialog wpml-dialog">
	<div class="wcml-base-dialog-inner" id="wcml-edit-base">
		<header class="wpml-dialog-header">
			<h3>
				<?php echo $label_name; ?>
			</h3>
			<i class="otgs-ico-close wpml-dialog-close-button wcml_cancel_base"></i>
		</header>


		<div class="wpml-dialog-body">
			<h3 class="wpml-header-original"><?php _e('Original', 'woocommerce-multilingual'); ?>:
				<span class="wpml-title-flag">
					<img src="<?php echo $sitepress->get_flag_url( $source_language ) ?>"
						 alt="<?php echo $sitepress->get_display_language_name( $source_language, 'en' ); ?>"/>
				</span>
				<strong><?php echo $sitepress->get_display_language_name( $source_language, 'en' ); ?></strong>
			</h3>

			<h3 class="wpml-header-translation"><?php _e('Translation to', 'woocommerce-multilingual'); ?>:
				<span class="wpml-title-flag">
					<img src="<?php echo $sitepress->get_flag_url($language) ?>"
						 alt="<?php echo $active_languages[$language]['english_name'] ?>"/>
				</span>
				<strong><?php echo $active_languages[$language]['english_name'] ?></strong>
			</h3>

			<div class="wpml-form-row">
				<input readonly id="base-original" class="original_value" value="<?php echo $original_base_value ?>"
					   type="text">

				<input id="base-translation"
					   class="translated_value"
					   name="base_translation" value="<?php echo $translated_base_value ?>" type="text"/>
			</div>

		</div>


		<footer class="wpml-dialog-footer">
			<input type="button" class="cancel wpml-dialog-close-button wcml_cancel_base alignleft"
				   value="<?php esc_attr_e( 'Cancel', 'woocommerce-multilingual' ) ?>" />
			<input type="button" class="button-primary wcml_save_base alignright"
				   value="<?php esc_attr_e( 'Save', 'woocommerce-multilingual' ) ?>" data-base="<?php echo $original_base ?>" data-language="<?php echo $language ?>"/>
			<input type="hidden" id="wcml_update_base_nonce" value="<?php echo wp_create_nonce( 'wcml_update_base_translation' ); ?>"/>
		</footer>
	</div>
</div>
<div class="wcml_fade_block"></div>