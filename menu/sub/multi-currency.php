<?php global $sitepress_settings;
$default_language = $sitepress->get_default_language();
?>

<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="wcml_mc_options">
	<?php wp_nonce_field( 'wcml_mc_options', 'wcml_nonce' ); ?>
	<input type="hidden" name="action" value="save-mc-options" />
	<div class="wcml-section ">
		<div class="wcml-section-header">
			<h3>
				<?php _e( 'Enable/disable', 'woocommerce-multilingual' ); ?>
			</h3>
		</div>
		<div class="wcml-section-content wcml-section-content-wide">
			<p>
				<input type="checkbox" name="multi_currency" id="multi_currency_independent"
					   value="<?php echo WCML_MULTI_CURRENCIES_INDEPENDENT ?>" <?php
				echo checked( $woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_INDEPENDENT ) ?> />
				<label for="multi_currency_independent">
					<?php _e( "Enable the multi-currency mode", 'woocommerce-multilingual' ); ?>
					&nbsp;
					<a href=" <?php echo $woocommerce_wpml->generate_tracking_link( 'http://wpml.org/documentation/related-projects/woocommerce-multilingual/multi-currency-support-woocommerce/', 'multi-currency-support-woocommerce', 'documentation' ) ?>"><?php _e( 'Learn more', 'wpl-wcml' ) ?></a>.
				</label>
			</p>
		</div>
	</div>
	<div class="wcml-section" id="multi-currency-per-language-details" <?php if ($woocommerce_wpml->settings['enable_multi_currency'] != WCML_MULTI_CURRENCIES_INDEPENDENT): ?>style="display:none"<?php endif; ?>>

		<div class="wcml-section-header">
			<h3>
				<?php _e( 'Currencies', 'woocommerce-multilingual' ); ?>
			</h3>
		</div>
		<div class="wcml-section-content wcml-section-content-wide">

			<div>
				<div class="currencies-table-content">
					<?php
					$wc_currencies    = get_woocommerce_currencies();
					$wc_currency      = get_option( 'woocommerce_currency' );
					$active_languages = $sitepress->get_active_languages();

					switch ( get_option( 'woocommerce_currency_pos' ) ) {
						case 'left':
							$positioned_price = sprintf( '%s99.99', get_woocommerce_currency_symbol( $wc_currency ) );
							break;
						case 'right':
							$positioned_price = sprintf( '99.99%s', get_woocommerce_currency_symbol( $wc_currency ) );
							break;
						case 'left_space':
							$positioned_price = sprintf( '%s 99.99', get_woocommerce_currency_symbol( $wc_currency ) );
							break;
						case 'right_space':
							$positioned_price = sprintf( '99.99 %s', get_woocommerce_currency_symbol( $wc_currency ) );
							break;
					}

					?>


					<div class="tablenav top clearfix">
						<button type="button" class="button-secondary wcml_add_currency alignright js-wpml-dialog-trigger" data-action="wcml_new_currency" data-wcml_nonce="<?php echo wp_create_nonce('wcml_edit_currency') ?>" data-width="460" data-heigh="600">
							<i class="otgs-ico-add otgs-ico-sm"></i>
							<?php _e( 'Add currency', 'woocommerce-multilingual' ); ?>
						</button>
					</div>
					<input type="hidden" id="update_currency_lang_nonce"
						   value="<?php echo wp_create_nonce( 'wcml_update_currency_lang' ); ?>"/>

					<table class="widefat currency_table" id="currency-table">
						<thead>
						<tr>
							<th class="wcml-col-currency"><?php _e( 'Currency', 'woocommerce-multilingual' ); ?></th>
							<th class="wcml-col-rate"><?php _e( 'Rate', 'woocommerce-multilingual' ); ?></th>
							<th class="wcml-col-edit"></th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td class="wcml-col-currency">
								<?php echo $wc_currencies[ $wc_currency ]; ?>
								<small><?php printf( __( ' (%s)', 'woocommerce-multilingual' ), $positioned_price ); ?></small>
							</td>
							<td class="wcml-col-rate"><?php _e( 'default', 'woocommerce-multilingual' ); ?></td>
							<td class="wcml-col-edit">&nbsp;</td>

						</tr>
						<?php
						unset( $wc_currencies[ $wc_currency ] );
						$currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
						foreach ( $currencies as $code => $currency ) :
							switch ( $currency['position'] ) {
								case 'left':
									$positioned_price = sprintf( '%s99.99', get_woocommerce_currency_symbol( $code ) );
									break;
								case 'right':
									$positioned_price = sprintf( '99.99%s', get_woocommerce_currency_symbol( $code ) );
									break;
								case 'left_space':
									$positioned_price = sprintf( '%s 99.99', get_woocommerce_currency_symbol( $code ) );
									break;
								case 'right_space':
									$positioned_price = sprintf( '99.99 %s', get_woocommerce_currency_symbol( $code ) );
									break;
							}
							?>
							<tr id="currency_row_<?php echo $code ?>">
								<td class="wcml-col-currency">
									<?php include WCML_PLUGIN_PATH . '/menu/sub/custom-currency-options.php'; ?>
									<?php echo $wc_currencies[ $code ]; ?>
									<small><?php printf( __( ' (%s)', 'woocommerce-multilingual' ), $positioned_price ); ?></small>
								</td>
								<td class="wcml-col-rate">
									<?php printf( '1 %s = %s %s', $wc_currency, $currency['rate'], $code ); ?>
								</td>

								<td class="wcml-col-edit">
									<a
										href="#" title="<?php esc_attr( _e( 'Edit', 'woocommerce-multilingual' ) ); ?>" class="edit_currency js-wpml-dialog-trigger"
										data-currency="<?php echo $code ?>" data-content="wcml_currency_options_<?php echo $code ?>"  id="wcml_currency_options_<?php echo $code ?>"
										data-height="530" data-width="450">
										<i class="otgs-ico-edit"
										   title="<?php esc_attr( _e( 'Edit', 'woocommerce-multilingual' ) ); ?>"></i>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr class="default_currency">
							<td colspan="3">
								<?php _e( 'Default currency', 'woocommerce-multilingual' ); ?>
								<i class="wcml-tip otgs-ico-help"
								   data-tip="<?php _e( 'Switch to this currency when switching language in the front-end', 'woocommerce-multilingual' ); ?>"></i>
							</td>
						</tr>
						</tbody>
					</table>

					<div class="currency_wrap">
						<div class="currency_inner">
							<table class="widefat currency_lang_table" id="currency-lang-table">
								<thead>
								<tr>
									<?php foreach ( $active_languages as $language ): ?>
										<th>
											<img src="<?php echo $sitepress->get_flag_url( $language['code'] ) ?>"
												 width="18" height="12"/>
										</th>
									<?php endforeach; ?>
								</tr>
								</thead>
								<tbody>
								<tr>
									<?php foreach ( $active_languages as $language ): ?>
										<td class="currency_languages">
											<ul>
												<li <?php echo $woocommerce_wpml->settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] == 0 ? 'class="on"' : ''; ?> >
													<a class="off_btn otgs-ico-no"
													   data-language="<?php echo $language['code']; ?>"
													   data-currency="<?php echo $wc_currency; ?>"></a>
												</li>
												<li <?php echo $woocommerce_wpml->settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] == 1 ? 'class="on"' : ''; ?> >
													<a class="on_btn otgs-ico-yes"
													   data-language="<?php echo $language['code']; ?>"
													   data-currency="<?php echo $wc_currency ?>"></a>
												</li>
											</ul>
										</td>
									<?php endforeach; ?>
								</tr>
								<?php foreach ( $currencies as $code => $currency ) : ?>
									<tr id="currency_row_langs_<?php echo $code ?>">
										<?php foreach ( $active_languages as $language ): ?>
											<td class="currency_languages">

												<ul>
													<li <?php echo $currency['languages'][ $language['code'] ] == 0 ? 'class="on"' : ''; ?> >
														<a class="off_btn otgs-ico-no"
														   data-language="<?php echo $language['code']; ?>"
														   data-currency="<?php echo $code; ?>"></a>
													</li>
													<li <?php echo $currency['languages'][ $language['code'] ] == 1 ? 'class="on"' : ''; ?> >
														<a class="on_btn otgs-ico-yes"
														   data-language="<?php echo $language['code']; ?>"
														   data-currency="<?php echo $code; ?>"></a>
													</li>
												</ul>

											</td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
								<tr class="default_currency">
									<?php foreach ( $active_languages as $language ): ?>
										<td align="center">
											<select rel="<?php echo $language['code']; ?>">
												<option
													value="0" <?php selected( '0', $woocommerce_wpml->settings['default_currencies'][ $language['code'] ] ); ?>><?php _e( 'Keep', 'woocommerce-multilingual' ); ?></option>
												<?php if ( $woocommerce_wpml->settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] == 1 ): ?>
													<option
														value="<?php echo $wc_currency; ?>" <?php selected( $wc_currency, $woocommerce_wpml->settings['default_currencies'][ $language['code'] ] ); ?>><?php echo $wc_currency; ?></option>
												<?php endif; ?>
												<?php foreach ( $currencies as $code2 => $currency2 ): ?>
													<?php if ( $woocommerce_wpml->settings['currency_options'][ $code2 ]['languages'][ $language['code'] ] == 1 ): ?>
														<option
															value="<?php echo $code2; ?>" <?php selected( $code2, $woocommerce_wpml->settings['default_currencies'][ $language['code'] ] ); ?>><?php echo $code2; ?></option>
													<?php endif; ?>
												<?php endforeach; ?>
											</select>
										</td>
									<?php endforeach; ?>
								</tr>
								</tbody>
							</table>
							<input type="hidden" id="wcml_update_default_currency_nonce"
								   value="<?php echo wp_create_nonce( 'wcml_update_default_currency' ); ?>"/>

						</div>
					</div>
					<table class="widefat currency_delete_table" id="currency-delete-table">
						<thead>
						<tr>
							<th></th>
						</tr>
						</thead>
						<tbody>
						<tr class="currency_default">
							<td class="wcml-col-delete">
								<a
									title="<?php esc_attr( _e( 'Delete', 'woocommerce-multilingual' ) ); ?>"
									class="delete_currency" data-currency="<?php echo $code ?>">
									<i class="otgs-ico-delete"
									   title="<?php esc_attr( _e( 'Delete', 'woocommerce-multilingual' ) ); ?>"></i>
								</a>
							</td>
						</tr>


						<?php foreach ( $currencies as $code => $currency ) : ?>
							<tr>
								<td class="wcml-col-delete">
									<a
										title="<?php esc_attr( _e( 'Delete', 'woocommerce-multilingual' ) ); ?>"
										class="delete_currency" data-currency="<?php echo $code ?>">
										<i class="otgs-ico-delete"
										   title="<?php esc_attr( _e( 'Delete', 'woocommerce-multilingual' ) ); ?>"></i>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr class="default_currency">
							<td></td>
						</tr>
						</tbody>
					</table>

				</div>

				<?php // backward compatibility ?>
				<?php
				$posts = $wpdb->get_results( $wpdb->prepare( "
					SELECT m.post_id, m.meta_value, p.post_title
					FROM {$wpdb->postmeta} m
						JOIN {$wpdb->posts} p ON p.ID = m.post_id
						JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
					WHERE m.meta_key='_custom_conversion_rate' AND t.language_code = %s
					ORDER BY m.post_id desc
				", $default_language ) );

				if ( $posts ) {
					echo "<script>
					function wcml_remove_custom_rates(post_id, el){
						jQuery.ajax({
							type: 'post',
							dataType: 'json',
							url: ajaxurl,
							data: {action: 'legacy_remove_custom_rates', 'post_id': post_id},
							success: function(){
								el.parent().parent().fadeOut(function(){ jQuery(this).remove()});
							}
						})
						return false;
					}";
					echo '</script>';
					echo '<p>' . __( 'Products using custom currency rates as they were migrated from the previous versions - option to support different prices per language', 'woocommerce-multilingual' ) . '</p>';
					echo '<form method="post" id="wcml_custom_exchange_rates">';
					echo '<input type="hidden" name="action" value="legacy_update_custom_rates">';
					echo '<table class="widefat currency_table" >';
					echo '<thead>';
					echo '<tr>';
					echo '<th rowspan="2">' . __( 'Product', 'woocommerce-multilingual' ) . '</th>';
					echo '<th colspan="' . count( $currencies ) . '">_price</th>';
					echo '<th colspan="' . count( $currencies ) . '">_sale_price</th>';
					echo '<th rowspan="2">&nbsp;</th>';
					echo '</tr>';
					echo '<tr>';
					foreach ( $currencies as $code => $currency ) {
						echo '<th>' . $code . '</th>';
					}
					foreach ( $currencies as $code => $currency ) {
						echo '<th>' . $code . '</th>';
					}
					echo '</tr>';
					echo '</thead>';
					echo '<tbody>';
					foreach ( $posts as $post ) {
						$rates = unserialize( $post->meta_value );
						echo '<tr>';
						echo '<td><a href="' . get_edit_post_link( $post->post_id ) . '">' . apply_filters( 'the_title', $post->post_title ) . '</a></td>';

						foreach ( $currencies as $code => $currency ) {
							echo '<td>';
							if ( isset( $rates['_price'][ $code ] ) ) {
								echo '<input name="posts[' . $post->post_id . '][_price][' . $code . ']" size="3" value="' . round( $rates['_price'][ $code ], 3 ) . '">';
							} else {
								_e( 'n/a', 'woocommerce-multilingual' );
							}
							echo '</td>';
						}

						foreach ( $currencies as $code => $currency ) {
							echo '<td>';
							if ( isset( $rates['_sale_price'][ $code ] ) ) {
								echo '<input name="posts[' . $post->post_id . '][_sale_price][' . $code . ']" size="3" value="' . round( $rates['_sale_price'][ $code ], 3 ) . '">';
							} else {
								_e( 'n/a', 'woocommerce-multilingual' );
							}
							echo '</td>';
						}

						echo '<td align="right"><a href="#" onclick=" if(confirm(\'' . esc_js( __( 'Are you sure?', 'woocommerce-multilingual' ) ) . '\')) wcml_remove_custom_rates(' . $post->post_id . ', jQuery(this));return false;"><i class="otgs-ico-delete" title="' . __( 'Delete', 'woocommerce-multilingual' ) . '"></i></a></td>';
						echo '<tr>';

					}
					echo '</tbody>';
					echo '</table>';
					echo '<p class="button-wrap"><input class="button-secondary" type="submit" value="' . esc_attr__( 'Update', 'woocommerce-multilingual' ) . '" /></p>';
					echo '</form>';


				}
				?>
				<ul id="display_custom_prices_select">
					<li>
						<input type="checkbox" name="display_custom_prices" id="display_custom_prices"
							   value="1" <?php echo checked( 1, $woocommerce_wpml->settings['display_custom_prices'] ) ?> >
						<label
							for="display_custom_prices"><?php _e( 'Show only products with custom prices in secondary currencies', 'woocommerce-multilingual' ); ?></label>
						<i class="otgs-ico-help wcml-tip"
						   data-tip="<?php _e( 'When this option is on, when you switch to a secondary currency on the front end, only the products with custom prices in that currency are being displayed. Products with prices determined based on the exchange rate are hidden.', 'woocommerce-multilingual' ) ?>"></i>
					</li>
				</ul>

			</div>
		</div>
			<!-- .wcml-section-content -->

	</div> <!-- .wcml-section -->
	<div class="wcml-section">
		<?php include WCML_PLUGIN_PATH . '/menu/sub/currency-switcher-options.php'; ?>
	</div>
	<input type="hidden" id="wcml_warn_message"
		   value="<?php esc_attr_e( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce-multilingual' ); ?>"/>
	<input type="hidden" id="wcml_warn_disable_language_massage"
		   value="<?php esc_attr_e( 'At least one currency must be enabled for this language!', 'woocommerce-multilingual' ); ?>"/>

	<p class="wpml-margin-top-sm">
		<input type='submit' value='<?php esc_attr( _e( 'Save changes', 'woocommerce-multilingual' ) ); ?>' class='button-primary'/>
	</p>

</form>