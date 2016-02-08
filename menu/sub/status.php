<?php global $sitepress_settings;
$default_language = $sitepress->get_default_language();
?>

<div class="wcml-section">
	<div class="wcml-section-header">
		<h3>
			<?php _e( 'Plugins Status', 'woocommerce-multilingual' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php _e( 'WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'woocommerce-multilingual' ) ?>"></i>
		</h3>
	</div>
	<div class="wcml-section-content">
		<ul class="wcml-status-list wcml-plugins-status-list">
			<?php if ( defined( 'ICL_SITEPRESS_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s is installed and active.', 'woocommerce-multilingual' ), '<strong>WPML</strong>' ); ?>
				</li>
				<?php if ( $sitepress->setup() ): ?>
					<li>
						<i class="otgs-ico-ok"></i> <?php printf( __( '%s is set up.', 'woocommerce-multilingual' ), '<strong>WPML</strong>' ); ?>
					</li>
				<?php else: ?>
					<li>
						<i class="otgs-ico-warning"></i> <?php printf( __( '%s is not set up.', 'woocommerce-multilingual' ), '<strong>WPML</strong>' ); ?>
					</li>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( defined( 'WPML_MEDIA_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s is installed and active.', 'woocommerce-multilingual' ), '<strong>WPML Media</strong>' ); ?>
				</li>
			<?php endif; ?>
			<?php if ( defined( 'WPML_TM_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s is installed and active.', 'woocommerce-multilingual' ), '<strong>WPML Translation Management</strong>' ); ?>
				</li>
			<?php endif; ?>
			<?php if ( defined( 'WPML_ST_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s is installed and active.', 'woocommerce-multilingual' ), '<strong>WPML String Translation</strong>' ); ?>
				</li>
			<?php endif; ?>
			<?php
			global $woocommerce;
			if ( class_exists( 'Woocommerce' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s is installed and active.', 'woocommerce-multilingual' ), '<strong>WooCommerce</strong>' ); ?>
				</li>
			<?php endif; ?>
		</ul>
	</div>
	<!-- .wcml-section-content -->
</div> <!-- .wcml-section -->


<?php
$miss_slug_lang = $woocommerce_wpml->strings->get_missed_product_slug_translations_languages();
$prod_slug      = $woocommerce_wpml->strings->product_permalink_slug();

if ( ( ! WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $default_language != 'en' && empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ) || ! empty( $woocommerce_wpml->dependencies->xml_config_errors ) || ! empty( $miss_slug_lang ) ): ?>
	<div class="wcml-section">
		<div class="wcml-section-header">
			<h3>
				<?php _e( 'Configuration warnings', 'woocommerce-multilingual' ); ?>
				<i class="otgs-ico-help wcml-tip"
				   data-tip="<?php _e( 'Reporting miscelaneous configuration issues that can make WooCommerce Multilingual not run normally', 'woocommerce-multilingual' ) ?>"></i>
			</h3>
		</div>
		<div class="wcml-section-content">
			<ul class="wcml-status-list">
				<?php if ( ! empty( $miss_slug_lang ) ): ?>
					<li>
						<i class="otgs-ico-warning"></i> <?php _e( "Your product permalink base is not translated to:", 'woocommerce-multilingual' ); ?>
							<ul class="wcml-lang-list">
								<?php foreach( $miss_slug_lang as $miss_lang): ?>

									<li>
									<span class="wpml-title-flag"><img
											src="<?php echo $sitepress->get_flag_url( $miss_lang['code'] ); ?>"
											alt="<?php echo $miss_lang['english_name'] ?>"></span> <?php echo ucfirst( $miss_lang['display_name'] ) ?>
									</li>

								<?php endforeach; ?>
							</ul>
						<?php _e( "The urls for the translated products will not work.", 'woocommerce-multilingual' ); ?>
						<a class="button-secondary"
						   href="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=slugs' ) ?>">
							<?php _e( "Translate URLs", 'woocommerce-multilingual' ); ?>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( ! WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $default_language != 'en' ): ?>
					<?php if ( $sitepress_settings['st']['strings_language'] != 'en' ): ?>
						<li>
							<i class="otgs-ico-warning"></i> <?php _e( "Your site's default language is not English and the strings language is also not English.", 'woocommerce-multilingual' ) ?>
							<small>
								<i class="otgs-ico-help"></i>
								<a href="https://wpml.org/?page_id=355545">
									<?php _e( "Running WooCommerce multilingual with default language other than English.", 'woocommerce-multilingual' ); ?>
								</a>
							</small>
							<p>
								<?php _e( "This may cause problems with URLs in different languages.", 'woocommerce-multilingual' ) ?>
								<input type="hidden" id="wcml_fix_strings_language_nonce"
								       value="<?php echo wp_create_nonce( 'wcml_fix_strings_language' ) ?>"/>
								<button id="wcml_fix_strings_language" type="button" class="button-secondary">
									<?php esc_attr_e( "Change default language", 'woocommerce-multilingual' ) ?>
								</button>
							</p>
					</li>
					<?php elseif ( empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ): ?>
						<li>
							<i class="otgs-ico-warning"></i> <?php _e( "Your site's default language is not English.", 'woocommerce-multilingual' ) ?>
							<small>
								<i class="otgs-ico-help"></i>
								<a href="https://wpml.org/?page_id=355545">
									<?php _e( "Running WooCommerce multilingual with default language other than English.", 'woocommerce-multilingual' ); ?>
								</a>
							</small>
							<p>
								<?php _e( "There are some settings that require careful attention.", 'woocommerce-multilingual' ) ?>
							</p>
						</li>
					<?php endif; ?>

				<?php endif; ?>

				<?php if ( ! empty( $woocommerce_wpml->dependencies->xml_config_errors ) ): ?>
					<li><i class="otgs-ico-warning"></i>
						<strong><?php _e( 'Some settings from the WooCommerce Multilingual wpml-config.xml file have been overwritten.', 'woocommerce-multilingual' ); ?></strong>

						<p><?php printf( __( 'You should check WPML configuration files added by other plugins or manual settings on the %sMultilingual Content Setup%s section.', 'woocommerce-multilingual' ),
								'<a href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup' ) . '">', '</a>' ) ?>
						</p>
						<ul>
							<?php foreach ( $woocommerce_wpml->dependencies->xml_config_errors as $error ): ?>
								<li><?php echo $error ?></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>
			</ul>
	</div>

	</div>
<?php endif; ?>


<div class="wcml-section">
	<div class="wcml-section-header">
		<h3>
			<?php _e( 'WooCommerce Store Pages', 'woocommerce-multilingual' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php _e( 'To run a multilingual e-commerce site, you need to have the WooCommerce shop pages translated to all the site\'s languages. Once all the pages are installed you can add the translations for them from this menu.', 'woocommerce-multilingual' ) ?>"></i>
		</h3>
	</div>
	<div class="wcml-section-content">

		<?php $miss_lang = $woocommerce_wpml->store->get_missing_store_pages(); ?>
		<ul class="wcml-status-list">
			<?php if ( $miss_lang == 'non_exist' ): ?>
				<li>
					<i class="otgs-ico-warning"></i> <?php _e( "One or more WooCommerce pages have not been created.", 'woocommerce-multilingual' ); ?>
				</li>
				<li>
					<a href="<?php echo version_compare( $woocommerce->version, '2.1', '<' ) ? admin_url( 'admin.php?page=woocommerce_settings&tab=pages' ) : admin_url( 'admin.php?page=wc-status&tab=tools' ); ?>"><?php _e( 'Install WooCommerce Pages' ) ?></a>
				</li>
			<?php elseif ( $miss_lang ): ?>
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
					<?php wp_nonce_field( 'create_pages', 'wcml_nonce' ); ?>
					<input type="hidden" name="create_missing_pages" value="1"/>
					<li>
						<?php
						if ( isset( $miss_lang['lang'] ) ): ?>
							<i class="otgs-ico-warning"></i> <?php _e( "WooCommerce store pages do not exist for these languages:", 'woocommerce-multilingual' ); ?>

							<ul class="wcml-lang-list">
								<?php foreach( $miss_lang['lang'] as $missed_lang ): ?>
									<li>
										<span class="wpml-title-flag"><img
										src="<?php echo $sitepress->get_flag_url( $missed_lang['code'] ); ?>"
										alt="<?php echo $missed_lang['english_name'] ?>"></span> <?php echo ucfirst( $missed_lang['display_name'] ) ?>
									</li>
								<?php endforeach; ?>
							</ul>

							<button class="button-secondary aligncenter" type="submit" name="create_pages">
								<?php _e( 'Create missing translations', 'woocommerce-multilingual' ); ?>
							</button>
						<?php endif; ?>
					</li>
					<?php
					if ( isset( $miss_lang['in_progress'] ) ): ?>
						<li>
							<i class="otgs-ico-in-progress"></i> <?php _e( "These pages are currently being translated by translators via WPML: ", 'woocommerce-multilingual' ); ?>
							<ul class="wcml-lang-list">
								<?php foreach( $miss_lang['in_progress'] as $in_progress_pages ): ?>
									<li>
										<span class="wpml-title-flag"><?php echo $in_progress_pages['page']; ?></span>
										<ul class="wcml-lang-list">
											<?php foreach( $in_progress_pages['lang'] as $miss_in_progress ): ?>
												<li>
													<span class="wpml-title-flag"><img
													src="<?php echo $sitepress->get_flag_url( $miss_in_progress['code'] ); ?>"
													alt="<?php echo $miss_in_progress['english_name'] ?>"></span> <?php echo ucfirst( $miss_in_progress['display_name'] ) ?>
												</li>
											<?php endforeach; ?>
										</ul>
									</li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php endif; ?>
				</form>

			<?php else: ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php _e( "WooCommerce store pages are translated to all the site's languages.", 'woocommerce-multilingual' ); ?>
				</li>
			<?php endif; ?>
		</ul>
	</div>
	<!-- .wcml-section-content -->
</div> <!-- .wcml-section -->

<div class="wcml-section">
	<div class="wcml-section-header">
		<h3>
			<?php _e( 'Taxonomies missing translations', 'woocommerce-multilingual' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php esc_attr_e( 'To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'woocommerce-multilingual' ) ?>"></i>
		</h3>
	</div>
	<div class="wcml-section-content js-tax-translation">
		<input type="hidden" id="wcml_ingore_taxonomy_translation_nonce"
		       value="<?php echo wp_create_nonce( 'wcml_ingore_taxonomy_translation_nonce' ) ?>"/>
		<?php
		$taxonomies = $woocommerce_wpml->terms->get_wc_taxonomies();

		?>
		<ul class="wcml-status-list wcml-tax-translation-list">
			<?php
			$no_tax_to_update = true;
			foreach ( $taxonomies as $taxonomy ): ?>
				<?php if ( $taxonomy == 'product_type' || WCML_Terms::get_untranslated_terms_number( $taxonomy ) == 0 ) {
					continue;
				} else {
					$no_tax_to_update = false;
				} ?>
				<li class="js-tax-translation-<?php echo $taxonomy ?>">
					<?php if ( $untranslated = WCML_Terms::get_untranslated_terms_number( $taxonomy ) ): ?>
						<?php if ( WCML_Terms::is_fully_translated( $taxonomy ) ): // covers the 'ignore' case' ?>
							<i class="otgs-ico-ok"></i> <?php printf( __( '%s do not require translation.', 'woocommerce-multilingual' ), get_taxonomy( $taxonomy )->labels->name ); ?>
							<small class="actions">
								<a class="unignore-<?php echo $taxonomy ?>" href="#unignore-<?php echo $taxonomy ?>"
								   title="<?php esc_attr_e( 'This taxonomy requires translation.', 'woocommerce-multilingual' ) ?>"><?php _e( 'Include in translation', 'woocommerce-multilingual' ) ?></a>
							</small>
						<?php else: ?>
							<i class="otgs-ico-warning"></i>
							<?php printf( __( '%d %s are missing translations.', 'woocommerce-multilingual' ), $untranslated, get_taxonomy( $taxonomy )->labels->name ); ?>
							<a class="button-secondary"
							   href="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=product-attributes&taxonomy=' . $taxonomy ) ?>">
								<?php printf( __( 'Translate %s', 'woocommerce-multilingual' ), get_taxonomy( $taxonomy )->labels->name ); ?>
							</a>
							<small class="actions">
								<a class="ignore-<?php echo $taxonomy ?>" href="#ignore-<?php echo $taxonomy ?>"
								   title="<?php esc_attr_e( 'This taxonomy does not require translation.', 'woocommerce-multilingual' ) ?>"><?php _e( 'Exclude from translation', 'woocommerce-multilingual' ) ?></a>
							</small>
						<?php endif; ?>
					<?php else: ?>
						<i class="otgs-ico-ok"></i> <?php printf( __( 'All %s are translated.', 'woocommerce-multilingual' ), get_taxonomy( $taxonomy )->labels->name ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			<?php if ( $no_tax_to_update ): ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php _e( 'Right now, there are no taxonomy terms needing translation.', 'woocommerce-multilingual' ); ?>
				</li>
			<?php endif; ?>
		</ul>
	</div>
</div>

<a class="alignright wpml-margin-top-sm"
   href="<?php echo admin_url( 'admin.php?page=' . basename( WCML_PLUGIN_PATH ) . '/menu/sub/troubleshooting.php' ); ?>"><?php _e( 'Troubleshooting page', 'woocommerce-multilingual' ); ?></a>

