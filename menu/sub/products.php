<?php
$pn = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
$lm = ( isset( $_GET['lm'] ) && $_GET['lm'] > 0 ) ? $_GET['lm'] : 20;

$search         = false;
$pagination_url = admin_url( 'admin.php?page=wpml-wcml&tab=products&paged=' );
$filter_url = $filter_cat_url = admin_url( 'admin.php?page=wpml-wcml&tab=products' );
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

if ( ! isset( $products ) &&
    ( isset( $_GET['cat'] ) ||
        isset( $_GET['trst'] ) ||
        isset( $_GET['st'] ) ||
        isset( $_GET['slang'] ) ||
        isset( $_GET['s'] ) ||
        isset( $_GET['cat'] ) ||
        isset( $_GET['ts'] ) ||
        isset( $_GET['ds'] ) ) ) {

    if( isset( $_GET['s'] ) && $_GET['s'] == '' ){
        unset( $_GET['s'] );
    }

	$products_data  = $woocommerce_wpml->products->get_products_from_filter(    isset( $_GET['s'] ) ? $_GET['s'] : '' ,
                                                                                isset( $_GET['cat'] ) ? $_GET['cat'] : false,
                                                                                isset( $_GET['trst'] ) ? $_GET['trst'] : false,
                                                                                isset( $_GET['st'] ) ? $_GET['st'] : false,
                                                                                $slang,
                                                                                $pn,
                                                                                $lm,
                                                                                isset( $_GET['ts'] ) ? $_GET['ts'] : false,
                                                                                isset( $_GET['ds'] ) ? $_GET['ds'] : false );
	$products       = $products_data['products'];
	$products_count = $products_data['count'];

    if( isset( $_GET['cat'] ) ){
        $search         = true;
    }

	$pagination_url = admin_url( 'admin.php?'. http_build_query( $_GET ). '&paged=' );

    $filters_array = $_GET;
    if( isset($filters_array['ts'] ) ){
        unset( $filters_array['ts'] );
        unset( $filters_array['ds'] );
    }elseif( isset($filters_array['ds'] ) ){
        unset( $filters_array['ds'] );
        unset( $filters_array['ts'] );
    }

    $filter_url = admin_url( 'admin.php?'. http_build_query( $filters_array ) );

    $filters_array = $_GET;
    if( isset($filters_array['cat'] ) ){
        unset( $filters_array['cat'] );
    }

    $filter_cat_url = admin_url( 'admin.php?'. http_build_query( $filters_array ) );
}

$title_sort = isset( $_GET['ts'] ) ? $_GET['ts'] == 'asc' ? 'desc' : 'asc' : 'asc';

$date_sort = isset( $_GET['ds'] ) ? $_GET['ds'] == 'asc' ? 'desc' : 'asc' : 'asc';

if ( ! isset( $products ) && current_user_can( 'wpml_operate_woocommerce_multilingual' ) ) {
	$products       = $woocommerce_wpml->products->get_product_list( $pn, $lm, $slang );
	$products_count = $woocommerce_wpml->products->get_products_count( $slang );
}

if ( $lm ) {
	$last = $woocommerce_wpml->products->get_product_last_page( $products_count, $lm );
}

$button_labels = array(
	'save'   => esc_attr__( 'Save', 'woocommerce-multilingual' ),
	'update' => esc_attr__( 'Update', 'woocommerce-multilingual' ),
);
?>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<?php if ( ! isset( $_GET['prid'] ) && ! $translator_id ): ?>
		<div class="tablenav top clearfix">
			<div class="alignleft">
				<select class="wcml_translation_status_lang">
					<option
						value="all" <?php echo ! $slang ? 'selected="selected"' : ''; ?> ><?php _e( 'All languages', 'woocommerce-multilingual' ); ?></option>
					<?php foreach ( $active_languages as $lang ): ?>
						<option
							value="<?php echo $lang['code'] ?>" <?php echo ( $slang == $lang['code'] ) ? 'selected="selected"' : ''; ?> ><?php echo $lang['display_name'] ?></option>
					<?php endforeach; ?>
				</select>

				<select class="wcml_product_category">
					<option value="0"><?php _e( 'All categories', 'woocommerce-multilingual' ); ?></option>
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
				<select class="wcml_translation_status">
					<option value="all"><?php _e( 'All translation statuses', 'woocommerce-multilingual' ); ?></option>
					<option
						value="not" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'not' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Not translated or needs updating', 'woocommerce-multilingual' ); ?></option>
					<option
						value="need_update" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'need_update' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Needs updating', 'woocommerce-multilingual' ); ?></option>
					<option
						value="in_progress" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'in_progress' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Translation in progress', 'woocommerce-multilingual' ); ?></option>
					<option
						value="complete" <?php echo ( isset( $_GET['trst'] ) && $_GET['trst'] == 'complete' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Translation complete', 'woocommerce-multilingual' ); ?></option>
				</select>

				<?php
				$all_statuses = get_post_stati();
				//unset unnecessary statuses
				unset( $all_statuses['trash'], $all_statuses['auto-draft'], $all_statuses['inherit'], $all_statuses['wc-pending'], $all_statuses['wc-processing'], $all_statuses['wc-on-hold'], $all_statuses['wc-completed'], $all_statuses['wc-cancelled'], $all_statuses['wc-refunded'], $all_statuses['wc-failed'] );
				?>
				<select class="wcml_product_status">
					<option value="all"><?php _e( 'All statuses', 'woocommerce-multilingual' ); ?></option>
					<?php foreach ( $all_statuses as $key => $status ): ?>
						<option
							value="<?php echo $key; ?>" <?php echo ( isset( $_GET['st'] ) && $_GET['st'] == $key ) ? 'selected="selected"' : ''; ?> ><?php echo ucfirst( $status ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" value="filter"
				        class="button-secondary wcml_search"><?php _e( 'Filter', 'woocommerce-multilingual' ); ?></button>
                <?php if($search): ?>
                    <button type="button" value="reset"
				        class="button-secondary wcml_reset_search"><?php _e( 'Reset', 'woocommerce-multilingual' ); ?></button>
                <?php endif; ?>
			</div>

			<div class="alignright">
				<input type="search" class="wcml_product_name" placeholder="<?php _e( 'Search', 'woocommerce-multilingual' ); ?>"
				       value="<?php echo isset( $_GET['s'] ) ? $_GET['s'] : ''; ?>"/>
				<input type="hidden" value="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=products' ); ?>"
				       class="wcml_products_admin_url"/>
				<input type="hidden" value="<?php echo $pagination_url; ?>" class="wcml_pagination_url"/>

				<button type="button" value="search"
				        class="button-secondary wcml_search_by_title"><?php _e( 'Search', 'woocommerce-multilingual' ); ?></button>
			</div>
		</div>
	<?php endif; ?>

    <input type="hidden" id="upd_product_nonce" value="<?php echo wp_create_nonce('update_product_actions'); ?>" />
    <input type="hidden" id="get_product_data_nonce" value="<?php echo wp_create_nonce('wcml_product_data'); ?>" />

	<table class="widefat fixed wpml-list-table wp-list-table striped" cellspacing="0">
		<thead>
		<tr>
			<th scope="col" class="column-thumb">
				<span class="wc-image wcml-tip"
				      data-tip="<?php _e( 'Image', 'woocommerce-multilingual' ) ?>"><?php _e( 'Image', 'woocommerce-multilingual' ) ?></span>
			</th>
			<th scope="col" class="wpml-col-title <?php echo isset( $_GET['ts']) ? ' sorted '.$_GET['ts'] : ''; ?>">
                <a href="<?php echo $filter_url.'&ts='.$title_sort; ?>">
                    <span><?php _e( 'Product', 'woocommerce-multilingual' ) ?></span>
                    <span class="sorting-indicator"></span>
                </a>
            </th>
			<th scope="col" class="wpml-col-languages">
				<?php echo $woocommerce_wpml->products->get_translation_flags( $active_languages, $slang, $job_id ? $job_language : false ); ?>
			</th>
			<th scope="col"
			    class="column-categories"><?php _e( 'Categories', 'woocommerce-multilingual' ) ?></th>
			<th scope="col" class="column-product_type">
				<span class="wc-type wcml-tip"
				      data-tip="<?php _e( 'Type', 'woocommerce-multilingual' ) ?>"><?php _e( 'Type', 'woocommerce-multilingual' ) ?></span>
			</th>
			<th scope="col" id="date" class="column-date <?php echo isset( $_GET['ds'] ) ? ' sorted '.$_GET['ds'] : ''; ?>">
                <a href="<?php echo $filter_url.'&ds='.$date_sort; ?>">
                    <span><?php _e( 'Date', 'woocommerce-multilingual' ) ?></span>
                    <span class="sorting-indicator"></span>
                </a>
            </th>
		</tr>
		</thead>
		<tbody>
		<?php if ( empty( $products ) ): ?>
			<tr>
				<td colspan="6" class="text-center"><strong><?php _e( 'No products found', 'woocommerce-multilingual' ); ?></strong>
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
					<td class="thumb column-thumb">
						<a href="<?php echo get_edit_post_link( $product->ID ); ?>">
                            <?php
                            if( has_post_thumbnail( $product->ID ) ){
                                echo get_the_post_thumbnail( $product->ID, 150, array( 'alt' => strip_tags( $product->post_title ) ) );
                            }else{
                                echo wc_placeholder_img( 150 );
                            }
							?>
						</a>
                    </td>
					<td class="wpml-col-title  wpml-col-title-flag">
						<?php echo $product->post_parent != 0 ? '&#8212; ' : ''; ?>
						<strong>
							<?php if ( ! $slang ): ?>
								<span class="wpml-title-flag"><img
										src="<?php echo $sitepress->get_flag_url( $original_lang ) ?>"/></span>
							<?php endif; ?>
							<a href="<?php echo get_edit_post_link( $product->ID ); ?>" title="<?php echo strip_tags( $product->post_title );?>">
								<?php echo $product->post_title;?>
							</a>
							<?php if ( $product->post_status == 'draft' && ( ( isset( $_GET['st'] ) && $_GET['st'] != 'draft' ) || ! isset( $_GET['st'] ) ) ): ?>
								- <span class="post-state"><?php _e( 'Draft', 'woocommerce-multilingual' ); ?></span>
							<?php endif; ?>
							<?php if ($search && $product->post_parent != 0): ?>
							| <span
								class="prod_parent_text"><?php printf( __( 'Parent product: %s', 'woocommerce-multilingual' ), get_the_title( $product->post_parent ) ); ?>
								<span>
	                        <?php endif; ?>
						</strong>

						<div class="row-actions">
		                    <span class="edit">
			                    <a href="<?php echo get_edit_post_link( $product->ID ); ?>"
			                       title="<?php _e( 'Edit this item', 'woocommerce-multilingual' );?>"><?php _e( 'Edit', 'woocommerce-multilingual' );?> </a>
		                    </span> | <span class="view">
								<a href="<?php echo get_post_permalink( $product->ID ); ?>"
								   title="<?php printf( __( 'View "%s"', 'woocommerce-multilingual' ), $product->post_title );?>" target="_blank"><?php _e( 'View', 'woocommerce-multilingual' );?> </a>
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
						echo $woocommerce_wpml->products->get_translation_statuses( $product->ID, $product_translations, $active_languages, $prod_lang, $trid, $job_id ? $job_language : false ); ?>
					</td>
					<td class="column-categories">
                        <?php $product_categories = wp_get_object_terms( $product->ID, 'product_cat' );
                        foreach( $product_categories as $key => $product_category ): ?>
                            <a href="<?php echo $filter_cat_url.'&cat='.$product_category->term_id; ?>"><?php echo $product_category->name.( array_key_exists( $key+1, $product_categories ) ? ', ': '' ) ?></a>
                        <?php endforeach; ?>
					</td>

					<td class="column-product_type">
						<?php
						$prod       = wc_get_product( $product->ID );
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


					<td class="column-date">
						<?php if ( $product->post_status == "publish" ) { ?>
							<?php echo date(' Y/m/d', strtotime( $product->post_date ) ); ?><br>
							<?php _e( 'Published', 'woocommerce-multilingual' ); ?>
						<?php } else { ?>
							<?php echo date(' Y/m/d', strtotime( $product->post_modified ) ); ?><br>
							<?php _e( 'Last Modified', 'woocommerce-multilingual' ); ?>
						<?php } ?>


					</td>
				</tr>

				<?php
				if ( isset( $_GET['prid'] ) ) {
                    $default_language = $sitepress->get_language_for_element($_GET['prid'], 'post_product');
                    $display_inline = true;
                    include WCML_PLUGIN_PATH . '/menu/sub/product-data.php';
                }
			endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $products && ! isset( $_GET['prid'] ) ): ?>
		<div class="tablenav bottom clearfix">
			<div class="tablenav-pages">
				<span
					class="displaying-num"><?php printf( __( '%d items', 'woocommerce-multilingual' ), $products_count ); ?></span>
		        <span class="pagination-links">
			    <?php if ( ! isset( $_GET['prid'] ) && isset( $last ) && $last > 1 ): ?>
				    <a class="first-page <?php echo $pn == 1 ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url; ?>1"
				       title="<?php _e( 'Go to the first page', 'woocommerce-multilingual' ); ?>">&laquo;</a>
				    <a class="prev-page <?php echo $pn == 1 ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url . ( (int) $pn > 1 ? $pn - 1 : $pn ); ?>"
				       title="<?php _e( 'Go to the previous page', 'woocommerce-multilingual' ); ?>">&lsaquo;</a>
				    <span class="paging-input">
			            <label for="current-page-selector" class="screen-reader-text">
				            <?php _e( 'Select Page', 'woocommerce-multilingual' ); ?>
			            </label>
						<input class="current-page" id="current-page-selector"
						       title="<?php _e( 'Current page', 'woocommerce-multilingual' ); ?>"
						       type="text" name="paged" value="<?php echo $pn; ?>" size="2">
			            &nbsp;<?php _e( 'of', 'woocommerce-multilingual' ); ?>&nbsp;<span
						    class="total-pages"><?php echo $last; ?></span>
		            </span>
				    <a class="next-page <?php echo $pn == $last ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url . ( (int) $pn < $last ? $pn + 1 : $last ); ?>"
				       title="<?php _e( 'Go to the next page', 'woocommerce-multilingual' ); ?>">&rsaquo;</a>
				    <a class="last-page <?php echo $pn == $last ? 'disabled' : ''; ?>"
				       href="<?php echo $pagination_url . $last; ?>"
				       title="<?php _e( 'Go to the last page', 'woocommerce-multilingual' ); ?>">&raquo;</a>
			    <?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

</form>