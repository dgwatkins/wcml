<?php
$pn = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
$lm = ( isset( $_GET['lm'] ) && $_GET['lm'] > 0 ) ? $_GET['lm'] : 20;

$search         = false;
$pagination_url = admin_url( 'admin.php?page=wpml-wcml&tab=products&paged=' );
$translator_id  = false;

if ( isset( $_GET['prid'] ) ) {
	if ( ! $woocommerce_wpml->products->is_original_product( $_GET['prid'] ) ) {
		$original_language = $this->products->get_original_product_language( $_GET['prid'] );
		$products[]        = get_post( apply_filters( 'translate_object_id', $_GET['prid'], 'product', true, $original_language ) );
	} else {
		$products[] = get_post( $_GET['prid'] );
	}
	$products_count = 1;
	$pr_edit        = true;
}

$job_id = 0;
if ( isset( $_GET['job_id'] ) ) {
	$job_id = $_GET['job_id'];

	global $iclTranslationManagement;
	$job = $iclTranslationManagement->get_translation_job( $_GET['job_id'] );

	$job_language = $job->language_code;
}


if ( ! current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
	global $iclTranslationManagement, $wp_query;
	$current_translator = $iclTranslationManagement->get_current_translator();
	$translator_id      = $current_translator->translator_id;

	if ( ! isset( $products ) ) {
		$icl_translation_filter['translator_id']      = $translator_id;
		$icl_translation_filter['include_unassigned'] = true;
		$icl_translation_filter['limit_no']           = $lm;
		$translation_jobs                             = $iclTranslationManagement->get_translation_jobs( (array) $icl_translation_filter );
		$products                                     = array();
		$products_count                               = 0;
		foreach ( $translation_jobs as $translation_job ) {
			if ( $translation_job->original_post_type == 'post_product' && ! array_key_exists( $translation_job->original_doc_id, $products ) ) {
				$products[ $translation_job->original_doc_id ] = get_post( $translation_job->original_doc_id );
				$products_count ++;
			}
		}

	}

}

$slang = isset( $_GET['slang'] ) && $_GET['slang'] != 'all' ? $_GET['slang'] : false;

if ( ! isset( $products ) && isset( $_GET['s'] ) && isset( $_GET['cat'] ) && isset( $_GET['trst'] ) && isset( $_GET['st'] ) && isset( $_GET['slang'] ) ) {
	$products_data  = $woocommerce_wpml->products->get_products_from_filter( $_GET['s'], $_GET['cat'], $_GET['trst'], $_GET['st'], $slang, $pn, $lm );
	$products       = $products_data['products'];
	$products_count = $products_data['count'];
	$search         = true;
	$pagination_url = admin_url( 'admin.php?page=wpml-wcml&tab=products&s=' . $_GET['s'] . '&cat=' . $_GET['cat'] . '&trst=' . $_GET['trst'] . '&st=' . $_GET['st'] . '&slang=' . $_GET['slang'] . '&paged=' );
}


if ( ! isset( $products ) && current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
	$products       = $woocommerce_wpml->products->get_product_list( $pn, $lm, $slang );
	$products_count = $woocommerce_wpml->products->get_products_count( $slang );
}

if ( $lm ) {
	$last = $woocommerce_wpml->products->get_product_last_page( $products_count, $lm );
}

$button_labels = array(
	'save'   => esc_attr__( 'Save', 'wpml-wcml' ),
	'update' => esc_attr__( 'Update', 'wpml-wcml' ),
);

$woocommerce_wpml->settings['first_editor_call'] = false;
$woocommerce_wpml->update_settings(); ?>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<?php if ( ! isset( $_GET['prid'] ) && ! $translator_id ): ?>
		<div class="tablenav top clearfix">
			<div class="alignleft">
				<select>
					<option
						value="all" <?php echo ! $slang ? 'selected="selected"' : ''; ?> ><?php _e( 'All languages', 'wpml-wcml' ); ?></option>
					<?php foreach ( $active_languages as $lang ): ?>
						<option
							value="<?php echo $lang['code'] ?>" <?php echo ( $slang == $lang['code'] ) ? 'selected="selected"' : ''; ?> ><?php echo $lang['display_name'] ?></option>
					<?php endforeach; ?>
				</select>

				<select>
					<option value="0"><?php _e( 'All categories', 'wpml-wcml' ); ?></option>
					<?php

					$sql = "SELECT tt.term_taxonomy_id,tt.term_id,t.name FROM $wpdb->term_taxonomy AS tt
                    LEFT JOIN $wpdb->terms AS t ON tt.term_id = t.term_id
                    LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = tt.term_taxonomy_id
                    WHERE tt.taxonomy = 'product_cat' AND icl.element_type= 'tax_product_cat' ";

					if ( $slang ) {
						$sql .= " AND icl.language_code = %s ";
						$product_categories = $wpdb->get_results( $wpdb->prepare( $sql, $slang ) );
					} else {
						$sql .= "AND icl.source_language_code IS NULL";
						$product_categories = $wpdb->get_results( $sql );
					}

					foreach ( $product_categories as $category ) {
						$selected = ( isset( $_GET['cat'] ) && $_GET['cat'] == $category->term_taxonomy_id ) ? 'selected="selected"' : '';
						echo '<option value="' . $category->term_taxonomy_id . '" ' . $selected . '>' . $category->name . '</option>';
					}
					?>
				</select>
				<select>
					<option value="all"><?php _e( 'All translation statuses', 'wpml-wcml' ); ?></option>
					<option
						value="not" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'not' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Not translated or needs updating', 'wpml-wcml' ); ?></option>
					<option
						value="need_update" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'need_update' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Needs updating', 'wpml-wcml' ); ?></option>
					<option
						value="in_progress" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'in_progress' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Translation in progress', 'wpml-wcml' ); ?></option>
					<option
						value="complete" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'complete' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Translation complete', 'wpml-wcml' ); ?></option>
				</select>

				<?php
				$all_statuses = get_post_stati();
				//unset unnecessary statuses
				unset( $all_statuses['trash'], $all_statuses['auto-draft'], $all_statuses['inherit'], $all_statuses['wc-pending'], $all_statuses['wc-processing'], $all_statuses['wc-on-hold'], $all_statuses['wc-completed'], $all_statuses['wc-cancelled'], $all_statuses['wc-refunded'], $all_statuses['wc-failed'] );
				?>
				<select class="wcml_product_status">
					<option value="all"><?php _e( 'All statuses', 'wpml-wcml' ); ?></option>
					<?php foreach ( $all_statuses as $key => $status ): ?>
						<option
							value="<?php echo $key; ?>" <?php echo ( isset( $_GET['st'] ) && $_GET['st'] == $key ) ? 'selected="selected"' : ''; ?> ><?php echo ucfirst( $status ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php //TODO Sergey: Make this button work ;) ?>
				<button type="button" value="filter"
				        class="button-secondary"><?php _e( 'Filter', 'wpml-wcml' ); ?></button>
			</div>

			<div class="alignright">
				<input type="text" class="wcml_product_name" placeholder="<?php _e( 'Search', 'wpml-wcml' ); ?>"
				       value="<?php echo isset( $_GET['s'] ) ? $_GET['s'] : ''; ?>"/>
				<input type="hidden" value="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=products' ); ?>"
				       class="wcml_products_admin_url"/>
				<input type="hidden" value="<?php echo $pagination_url; ?>" class="wcml_pagination_url"/>

				<button type="button" value="search"
				        class="button-secondary"><?php _e( 'Search', 'wpml-wcml' ); ?></button>
			</div>
		</div>
	<?php endif; ?>


	<input type="hidden" id="upd_product_nonce" value="<?php echo wp_create_nonce( 'update_product_actions' ); ?>"/>
	<input type="hidden" id="get_product_data_nonce" value="<?php echo wp_create_nonce( 'wcml_product_data' ); ?>"/>

	<table class="widefat fixed wcml-products wp-list-table striped" cellspacing="0">
		<thead>
		<tr>
			<?php //TODO Sergey: make Title and Date columns sortable ?>
			<th scope="col" class="manage-column column-thumb">
				<span class="wc-image wcml-tip"
				      data-tip="<?php _e( 'Image', 'wpml-wcml' ) ?>"><?php _e( 'Image', 'wpml-wcml' ) ?></span>
			</th>
			<th scope="col" class="wpml-col-title"><?php _e( 'Product', 'wpml-wcml' ) ?></th>
			<th scope="col" class="wpml-col-languages">
				<?php echo $woocommerce_wpml->products->get_translation_flags( $active_languages, $slang, $job_id ? $job_language : false ); ?>
			</th>
			<th scope="col"
			    class="manage-column column-categories column-product_cat"><?php _e( 'Categories', 'wpml-wcml' ) ?></th>
			<th scope="col" class="manage-column column-product_type">
				<span class="wc-type wcml-tip"
				      data-tip="<?php _e( 'Type', 'wpml-wcml' ) ?>"><?php _e( 'Type', 'wpml-wcml' ) ?></span>
			</th>
			<th scope="col" id="date" class="manage-column column-date"><?php _e( 'Date', 'wpml-wcml' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php if ( empty( $products ) ): ?>
			<tr>
				<td colspan="6" class="text-center"><strong><?php _e( 'No products found', 'wpml-wcml' ); ?></strong>
				</td>
			</tr>
		<?php else: ?>
			<?php foreach ( $products as $product ) :
				$trid       = $sitepress->get_element_trid( $product->ID, 'post_' . $product->post_type );
				$product_translations = $sitepress->get_element_translations( $trid, 'post_' . $product->post_type, true, true );
				if ( ! $slang ) {
					foreach ( $product_translations as $lang_code => $translation ) {
						if ( $translation->original ) {
							$original_lang = $lang_code;
						}
					}
				} else {
					$original_lang = $slang;
				}
				$product_id = apply_filters( 'translate_object_id', $product->ID, 'product', true, $original_lang );

				?>
				<tr>
					<?php //TODO Sergey: Add image URL, product title in alt + a link to the product.
					?>
					<td class="thumb column-thumb">
						<a href="http://localhost/wpml/wp-admin/post.php?post=11&amp;action=edit&amp;lang=en">
							<img width="150" height="150"
							     src="http://localhost/wpml/wp-content/uploads/2015/07/DeathtoStock_EnergyandSerenity7-150x150.jpg"
							     alt="DeathtoStock_EnergyandSerenity7">
						</a>
					</td>


					<td class="wpml-col-title  wpml-col-title-flag">
						<?php echo $product->post_parent != 0 ? '&#8212; ' : ''; ?>
						<strong>
							<?php if ( ! $slang ): ?>
								<span class="icl-title-flag"><img
										src="<?php echo $sitepress->get_flag_url( $original_lang ) ?>"/></span>
							<?php endif; ?>
							<?php //TODO Sergey: Add Edit post link
							?>
							<a href="<?php echo '#' ?>" title="<?php echo strip_tags( $product->post_title );?>">
								<?php echo $product->post_title;?>
							</a>
							<?php if ( $product->post_status == 'draft' && ( ( isset( $_GET['st'] ) && $_GET['st'] != 'draft' ) || ! isset( $_GET['st'] ) ) ): ?>
								- <span class="post-state"><?php _e( 'Draft', 'wpml-wcml' ); ?></span>
							<?php endif; ?>
							<?php if ($search && $product->post_parent != 0): ?>
							| <span
								class="prod_parent_text"><?php printf( __( 'Parent product: %s', 'wpml-wcml' ), get_the_title( $product->post_parent ) ); ?>
								<span>
	                        <?php endif; ?>
						</strong>

						<div class="row-actions">
		                    <span class="edit">
			                    <?php //TODO Sergey: Add edit and View links
			                    ?>
			                    <a href=""
			                       title="<?php _e( 'Edit this item', 'wpml-wcml' );?>"><?php _e( 'Edit', 'wpml-wcml' );?> </a>
		                    </span> | <span class="view">
			                    <?php //TODO Sergey: Add edit and View links
			                    ?>
								<a href=""
								   title="<?php printf( __( 'View "%s"', 'wpml-wcml' ), $product->post_title );?>"><?php _e( 'View', 'wpml-wcml' );?> </a>
		                    </span>

						</div>
					</td>

					<td class="wpml-col-languages">
						<?php
						if ( isset( $current_translator ) ) {
							$prod_lang = $woocommerce_wpml->products->get_original_product_language( $product->ID );
						} else {
							$prod_lang = $slang;
						}
						echo $woocommerce_wpml->products->get_translation_statuses( $product_translations, $active_languages, $prod_lang, $trid, $job_id ? $job_language : false ); ?>
					</td>
					<td class="product_cat column-product_cat">
						<?php //TODO Sergey Put here a nice Categories function (separated by comma)
						?>
					</td>

					<td class="column-product_type">
						<?php
						//TODO Sergey: Check on get_product function (it seems to be deprecated)
						$prod       = get_product( $product->ID );
						$icon_class = $prod->product_type;

						if ( $prod->is_virtual() ) {
							$icon_class = 'virtual';
						} else if ( $prod->is_downloadable() ) {
							$icon_class = 'downloadable';
						}

						?>
						<span class="product-type wcml-tip <?php echo $icon_class;?>"
						      data-tip="<?php echo $icon_class;?>"></span>
					</td>


					<td class="date column-date">
						<?php //TODO Sergey: Put here this nice WP function that shows "13 mins ago" and knows about future posts etc. ;)
						?>
						<?php if ( $product->post_status == "publish" ) { ?>
							<?php echo $product->post_date; ?><br>
							<?php //TODO Sergey: Consider using the same textdomain as WordPress or as WooCommerce (so not always "wpml-wcml") in proper places so that the translation do not have to be done more than once...?>
							<?php _e( 'Published', '' ); ?>
						<?php } else { ?>
							<?php echo $product->post_modified; ?><br>
							<?php _e( 'Last Modified', '' ); ?>
						<?php } ?>


					</td>
				</tr>

				<?php
				if ( isset( $_GET['prid'] ) ) {
					$default_language = $sitepress->get_language_for_element( $_GET['prid'], 'post_product' );
					$display_inline   = true;
					include WCML_PLUGIN_PATH . '/menu/sub/product-data.php';
				}
			endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>


	<?php //TODO Sergey: Add Screen option with number of displayed products
	//TODO Sergey: Test if pagination input works
	?>
	<?php if ( $products && ! isset( $_GET['prid'] ) ): ?>
		<div class="tablenav bottom clearfix">
			<div class="tablenav-pages">
				<span
					class="displaying-num"><?php printf( __( '%d products', 'wpml-wcml' ), $products_count ); ?></span>
		        <span class="pagination-links">
			    <?php if ( ! isset( $_GET['prid'] ) && isset( $last ) && $last > 1 ): ?>
				    <a class="first-page <?php echo $pn == 1 ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url; ?>1"
				       title="<?php _e( 'Go to the first page', 'wpml-wcml' ); ?>">&laquo;</a>
				    <a class="prev-page <?php echo $pn == 1 ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url . ( (int) $pn > 1 ? $pn - 1 : $pn ); ?>"
				       title="<?php _e( 'Go to the previous page', 'wpml-wcml' ); ?>">&lsaquo;</a>
				    <span class="paging-input">
			            <label for="current-page-selector" class="screen-reader-text">
				            <?php _e( 'Select Page', 'wpml-wcml' ); ?>
			            </label>
						<input class="current-page" id="current-page-selector"
						       title="<?php _e( 'Current page', 'wpml-wcml' ); ?>"
						       type="text" name="paged" value="<?php echo $pn; ?>" size="2">
			            &nbsp;<?php _e( 'of', 'wpml-wcml' ); ?>&nbsp;<span
						    class="total-pages"><?php echo $last; ?></span>
		            </span>
				    <a class="next-page <?php echo $pn == $last ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url . ( (int) $pn < $last ? $pn + 1 : $last ); ?>"
				       title="<?php _e( 'Go to the next page', 'wpml-wcml' ); ?>">&rsaquo;</a>
				    <a class="last-page <?php echo $pn == $last ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url . $last; ?>"
				       title="<?php _e( 'Go to the last page', 'wpml-wcml' ); ?>">&raquo;</a>
			    <?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</form>


<div class="wpml-dialog wpml-dialog-translate wcml-pt-form">
	<header class="wpml-dialog-header">
		<?php //TODO Add title ?>
		<h2 class="wpml-dialog-title"><?php printf( __( 'Product translation:  %s', 'wpml-wcml' ), "<strong>Product title</strong>" ); ?></h2>
		<?php //TODO Add link and title ?>
		<a href="#" class="view"
		   title="<?php printf( __( 'View "%s"', 'wpml-wcml' ), "Product title" ); ?>"><?php _e( 'View Product', 'wpml-wcml' ); ?> </a>
		<?php //TODO Sergey: close Dialog on wpml-dialog-close (not on icon classes) ?>
		<i class="dashicons dashicons-no wpml-dialog-close"></i>
	</header>
	<form class="wpml-dialog-body"
	      id="poststuff"> <?php //   IMpoRTANT This ID must stay like this if it is impossible -> create additional div ?>
		<header class="wpml-translation-header">
			<h3 class="wpml-header-original"><?php _e( 'Original', 'wpml-wcml' ); ?>: <span class="icl-title-flag"><img
						src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"
						alt="English"></span><strong>English</strong></h3>

			<h3 class="wpml-header-translation"><?php _e( 'Translation to', 'wpml-wcml' ); ?>: <span
					class="icl-title-flag"><img
						src="https://wpml.org/wp-content/plugins/sitepress-multilingual-cms/res/flags/es.png"
						alt="Spanish"></span><strong>Spanish</strong></h3>
			<a class="button-copy" title="<?php _e( 'Copy from original' ); ?>"><i
					class="otgs-ico-copy"></i> <?php _e( 'Copy all fields from original', 'wpml-wcml' ); ?></a>
		</header>


		<?php
		//TODO Sergey: Do that... Right ;)
		//TODO Sergey: I disabled possibility of moving boxes since I cannot force WP to remember where the boxes were moved to, but if you know how to do that feel free
		wp_enqueue_script( 'postbox' );
		//wp_enqueue_script( 'postbox-edit', WCML_PLUGIN_PATH.'res/js/postbox-edit.js', array('jquery', 'postbox') );

		?>
		<script type="text/javascript">
			jQuery(document).on('ready', function ($) {
				//TODO Sergey: I disabled remembering open/close state and order because it wasn't working anyway, bu if you know how to make it work feel free
				postboxes.save_state = function () {
					return;
				};
				postboxes.save_order = function () {
					return;
				};
				postboxes.add_postbox_toggles();
			});
		</script>

		<div class="wpml-form-row">
			<label for="term-name"> Title </label>
			<input disabled id="term-name-original" value="Children" type="text">
			<a class="button-copy" title="<?php _e( 'Copy from original' ); ?>"><i
					class="otgs-ico-copy otgs-ico-32"></i></a>
			<input id="term-name" value="Niños" type="text">
		</div>

		<div class="wpml-form-row">
			<label for="term-slug">Slug</label>
			<input disabled id="term-slug-original" value="children" type="text">
			<a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
					class="otgs-ico-copy otgs-ico-32"></i></a>
			<input id="term-slug" value="ninos" type="text">
		</div>

		<div class="wpml-form-row">
			<label for="term-description">Content /<br>Description</label>
			<textarea disabled id="term-description-original" cols="22" rows="4"></textarea>
			<a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
					class="otgs-ico-copy otgs-ico-32"></i></a>
			<textarea id="term-description" cols="22" rows="4"></textarea>
		</div>


		<div class="postbox wpml-form-row wcml-row-excerpt">
			<div title="<?php _e( 'Click to toggl' ); ?>" class="handlediv"><br></div>
			<h3 class="hndle"><span>Excerpt</span></h3>

			<div class="inside">
				<textarea disabled id="term-description-original" cols="22" rows="4"></textarea>
				<a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
						class="otgs-ico-copy otgs-ico-32"></i></a>
				<textarea id="term-description" cols="22" rows="4"></textarea>
			</div>
		</div>
		<?php //TODO Sergey: Add: IF no original THEN: class="postbox closed" and <em>(empty)</em> after title ?>
		<div class="postbox wpml-form-row closed">
			<div title="<?php _e( 'Click to toggle' ); ?>" class="handlediv"><br></div>
			<h3 class="hndle"><span>Test 2 <em>(empty)</em> </span></h3>

			<div class="inside">
				testing content
			</div>
		</div>

		<?php //TODO Sergey: Add here the rest of controls and I will style them properly. ?>


		<div class="wpml-dialog-footer sticky">
			<span class="errors icl_error_text"></span>

			<div class="wcml-pt-progress"></div>
			<div class="alignleft">
				<input class="button-secondary cancel" value="Cancel" type="button">
				<input class="button-secondary resign" value="Resign" type="button">
			</div>
			<div class="alignright">
				<input class="button-primary" value="Save" type="submit">
				<input class="button-primary" value="Save&Close" type="submit">
			</div>
		</div>

	</form>
</div>



