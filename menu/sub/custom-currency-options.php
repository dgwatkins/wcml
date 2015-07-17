<?php
$currency_name   = $wc_currencies[ $code ];
$currency_symbol = get_woocommerce_currency_symbol( $code );
?>

<div class="wpml-dialog wcml-co-dialog" id="wcml_currency_options_<?php echo $code ?>">
	<header class="wpml-dialog-header">
		<h3>
			<?php printf( __( 'Currency options for %s', 'wpml-wcml' ), '<strong>' . $currency_name . ' (' . $currency_symbol . ')</strong>' ) ?>
		</h3>
		<?php //TODO Sergey: close Dialog on wpml-dialog-close (not on icon classes) ?>
		<i class="otgs-ico-close wpml-dialog-close"></i>
	</header>


	<div class="wpml-dialog-body">

		<div class="wpml-form-row wcml-co-exchange-rate">
			<label><?php _e( 'Exchange Rate', 'wpml-wcml' ) ?></label>

			<p class="wcml-co-set-rate">
				<?php printf( "1 %s = %s %s", $wc_currency, '<input name="currency_options[' . $code . '][rate]" type="number" class="ext_rate" step="0.01" value="' . $currency['rate'] . '" data-message="' . __( 'Only numeric', 'wpml-wcml' ) . '" />', $code ) ?>
				<small>
					<i><?php printf( __( 'Set on %s', 'wpml-wcml' ), date( 'F j, Y, H:i', strtotime( isset( $currency['updated'] ) ? $currency['updated'] : time() ) ) ); ?></i>
				</small>
			</p>

		</div>


		<hr/>

		<div class="wpml-form-row wcml-co-preview">
			<label><strong><?php _e( 'Currency Preview', 'wpml-wcml' ) ?></strong></label>

			<p class=" wcml-co-preview-value">
				<?php //TODO Sergey: Make it work ?>
				$ 2,345.39
			</p>
		</div>

		<hr/>
		<div class="wpml-form-row">
			<label for="currency_options[<?php echo $code ?>"><?php _e( 'Currency Position', 'wpml-wcml' ) ?></label>
			<?php //TODO Sergey: Is $post_str[''] needed for anything? ?>
			<select name="currency_options[<?php echo $code ?>][position]">
				<option value="left" <?php selected( 'left', $currency['position'], 1 ); ?>>
					<?php echo $post_str['left'] = __( 'Left', 'wpml-wcml' ) ?>
				</option>
				<option value="right" <?php selected( 'right', $currency['position'], 1 ); ?>>
					<?php
					echo $post_str['right'] = __( 'Right', 'wpml-wcml' ) ?>
				</option>
				<option value="left_space" <?php selected( 'left_space', $currency['position'], 1 ); ?>>
					<?php
					echo $post_str['left_space'] = __( 'Left with space', 'wpml-wcml' ) ?>
				</option>
				<option value="right_space" <?php selected( 'right_space', $currency['position'], 1 ); ?>>
					<?php
					echo $post_str['right_space'] = __( 'Right with space', 'wpml-wcml' ) ?>
				</option>
			</select>

		</div>
		<div class="wpml-form-row">
			<label
				for="currency_options[<?php echo $code ?>][thousand_sep]"><?php _e( 'Thousand Separator', 'wpml-wcml' ) ?></label>
			<input name="currency_options[<?php echo $code ?>][thousand_sep]" type="text"
			       class="currency_option_input" value="<?php echo esc_attr( $currency['thousand_sep'] ) ?>"/>
		</div>
		<div class="wpml-form-row">
			<label
				for="currency_options[<?php echo $code ?>][decimal_sep]"><?php _e( 'Decimal Separator', 'wpml-wcml' ) ?></label>
			<input name="currency_options[<?php echo $code ?>][decimal_sep]" type="text"
			       class="currency_option_input" value="<?php echo esc_attr( $currency['decimal_sep'] ) ?>"/>
		</div>
		<div class="wpml-form-row">
			<label
				for="currency_options[<?php echo $code ?>][num_decimals]"><?php _e( 'Number of Decimals', 'wpml-wcml' ) ?></label>
			<input name="currency_options[<?php echo $code ?>][num_decimals]" type="number" class="decimals_number"
			       value="<?php echo esc_attr( $currency['num_decimals'] ) ?>" min="0" step="1"
			       data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>"/>
		</div>


		<hr/>

		<div class="wpml-form-row">
			<label
				for="currency_options[<?php echo $code ?>][rounding]"><?php _e( 'Rounding to the nearest integer', 'wpml-wcml' ) ?></label>
			<select name="currency_options[<?php echo $code ?>][rounding]">
				<option
					value="disabled" <?php selected( 'disabled', $currency['rounding'] ) ?> ><?php _e( 'disabled', 'wpml-wcml' ) ?></option>
				<option
					value="up" <?php selected( 'up', $currency['rounding'] ) ?>><?php _e( 'up', 'wpml-wcml' ) ?></option>
				<option
					value="down" <?php selected( 'down', $currency['rounding'] ) ?>><?php _e( 'down', 'wpml-wcml' ) ?></option>
				<option
					value="down" <?php selected( 'nearest', $currency['rounding'] ) ?>><?php _e( 'nearest', 'wpml-wcml' ) ?></option>
			</select>
		</div>
		<div class="wpml-form-row">
			<label
				for="currency_options[<?php echo $code ?>][rounding_increment]"><?php _e( 'Increment for nearest integer', 'wpml-wcml' ) ?></label>
			<select name="currency_options[<?php echo $code ?>][rounding_increment]">
				<option value="1" <?php selected( '1', $currency['rounding_increment'] ) ?> >1</option>
				<option value="10" <?php selected( '10', $currency['rounding_increment'] ) ?>>10</option>
				<option value="100" <?php selected( '100', $currency['rounding_increment'] ) ?>>100</option>
				<option value="1000" <?php selected( '1000', $currency['rounding_increment'] ) ?>>1000</option>
			</select>
		</div>
		<div class="wpml-form-row">
			<label
				for="currency_options[<?php echo $code ?>][auto_subtract]"><?php _e( 'Autosubtract amount', 'wpml-wcml' ) ?></label>

			<input name="currency_options[<?php echo $code ?>][auto_subtract]" class="abstract_amount"
			       value="<?php echo $currency['auto_subtract'] ?>" type="number" value="0"
			       data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>"/>
		</div>
	</div>


	<footer class="wpml-dialog-footer">
		<input type="button" class="button-secondary currency_options_cancel alignleft"
		       value="<?php esc_attr_e( 'Cancel', 'wpml-wcml' ) ?>" data-currency="<?php echo $code ?>"/>&nbsp;
		<input type="submit" class="button-primary currency_options_save alignright"
		       value="<?php esc_attr_e( 'Save', 'wpml-wcml' ) ?>" data-currency="<?php echo $code ?>"/>
		<input type="hidden" id="save_currency_nonce" value="<?php echo wp_create_nonce( 'save_currency' ); ?>"/>
	</footer>
</div>