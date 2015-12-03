
<div class="wpml-dialog hidden" id="wcml_currency_options_<?php echo $args['currency_code'] ?>">

	<div class="wcml_currency_options wcml-co-dialog">

		<form id="wcml_currency_options_form_<?php echo $args['currency_code'] ?>" method="post" action="">

			<header class="wpml-dialog-header">
				<h3>
					<?php echo $args['title'] ?>
				</h3>
				<i class="otgs-ico-close wpml-dialog-close-button"></i>
			</header>

			<div class="wpml-dialog-body">

				<?php if( empty($args['currency_code']) ): ?>
				<div class="wpml-form-row currency_code">
					<label for="wcml_currency_options_code_<?php echo $args['currency_code'] ?>"><?php _e( 'Select currency', 'woocommerce-multilingual' ) ?></label>
					<select name="currency_options[code]" id="wcml_currency_options_code_<?php echo $args['currency_code'] ?>">
						<?php foreach($args['wc_currencies'] as $currency_code => $currency_name): ?>
							<?php if( empty( $args['currencies'][$currency_code]) && $currency_code != $args['default_currency'] ): ?>
								<option value="<?php echo $currency_code; ?>" <?php if( isset( $args['currency_code'] ) ) selected($currency_code,  $args['currency_code'] , true) ?> ><?php echo $currency_name; ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</div>

				<?php else: ?>
					<input type="hidden" name="currency_options[code]" value="<?php echo $args['currency_code'] ?>" />
				<?php endif; ?>

				<?php
					$cur_cur = empty($args['currency_code']) ? current( array_diff( array_keys( $args['wc_currencies'] ), array_keys( $args['currencies'] ) ) ) : $args['currency_code'];
				?>

				<div class="wpml-form-row wcml-co-exchange-rate">
					<label for="wcml_currency_options_rate_<?php echo $args['currency_code'] ?>"><?php _e( 'Exchange Rate', 'woocommerce-multilingual' ) ?></label>
					<div class="wcml-co-set-rate">
						<?php printf( "1 %s = %s %s", $args['default_currency'], '<input name="currency_options[rate]" size="5" type="number" class="ext_rate" step="0.01" value="' .
							$args['currency']['rate'] . '" data-message="' . __( 'Only numeric', 'woocommerce-multilingual' ) .
							'" id="wcml_currency_options_rate_' . $args['currency_code'] .'" />',
							'<span class="this-currency">' . $cur_cur . '</span>' ) ?>
						<?php if( isset($args['currency']['updated']) ): ?>
						<small>
							<i><?php printf( __( 'Set on %s', 'woocommerce-multilingual' ), date( 'F j, Y, H:i', strtotime( $args['currency']['updated'] ) ) ) ; ?></i>
						</small>
						<?php endif; ?>
					</div>
				</div>

				<hr>

				<div class="wpml-form-row wcml-co-preview">
					<label><strong><?php _e( 'Currency Preview', 'woocommerce-multilingual' ) ?></strong></label>
					<p class="wcml-co-preview-value">
						<?php
						$price = apply_filters( 'formatted_woocommerce_price', number_format( '1234.56', 2, '.', ',' ), '1234.56', 2, '.', ',' );
						printf( '%1$s%2$s', get_woocommerce_currency_symbol($cur_cur), $price );
						?>
					</p>
				</div>

				<div class="wpml-form-row">
					<label for="wcml_currency_options_position_<?php echo $args['currency_code'] ?>"><?php _e( 'Currency Position', 'woocommerce-multilingual' ) ?></label>
					<select class="currency_option_position" name="currency_options[position]" id="wcml_currency_options_position_<?php echo $args['currency_code'] ?>">
						<option value="left" <?php selected( 'left', $args['currency']['position'], 1 ); ?>>
							<?php _e( 'Left', 'woocommerce-multilingual' ) ?>
						</option>
						<option value="right" <?php selected( 'right', $args['currency']['position'], 1 ); ?>>
							<?php _e( 'Right', 'woocommerce-multilingual' ) ?>
						</option>
						<option value="left_space" <?php selected( 'left_space', $args['currency']['position'], 1 ); ?>>
							<?php _e( 'Left with space', 'woocommerce-multilingual' ) ?>
						</option>
						<option value="right_space" <?php selected( 'right_space', $args['currency']['position'], 1 ); ?>>
							<?php _e( 'Right with space', 'woocommerce-multilingual' ) ?>
						</option>
					</select>
				</div>

				<div class="wpml-form-row">
					<label for="wcml_currency_options_thousand_<?php echo $args['currency_code'] ?>"><?php _e( 'Thousand Separator', 'woocommerce-multilingual' ) ?></label>
					<input name="currency_options[thousand_sep]" type="text"
						   class="currency_option_input currency_option_thousand_sep" value="<?php echo esc_attr( $args['currency']['thousand_sep'] ) ?>"
						   id="wcml_currency_options_thousand_<?php echo $args['currency_code'] ?>" />
				</div>
				<div class="wpml-form-row">
					<label for="wcml_currency_options_decimal_<?php echo $args['currency_code'] ?>"><?php _e( 'Decimal Separator', 'woocommerce-multilingual' ) ?></label>
					<input name="currency_options[decimal_sep]" type="text"
						   class="currency_option_input currency_option_decimal_sep" value="<?php echo esc_attr( $args['currency']['decimal_sep'] ) ?>"
						   id="wcml_currency_options_decimal_<?php echo $args['currency_code'] ?>" />
				</div>
				<div class="wpml-form-row">
					<label id="wcml_currency_options_decimals_<?php echo $args['currency_code'] ?>"><?php _e( 'Number of Decimals', 'woocommerce-multilingual' ) ?></label>
					<input name="currency_options[num_decimals]" type="number" class="currency_option_decimals"
						   value="<?php echo esc_attr( $args['currency']['num_decimals'] ) ?>" min="0" step="1"
						   data-message="<?php _e( 'Only numeric', 'woocommerce-multilingual' ); ?>"
						   for="wcml_currency_options_decimals_<?php echo $args['currency_code'] ?>" />
				</div>

				<hr/>

				<div class="wpml-form-row">
					<label for="wcml_currency_options_rounding_<?php echo $args['currency_code'] ?>"><?php _e( 'Rounding to the nearest integer', 'woocommerce-multilingual' ) ?></label>
					<select name="currency_options[rounding]" id="wcml_currency_options_rounding_<?php echo $args['currency_code'] ?>">
						<option
							value="disabled" <?php selected( 'disabled', $args['currency']['rounding'] ) ?> ><?php _e( 'disabled', 'woocommerce-multilingual' ) ?></option>
						<option
							value="up" <?php selected( 'up', $args['currency']['rounding'] ) ?>><?php _e( 'up', 'woocommerce-multilingual' ) ?></option>
						<option
							value="down" <?php selected( 'down', $args['currency']['rounding'] ) ?>><?php _e( 'down', 'woocommerce-multilingual' ) ?></option>
						<option
							value="down" <?php selected( 'nearest', $args['currency']['rounding'] ) ?>><?php _e( 'nearest', 'woocommerce-multilingual' ) ?></option>
					</select>
				</div>
				<div class="wpml-form-row">
					<label for="wcml_currency_options_increment_<?php echo $args['currency_code'] ?>"><?php _e( 'Increment for nearest integer', 'woocommerce-multilingual' ) ?></label>
					<select name="currency_options[rounding_increment]" id="wcml_currency_options_increment_<?php echo $args['currency_code'] ?>">
						<option value="1" <?php selected( '1', $args['currency']['rounding_increment'] ) ?> >1</option>
						<option value="10" <?php selected( '10', $args['currency']['rounding_increment'] ) ?>>10</option>
						<option value="100" <?php selected( '100', $args['currency']['rounding_increment'] ) ?>>100</option>
						<option value="1000" <?php selected( '1000', $args['currency']['rounding_increment'] ) ?>>1000</option>
					</select>
				</div>
				<div class="wpml-form-row">
					<label for="wcml_currency_options_subtract_<?php echo $args['currency_code'] ?>"><?php _e( 'Autosubtract amount', 'woocommerce-multilingual' ) ?></label>

					<input name="currency_options[auto_subtract]" class="abstract_amount"
						   value="<?php echo $args['currency']['auto_subtract'] ?>" type="number" value="0"
						   data-message="<?php _e( 'Only numeric', 'woocommerce-multilingual' ); ?>"
						   id="wcml_currency_options_subtract_<?php echo $args['currency_code'] ?>"/>
				</div>

			</div>

			<footer class="wpml-dialog-footer">
				<input type="button" class="button-secondary wpml-dialog-close-button alignleft"
					   value="<?php esc_attr_e( 'Cancel', 'woocommerce-multilingual' ) ?>" data-currency="<?php echo $cur_cur ?>"/>&nbsp;
				<input type="submit" class="wpml-dialog-close-button button-primary currency_options_save alignright"
					   value="<?php esc_attr_e( 'Save', 'woocommerce-multilingual' ) ?>" data-currency="<?php echo $cur_cur; ?>" data-stay="1" />
			</footer>

		</form>

	</div>

</div>
