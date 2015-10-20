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
				<strong><?php echo get_post( get_option('woocommerce_shop_page_id' ) )->post_name ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'shop', $active_languages ); ?>
			</td>

		</tr>
		<tr>
			<td>
				<strong>
					<?php _e( 'Product(s) page(s) base', 'woocommerce-multilingual' ); ?>
				</strong>
			</td>

			<td class="wpml-col-url">
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
				<strong><?php echo trim( $woocommerce_wpml->url_translation->wc_permalinks['attribute_base'], '/' ) ?></strong>
			</td>

			<td class="wpml-col-languages">
				<?php echo $woocommerce_wpml->url_translation->get_base_translations_statuses( 'attribute', $active_languages ); ?>
			</td>
		</tr>
	</tbody>
</table>
<?php wp_nonce_field('wcml_edit_base', 'wcml_edit_base_nonce'); ?>

