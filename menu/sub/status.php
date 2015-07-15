<?php global $sitepress_settings;
$default_language = $sitepress->get_default_language();
?>

<div class="wcml-section">
	<div class="wcml-section-header">
		<h3>
			<?php _e( 'Plugins Status', 'wpml-wcml' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php _e( 'WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'wpml-wcml' ) ?>"></i>
		</h3>
	</div>
	<div class="wcml-section-content">
		<ul class="wcml-status-list">
			<?php if ( defined( 'ICL_SITEPRESS_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s plugin is installed and active.', 'wpml-wcml' ), '<strong>WPML</strong>' ); ?>
				</li>
				<?php if ( $sitepress->setup() ): ?>
					<li>
						<i class="otgs-ico-ok"></i> <?php printf( __( '%s is set up.', 'wpml-wcml' ), '<strong>WPML</strong>' ); ?>
					</li>
				<?php else: ?>
					<li>
						<i class="otgs-ico-warning"></i> <?php printf( __( '%s is not set up.', 'wpml-wcml' ), '<strong>WPML</strong>' ); ?>
					</li>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( defined( 'WPML_MEDIA_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s plugin is installed and active.', 'wpml-wcml' ), '<strong>WPML Media</strong>' ); ?>
				</li>
			<?php endif; ?>
			<?php if ( defined( 'WPML_TM_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s plugin is installed and active.', 'wpml-wcml' ), '<strong>WPML Translation Management</strong>' ); ?>
				</li>
			<?php endif; ?>
			<?php if ( defined( 'WPML_ST_VERSION' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s plugin is installed and active.', 'wpml-wcml' ), '<strong>WPML String Translation</strong>' ); ?>
				</li>
			<?php endif; ?>
			<?php
			global $woocommerce;
			if ( class_exists( 'Woocommerce' ) ) : ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php printf( __( '%s plugin is installed and active.', 'wpml-wcml' ), '<strong>WooCommerce</strong>' ); ?>
				</li>
			<?php endif; ?>
		</ul>
	</div>
	<!-- .wcml-section-content -->

</div> <!-- .wcml-section -->


<?php
$miss_slug_lang = $woocommerce_wpml->strings->get_missed_product_slag_translations_languages();
$prod_slug      = $woocommerce_wpml->strings->product_permalink_slug();

if ( ( ! WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $default_language != 'en' && empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ) || ! empty( $woocommerce_wpml->dependencies->xml_config_errors ) || ! empty( $miss_slug_lang ) ): ?>
	<div class="wcml-section">
		<div class="wcml-section-header">
			<h3>
				<?php _e( 'Configuration warnings', 'wpml-wcml' ); ?>
				<i class="otgs-ico-help wcml-tip"
				   data-tip="<?php _e( 'Reporting miscelaneous configuration issues that can make WooCommerce Multilingual not run normally', 'wpml-wcml' ) ?>"></i>
			</h3>
		</div>

		<div class="wcml-section-content">
			<ul class="wcml-status-list">

				<?php if ( ! empty( $miss_slug_lang ) ): ?>

					<li>
						<i class="otgs-ico-warning"></i> <?php printf( __( "Your product permalink base is not translated in %s. The urls for the translated products will not work. Go to the %sString Translation%s to translate.", 'wpml-wcml' ), '<b>' . implode( ', ', $miss_slug_lang ) . '</b>', '<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&search=' . $prod_slug . '&context=WordPress&em=1' ) . '">', '</a>' ) ?>
					</li>

				<?php endif; ?>

				<?php if ( ! WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $default_language != 'en' ): ?>

					<?php if ( $sitepress_settings['st']['strings_language'] != 'en' ): ?>
						<li>

							<i class="otgs-ico-warning"></i> <?php _e( "Your site's default language is not English and the strings language is also not English. This may lead to problems with your site's URLs in different languages.", 'wpml-wcml' ) ?>

							<ul>
								<li>&raquo;&nbsp;<?php _e( 'Change the strings language to English', 'wpml-wcml' ) ?></li>
								<li>&raquo;&nbsp;<?php _e( 'Re-scan strings', 'wpml-wcml' ) ?></li>
							</ul>

							<p class="submit">
								<input type="hidden" id="wcml_fix_strings_language_nonce"
								       value="<?php echo wp_create_nonce( 'wcml_fix_strings_language' ) ?>"/>
								<input id="wcml_fix_strings_language" type="button" class="button-primary"
								       value="<?php esc_attr_e( 'Run fix', 'wpml-wcml' ) ?>"/>
							</p>

							<p><?php printf( __( "Please review the %sguide for running WooCommerce multilingual with default language other than English%s.", 'wpml-wcml' ), '<a href="http://wpml.org/?page_id=355545">', '</a>' ) ?> </p>
						</li>
					<?php elseif ( empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ): ?>
						<li>
							<i class="otgs-ico-warning"></i> <?php _e( "Your site's default language is not English. There are some settings that require careful attention.", 'wpml-wcml' ) ?>
							<p><?php printf( __( "Please review the %sguide for running WooCommerce multilingual with default language other than English%s.", 'wpml-wcml' ), '<a href="http://wpml.org/?page_id=355545">', '</a>' ) ?> </p>
						</li>
					<?php endif; ?>

				<?php endif; ?>

				<?php if ( ! empty( $woocommerce_wpml->dependencies->xml_config_errors ) ): ?>
					<li><i class="otgs-ico-warning"></i>
						<strong><?php _e( 'Some settings from the WooCommerce Multilingual wpml-config.xml file have been overwritten.', 'wpml-wcml' ); ?></strong>

						<p><?php printf( __( 'You should check WPML configuration files added by other plugins or manual settings on the %sMultilingual Content Setup%s section.', 'wpml-wcml' ),
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
			<?php _e( 'WooCommerce Store Pages', 'wpml-wcml' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php _e( 'To run a multilingual e-commerce site, you need to have the WooCommerce shop pages translated in all the site\'s languages. Once all the pages are installed you can add the translations for them from this menu.', 'wpml-wcml' ) ?>"></i>
		</h3>
	</div>

	<div class="wcml-section-content">

		<?php $miss_lang = $woocommerce_wpml->store->get_missing_store_pages(); ?>
		<ul class="wcml-status-list">
		<?php if ( $miss_lang == 'non_exist' ): ?>

				<li>
					<i class="otgs-ico-warning"></i> <?php _e( "One or more WooCommerce pages have not been created.", 'wpml-wcml' ); ?>
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
					if ( isset( $miss_lang['codes'] ) ): ?>
						<i class="otgs-ico-warning"></i> <?php _e( "WooCommerce store pages do not exist for these languages:", 'wpml-wcml' ); ?>
                    <?php endif; ?>
					<?php //TODO Sergey: Make it work ?>
					<ul class="wcml-lang-list">
						<li>
						<span class="wpml-title-flag"><img
								src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"
								alt="Polish"></span> Polish
						</li>
						<li>
						<span class="wpml-title-flag"><img
								src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"
								alt="Spanish"></span> Spanish
						</li>
					</ul>
					<input class="button-secondary aligncenter" type="submit" name="create_pages"
					       value="<?php esc_attr( _e( 'Create missing translations', 'wpml-wcml' ) ) ?>"/>
					<?php /*
					if ( isset( $miss_lang['lang'] ) ): ?>
						<p>
							<strong><?php echo $miss_lang['lang'] ?></strong>

						</p>
					<?php endif; */ ?>
				</li>
				<?php
				if ( isset( $miss_lang['in_progress'] ) ): ?>
					<li class="wpml-margin-top-base">
						<i class="otgs-ico-in-progress"></i> <?php _e( "These pages are currently being translated by translators via WPML: ", 'wpml-wcml' ); ?>
						<?php //TODO Sergey: Make it work ?>
						<ul class="wcml-lang-list">
							<li>
							<span class="wpml-title-flag"><img
									src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"
									alt="Ukraininan"></span> Ukraininan
							</li>
						</ul>
						<!--							<strong>-->
						<?php //echo $miss_lang['in_progress'] ?><!--</strong>-->
					</li>
				<?php endif; ?>

			</form>

		<?php else: ?>
			<li>
				<i class="otgs-ico-ok"></i> <?php _e( "WooCommerce store pages are translated to all the site's languages.", 'wpml-wcml' ); ?>
			</li>
		<?php endif; ?>
		</ul>

	</div>
	<!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="wcml-section">

	<div class="wcml-section-header">
		<h3>
			<?php _e( 'Taxonomies missing translations', 'wpml-wcml' ); ?>
			<i class="otgs-ico-help wcml-tip"
			   data-tip="<?php esc_attr_e( 'To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'wpml-wcml' ) ?>"></i>
		</h3>
	</div>

	<div class="wcml-section-content js-tax-translation">
		<input type="hidden" id="wcml_ingore_taxonomy_translation_nonce"
		       value="<?php echo wp_create_nonce( 'wcml_ingore_taxonomy_translation_nonce' ) ?>"/>
		<?php
		global $wp_taxonomies;
		$taxonomies = array();

		//don't use get_taxonomies for product, because when one more post type registered for product taxonomy functions returned taxonomies only for product type
		foreach ( $wp_taxonomies as $key => $taxonomy ) {
			if ( ( in_array( 'product', $taxonomy->object_type ) || in_array( 'product_variation', $taxonomy->object_type ) ) && ! in_array( $key, $taxonomies ) ) {
				$taxonomies[] = $key;
			}
		}

		?>

		<ul class="wcml-status-list">
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
							<i class="otgs-ico-ok"></i> <?php printf( __( '%s do not require translation.', 'wpml-wcml' ), get_taxonomy( $taxonomy )->labels->name ); ?>
							<div class="actions">
								<a href="#unignore-<?php echo $taxonomy ?>"
								   title="<?php esc_attr_e( 'This taxonomy requires translation.', 'wpml-wcml' ) ?>"><?php _e( 'Change', 'wpml-wcml' ) ?></a>
							</div>
						<?php else: ?>
							<i class="otgs-ico-warning"></i> <?php printf( __( 'Some %s are missing translations (%d translations missing).', 'wpml-wcml' ), get_taxonomy( $taxonomy )->labels->name, $untranslated ); ?>
							<div class="actions">
								<a href="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=' . $taxonomy ) ?>"><?php _e( 'Translate now', 'wpml-wcml' ) ?></a>
								|
								<a href="#ignore-<?php echo $taxonomy ?>"
								   title="<?php esc_attr_e( 'This taxonomy does not require translation.', 'wpml-wcml' ) ?>"><?php _e( 'Ignore', 'wpml-wcml' ) ?></a>
							</div>
						<?php endif; ?>
					<?php else: ?>
						<i class="otgs-ico-ok"></i> <?php printf( __( 'All %s are translated.', 'wpml-wcml' ), get_taxonomy( $taxonomy )->labels->name ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			<?php if ( $no_tax_to_update ): ?>
				<li>
					<i class="otgs-ico-ok"></i> <?php _e( 'Right now, there are no taxonomy terms needing translation.', 'wpml-wcml' ); ?>
				</li>
			<?php endif; ?>
		</ul>


	</div>

</div>

<a class="alignright wpml-margin-top-sm"
   href="<?php echo admin_url( 'admin.php?page=' . basename( WCML_PLUGIN_PATH ) . '/menu/sub/troubleshooting.php' ); ?>"><?php _e( 'Troubleshooting page', 'wpml-wcml' ); ?></a>

