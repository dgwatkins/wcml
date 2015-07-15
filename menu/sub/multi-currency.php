<?php global $sitepress_settings;
$default_language = $sitepress->get_default_language();
?>

<div class="wcml-section">

	<div class="wcml-section-header">
		<h3>
			<?php _e( 'Manage Currencies', 'wpml-wcml' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php _e( 'This will let you enable the multi-currency mode where users can see prices according to their currency preference and configured exchange rate.', 'wpml-wcml' ) ?>"></i>
		</h3>
	</div>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="wcml_mc_options">
		<div class="wcml-section-content">
			<?php wp_nonce_field( 'wcml_mc_options', 'wcml_nonce' ); ?>

			<ul id="wcml_mc_options_block">

				<li>
					<ul id="multi_currency_option_select">
						<li>
							<input type="radio" name="multi_currency" id="multi_currency_disabled"
							       value="<?php echo WCML_MULTI_CURRENCIES_DISABLED ?>" <?php
							echo checked( $woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_DISABLED ) ?> />
							<label
								for="multi_currency_disabled"><?php _e( "No multi-currency", 'wpml-wcml' ); ?></label>
						</li>
						<li>
							<input type="radio" name="multi_currency" id="multi_currency_independent"
							       value="<?php echo WCML_MULTI_CURRENCIES_INDEPENDENT ?>" <?php
							echo checked( $woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_INDEPENDENT ) ?> />
							<label for="multi_currency_independent">
								<?php _e( "Multi-currency (independent of languages)", 'wpml-wcml' ); ?>
								&nbsp;
								<a href=" <?php echo $woocommerce_wpml->generate_tracking_link( 'http://wpml.org/documentation/related-projects/woocommerce-multilingual/multi-currency-support-woocommerce/', 'multi-currency-support-woocommerce', 'documentation' ) ?>"><?php _e( 'Learn more', 'wpl-wcml' ) ?></a>.
							</label>
						</li>
					</ul>
				</li>
			</ul>


			<div id="multi-currency-per-language-details"
			     <?php if ($woocommerce_wpml->settings['enable_multi_currency'] != WCML_MULTI_CURRENCIES_INDEPENDENT): ?>style="display:none"<?php endif; ?>>
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
						<button type="button" class="button-secondary alignright">
							<i class="otgs-ico-add otgs-ico-sm"></i>
							<?php _e( 'Add currency', 'wpml-wcml' ); ?>
						</button>
					</div>
					<input type="hidden" id="update_currency_lang_nonce"
					       value="<?php echo wp_create_nonce( 'wcml_update_currency_lang' ); ?>"/>


					<table class="widefat wcml-currency-table" id="currency-table">
						<thead>
						<tr>
							<th class="wcml-col-currency"><?php _e( 'Currency', 'wpml-wcml' ); ?></th>
							<th class="wcml-col-rate"><?php _e( 'Rate', 'wpml-wcml' ); ?></th>
							<th class="wcml-col-edit"></th>

							<?php foreach ( $active_languages as $language ): ?>
								<th class="wcml-col-currency-switcher">
									<img src="<?php echo $sitepress->get_flag_url( $language['code'] ) ?>" width="18"
									     height="12"/>
								</th>
							<?php endforeach; ?>


							<th class="wcml-col-delete"></th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td class="wcml-col-currency">
								<?php echo $wc_currencies[ $wc_currency ]; ?>
								<small><?php printf( __( ' (%s)', 'wpml-wcml' ), $positioned_price ); ?></small>
							</td>
							<td class="wcml-col-rate">
								<?php _e( 'default', 'wpml-wcml' ); ?>
							</td>
							<td class="wcml-col-edit">
								<i class="otgs-ico-edit wcml-currency-edit"></i>
							</td>
							<?php foreach ( $active_languages as $language ): ?>
								<td class="wcml-col-currency-switcher">
									<i class="otgs-ico-yes wcml-currency-switch"></i>
								</td>
							<?php endforeach; ?>
							<td class="wcml-col-delete">
								<i class="otgs-ico-delete wcml-currency-delete"></i>
							</td>
						</tr>
						</tbody>
						<tfoot>
						<td colspan="3">
							<?php _e( "Default currency", 'wpml-wcml' ); ?>
							<i class="otgs-ico-help wcml-tip"
							   data-tip="<?php _e( 'Switch to this currency when switching language on the front-end.', 'wpml-wcml' ) ?>"></i>
						</td>

						<?php foreach ( $active_languages as $language ): ?>
							<td class="wcml-col-currency-switcher">
								<select rel="<?php echo $language['code']; ?>">
									<option
										value="0" <?php selected( '0', $woocommerce_wpml->settings['default_currencies'][ $language['code'] ] ); ?>><?php _e( 'Keep', 'wpml-wcml' ); ?></option>
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
						<td></td>
						</tfoot>
					</table>


					<table class="widefat currency_table" id="currency-table">
						<thead>
						<tr>
							<th><?php _e( 'Currency', 'wpml-wcml' ); ?></th>
							<th></th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td class="currency_code">
								<span
									class="code_val"><?php echo $wc_currencies[ $wc_currency ]; ?><?php printf( __( ' (%s)', 'wpml-wcml' ), $positioned_price ); ?></span>

								<div class="currency_value"><span><?php _e( 'default', 'wpml-wcml' ); ?></span></div>
							</td>
							<td class="currency-actions"></td>

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
								<td class="currency_code">
									<?php include WCML_PLUGIN_PATH . '/menu/sub/custom-currency-options.php'; ?>
									<span
										class="code_val"><?php echo $wc_currencies[ $code ]; ?><?php printf( __( ' (%s)', 'wpml-wcml' ), $positioned_price ); ?></span>

									<div class="currency_value">
										<span><?php printf( '1 %s = %s %s', $wc_currency, $currency['rate'], $code ); ?></span>
									</div>

								</td>

								<td class="currency-actions">
									<div class="currency_action_update">
										<a href="javascript:void(0);"
										   title="<?php esc_attr( _e( 'Edit', 'wpml-wcml' ) ); ?>" class="edit_currency"
										   data-currency="<?php echo $code ?>">
											<i class="icon-edit"
											   title="<?php esc_attr( _e( 'Edit', 'wpml-wcml' ) ); ?>"></i>
										</a>
									</div>
									<div class="currency_action_delete">
										<a href="javascript:void(0);"
										   title="<?php esc_attr( _e( 'Delete', 'wpml-wcml' ) ); ?>"
										   class="delete_currency" data-currency="<?php echo $code ?>">
											<i class="icon-trash"
											   title="<?php esc_attr( _e( 'Delete', 'wpml-wcml' ) ); ?>"></i>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr class="default_currency">
							<td colspan="2">
								<?php _e( 'Default currency', 'wpml-wcml' ); ?>
								<i class="wcml-tip otgs-ico-help"
								   data-tip="<?php _e( 'Switch to this currency when switching language in the front-end', 'wpml-wcml' ); ?>"></i>
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
											<div class="wcml_onof_buttons">
												<ul>
													<li <?php echo $woocommerce_wpml->settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] == 0 ? 'class="on"' : ''; ?> >
														<a class="off_btn" href="javascript:void(0);"
														   data-language="<?php echo $language['code']; ?>"
														   data-currency="<?php echo $wc_currency; ?>"><?php _e( 'OFF', 'wpml-wcml' ); ?></a>
													</li>
													<li <?php echo $woocommerce_wpml->settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] == 1 ? 'class="on"' : ''; ?> >
														<a class="on_btn" href="javascript:void(0);"
														   data-language="<?php echo $language['code']; ?>"
														   data-currency="<?php echo $wc_currency ?>"><?php _e( 'ON', 'wpml-wcml' ); ?></a>
													</li>
												</ul>
											</div>
										</td>
									<?php endforeach; ?>
								</tr>
								<?php foreach ( $currencies as $code => $currency ) : ?>
									<tr id="currency_row_langs_<?php echo $code ?>">
										<?php foreach ( $active_languages as $language ): ?>
											<td class="currency_languages">
												<div class="wcml_onof_buttons">
													<ul>
														<li <?php echo $currency['languages'][ $language['code'] ] == 0 ? 'class="on"' : ''; ?> >
															<a class="off_btn" href="javascript:void(0);"
															   data-language="<?php echo $language['code']; ?>"
															   data-currency="<?php echo $code; ?>"><?php _e( 'OFF', 'wpml-wcml' ); ?></a>
														</li>
														<li <?php echo $currency['languages'][ $language['code'] ] == 1 ? 'class="on"' : ''; ?> >
															<a class="on_btn" href="javascript:void(0);"
															   data-language="<?php echo $language['code']; ?>"
															   data-currency="<?php echo $code; ?>"><?php _e( 'ON', 'wpml-wcml' ); ?></a>
														</li>
													</ul>
												</div>
											</td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
								<tr class="default_currency">
									<?php foreach ( $active_languages as $language ): ?>
										<td class="currency_languages">
											<select rel="<?php echo $language['code']; ?>">
												<option
													value="0" <?php selected( '0', $woocommerce_wpml->settings['default_currencies'][ $language['code'] ] ); ?>><?php _e( 'Keep', 'wpml-wcml' ); ?></option>
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

					<?php // this is a template for scripts.js : jQuery('.wcml_add_currency button').click(function(); ?>
					<table class="hidden js-table-row-wrapper">
						<tr class="edit-mode js-table-row">
							<td class="currency_code" data-message="<?php _e( 'Please fill field', 'wpml-wcml' ); ?>">
								<span class="code_val"></span>
								<select name="code" style="display:block">
									<?php foreach ( $wc_currencies as $wc_code => $currency_name ): ?>
										<?php if ( empty( $currencies[ $wc_code ] ) ): ?>
											<option
												value="<?php echo $wc_code; ?>"><?php echo $currency_name; ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>

								<div class="currency_value" data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>">
                            <span>
                                <?php printf( '1 %s = ', $wc_currency ); ?>
	                            <span class="curr_val"></span>
                                <input type="text" value="" style="display: inline-block;">
                                <span class="curr_val_code"></span>
                            </span>
								</div>
							</td>
							<td class="currency-actions">
								<div class="currency_action_update">
									<a href="javascript:void(0);" title="Edit" class="edit_currency"
									   style="display:none">
										<i class="icon-edit" title="Edit"></i>
									</a>
								</div>
								<div class="currency_action_delete">
									<a href="javascript:void(0);" title="Delete" class="delete_currency"
									   data-currency="" style="display:none">
										<i class="icon-trash" alt="Delete"></i>
									</a>
									<i class="icon-remove-circle cancel_currency" style="display:inline"></i>
								</div>
							</td>
						</tr>
					</table>

					<table class="hidden js-currency_lang_table">
						<tr>
							<?php foreach ( $active_languages as $language ): ?>
								<td class="currency_languages">
									<div class="wcml_onof_buttons">
										<ul>
											<li><a class="off_btn" href="javascript:void(0);"
											       data-language="<?php echo $language['code']; ?>"><?php _e( 'OFF', 'wpml-wcml' ); ?></a>
											</li>
											<li class="on"><a class="on_btn" href="javascript:void(0);"
											                  data-language="<?php echo $language['code']; ?>"><?php _e( 'ON', 'wpml-wcml' ); ?></a>
											</li>
										</ul>
									</div>
								</td>
							<?php endforeach; ?>
						</tr>
					</table>

					<input type="hidden" value="<?php echo WCML_PLUGIN_URL; ?>" class="wcml_plugin_url"/>
					<input type="hidden" id="new_currency_nonce"
					       value="<?php echo wp_create_nonce( 'wcml_new_currency' ); ?>"/>
					<input type="hidden" id="del_currency_nonce"
					       value="<?php echo wp_create_nonce( 'wcml_delete_currency' ); ?>"/>
					<input type="hidden" id="currencies_list_nonce"
					       value="<?php echo wp_create_nonce( 'wcml_currencies_list' ); ?>"/>
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
					echo '<p>' . __( 'Products using custom currency rates as they were migrated from the previous versions - option to support different prices per language', 'wpml-wcml' ) . '</p>';
					echo '<form method="post" id="wcml_custom_exchange_rates">';
					echo '<input type="hidden" name="action" value="legacy_update_custom_rates">';
					echo '<table class="widefat currency_table" >';
					echo '<thead>';
					echo '<tr>';
					echo '<th rowspan="2">' . __( 'Product', 'wpml-wcml' ) . '</th>';
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
								_e( 'n/a', 'wpml-wcml' );
							}
							echo '</td>';
						}

						foreach ( $currencies as $code => $currency ) {
							echo '<td>';
							if ( isset( $rates['_sale_price'][ $code ] ) ) {
								echo '<input name="posts[' . $post->post_id . '][_sale_price][' . $code . ']" size="3" value="' . round( $rates['_sale_price'][ $code ], 3 ) . '">';
							} else {
								_e( 'n/a', 'wpml-wcml' );
							}
							echo '</td>';
						}

						echo '<td align="right"><a href="#" onclick=" if(confirm(\'' . esc_js( __( 'Are you sure?', 'wpml-wcml' ) ) . '\')) wcml_remove_custom_rates(' . $post->post_id . ', jQuery(this));return false;"><i class="icon-trash" title="' . __( 'Delete', 'wpml-wcml' ) . '"></i></a></td>';
						echo '<tr>';

					}
					echo '</tbody>';
					echo '</table>';
					echo '<p class="button-wrap"><input class="button-secondary" type="submit" value="' . esc_attr__( 'Update', 'wpml-wcml' ) . '" /></p>';
					echo '</form>';


				}
				?>
				<ul id="display_custom_prices_select">
					<li>
						<input type="checkbox" name="display_custom_prices" id="display_custom_prices"
						       value="1" <?php echo checked( 1, $woocommerce_wpml->settings['display_custom_prices'] ) ?> >
						<label
							for="display_custom_prices"><?php _e( 'Show only products with custom prices in secondary currencies', 'wpml-wcml' ); ?></label>
						<i class="otgs-ico-help wcml-tip"
						   data-tip="<?php _e( 'When this option is on, when you switch to a secondary currency on the front end, only the products with custom prices in that currency are being displayed. Products with prices determined based on the exchange rate are hidden.', 'wpml-wcml' ) ?>"></i>
					</li>
				</ul>

			</div>
		</div>
		<!-- .wcml-section-content -->

	</form>
</div> <!-- .wcml-section -->
<div class="wcml-section">
	<?php include WCML_PLUGIN_PATH . '/menu/sub/currency-switcher-options.php'; ?>
</div>
<input type="hidden" id="wcml_warn_message"
       value="<?php esc_attr_e( 'The changes you made will be lost if you navigate away from this page.', 'wpml-wcml' ); ?>"/>
<input type="hidden" id="wcml_warn_disable_language_massage"
       value="<?php esc_attr_e( 'At least one currency must be enabled for this language!', 'wpml-wcml' ); ?>"/>

<p class="wpml-margin-top-sm">
	<input type='submit' value='<?php esc_attr( _e( 'Save changes', 'wpml-wcml' ) ); ?>' class='button-primary'/>
</p>