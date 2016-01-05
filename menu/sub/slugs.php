<div>
	<p><?php _e( 'This page allows you to translate all strings that are being used by WooCommerce in building different type of urls. Translating them enables you to have fully localized urls that match the language of the pages.', 'woocommerce-multilingual'); ?></p>
	<p><?php echo sprintf( __('You can enter or edit your default values on the %s page or, for the endpoints, on the WooCommerce %s page.', 'woocommerce-multilingual' ), '<a href="'.admin_url('options-permalink.php').'" >'. __( 'permalinks settings', 'woocommerce-multilingual').'</a>', '<a href="admin.php?page=wc-settings&tab=account" >'. __( 'Account settings', 'woocommerce-multilingual').'</a>' ); ?></p>
</div>
<table class="widefat wpml-list-table wp-list-table striped" cellspacing="0">
	<thead>
		<tr>
			<th scope="col"><?php _e( 'Slug type', 'woocommerce-multilingual' ) ?></th>
			<th scope="col" id="date" class="wpml-col-url">
				<?php _e( 'Original Slug', 'woocommerce-multilingual' ) ?>
			</th>
			<th scope="col" class="wpml-col-languages">
				<?php echo $woocommerce_wpml->products->get_translation_flags( $active_languages, false, false ); ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<strong>
					<?php _e( 'Shop page', 'woocommerce-multilingual' ); ?>
				</strong>
			</td>

			<td class="wpml-col-url">
				<img src="<?php echo $sitepress->get_flag_url( $woocommerce_wpml->url_translation->get_source_slug_language( 'shop' ) ); ?>" />
				<strong><?php echo get_post( get_option('woocommerce_shop_page_id' ) )->post_name ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'shop', $active_languages ); ?>
			</td>

		</tr>
		<tr>
			<td>
				<strong>
					<?php _e( 'Product base', 'woocommerce-multilingual' ); ?>
				</strong>
			</td>

			<td class="wpml-col-url">
				<img src="<?php echo $sitepress->get_flag_url( $woocommerce_wpml->url_translation->get_source_slug_language( 'product' ) ); ?>" />
				<strong><?php echo $woocommerce_wpml->url_translation->get_woocommerce_product_base(); ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'product', $active_languages ); ?>
			</td>

		</tr>
		<tr>
			<td>
				<strong>
					<?php _e( 'Product category base', 'woocommerce-multilingual' ); ?>
				</strong>
			</td>

			<td class="wpml-col-url">
				<img src="<?php echo $sitepress->get_flag_url( $woocommerce_wpml->url_translation->get_source_slug_language( 'product_cat' ) ); ?>" />
				<strong><?php echo !empty( $woocommerce_wpml->url_translation->wc_permalinks['category_base'] ) ? trim( $woocommerce_wpml->url_translation->wc_permalinks['category_base'], '/' ) : 'product-category' ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'product_cat', $active_languages ); ?>
			</td>

		</tr>
		<tr>
			<td>
				<strong>
					<?php _e( 'Product tag base', 'woocommerce-multilingual' ); ?>
				</strong>
			</td>

			<td class="wpml-col-url">
				<img src="<?php echo $sitepress->get_flag_url( $woocommerce_wpml->url_translation->get_source_slug_language( 'product_tag' ) ); ?>" />
				<strong><?php echo !empty( $woocommerce_wpml->url_translation->wc_permalinks['tag_base'] ) ? trim( $woocommerce_wpml->url_translation->wc_permalinks['tag_base'], '/' ) : 'product-tag' ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'product_tag', $active_languages ); ?>
			</td>

		</tr>
		<tr>
			<td>
				<strong>
					<?php _e( 'Product attribute base', 'woocommerce-multilingual' ); ?>
				</strong>
			</td>

			<td class="wpml-col-url">
				<img src="<?php echo $sitepress->get_flag_url( $woocommerce_wpml->url_translation->get_source_slug_language( 'attribute' ) ); ?>" />
				<strong><?php echo trim( $woocommerce_wpml->url_translation->wc_permalinks['attribute_base'], '/' ) ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'attribute', $active_languages, $woocommerce_wpml->url_translation->wc_permalinks['attribute_base'] ); ?>
			</td>
		</tr>
		<?php $endpoints = WC()->query->query_vars;
		foreach( $endpoints as $key => $endpoint ): ?>
			<tr>
				<td>
					<strong>
						<?php echo sprintf( __( 'Endpoint: %s', 'woocommerce-multilingual' ), $key ); ?>
					</strong>
				</td>

				<td class="wpml-col-url">
					<img src="<?php echo $sitepress->get_flag_url( $woocommerce_wpml->url_translation->get_source_slug_language( $key ) ); ?>" />
					<strong><?php echo $endpoint ?></strong>
				</td>

				<td class="wpml-col-languages">
					<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( $key, $active_languages, $endpoint ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php wp_nonce_field('wcml_edit_base', 'wcml_edit_base_nonce'); ?>

