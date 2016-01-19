<div class="wpml-dialog hidden" id="wcml-edit-base-slug-<?php echo $args['original_base'].'-'.$args['language'] ?>" title="<?php echo esc_attr( $args['label_name'] ) ?>">
	<div class="wcml-slug-dialog" >

			<h3 class="wpml-header-original"><?php _e('Original', 'woocommerce-multilingual'); ?>:
				<span class="wpml-title-flag">
					<img src="<?php echo $sitepress->get_flag_url( $args['source_language'] ) ?>"
						 alt="<?php echo $sitepress->get_display_language_name( $args['source_language'], 'en' ); ?>"/>
				</span>
				<strong><?php echo $sitepress->get_display_language_name( $args['source_language'], 'en' ); ?></strong>
			</h3>

			<h3 class="wpml-header-translation"><?php _e('Translation to', 'woocommerce-multilingual'); ?>:
				<span class="wpml-title-flag">
					<img src="<?php echo $sitepress->get_flag_url($args['language']) ?>"
						 alt="<?php echo $active_languages[$args['language']]['english_name'] ?>"/>
				</span>
				<strong><?php echo $active_languages[$args['language']]['english_name'] ?></strong>
			</h3>

			<div class="wpml-form-row">
				<input readonly id="base-original" class="original_value" value="<?php echo $args['original_base_value'] ?>"
					   type="text">

				<input id="base-translation"
					   class="translated_value"
					   name="base_translation" value="<?php echo $args['translated_base_value'] ?>" type="text"/>
			</div>



		<footer class="wpml-dialog-footer">
			<input type="button" class="cancel wpml-dialog-close-button wcml_cancel_base alignleft"
				   value="<?php esc_attr_e( 'Cancel', 'woocommerce-multilingual' ) ?>" />
			<input type="button" class="wpml-dialog-close-button button-primary wcml_save_base alignright"
				   value="<?php esc_attr_e( 'Save', 'woocommerce-multilingual' ) ?>" data-base="<?php echo esc_attr( $args['original_base'] ) ?>" data-language="<?php echo esc_attr( $args['language'] ) ?>"/>
		</footer>
	</div>
</div>